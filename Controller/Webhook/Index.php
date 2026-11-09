<?php
declare(strict_types=1);

namespace Payfurl\Payment\Controller\Webhook;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Payfurl\Payment\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * payFURL transaction webhooks: POST /payfurl/webhook. Settles an order held in Payment Review (a PayID, PayTo or
 * other charge that was still pending when the order was placed). The status comes from fetching the charge
 * (Order\Payment::update()), never from the webhook body; the signature only keeps out requests not from payFURL.
 */
class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public const SIGNATURE_HEADER = 'X-Payfurl-Signature';

    public function __construct(
        private readonly HttpRequest $request,
        private readonly RawFactory $rawFactory,
        private readonly Config $config,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): Raw
    {
        $body = (string)$this->request->getContent();
        $chargeId = (string)(json_decode($body, true)['data']['chargeId'] ?? '');
        if ($chargeId === '') {
            return $this->respond(200, 'ignored: not a charge');
        }

        $order = $this->findOrder($chargeId);
        if ($order === null) {
            return $this->respond(200, 'ignored: no order holds this charge');
        }

        $key = $this->config->getWebhookSigningKey($order->getStoreId());
        $signature = (string)$this->request->getHeader(self::SIGNATURE_HEADER);
        if ($key === '' || !self::isSignedBy($body, $signature, $key)) {
            return $this->respond(401, 'invalid signature');
        }

        if ($order->getState() !== Order::STATE_PAYMENT_REVIEW) {
            return $this->respond(200, 'ignored: order is not in payment review');
        }

        try {
            $order->getPayment()->update();
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            $this->logger->critical('payFURL webhook: updating order ' . $order->getIncrementId() . ' failed', [
                'exception' => $e,
            ]);
            return $this->respond(500, 'update failed');
        }

        return $this->respond(200, 'updated');
    }

    /**
     * Same as payFURL's webhooks-sender: base64(HMAC-SHA256(body, signing key)).
     */
    public static function isSignedBy(string $body, string $signature, string $key): bool
    {
        return hash_equals(base64_encode(hash_hmac('sha256', $body, $key, true)), $signature);
    }

    private function findOrder(string $chargeId): ?Order
    {
        $criteria = $this->searchCriteriaBuilder->addFilter('txn_id', $chargeId)->setPageSize(1)->create();
        foreach ($this->transactionRepository->getList($criteria)->getItems() as $transaction) {
            /** @var Order $order */
            $order = $this->orderRepository->get($transaction->getOrderId());
            return $order;
        }

        return null;
    }

    private function respond(int $code, string $text): Raw
    {
        return $this->rawFactory->create()->setHttpResponseCode($code)->setContents($text);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        // Server-to-server call: authenticated by the signature instead of a form key.
        return true;
    }
}
