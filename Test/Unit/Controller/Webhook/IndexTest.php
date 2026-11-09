<?php
declare(strict_types=1);

namespace Payfurl\Payment\Test\Unit\Controller\Webhook;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Payfurl\Payment\Controller\Webhook\Index;
use Payfurl\Payment\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * POST /payfurl/webhook: a signed webhook for a charge an order holds settles that order if it is in Payment Review,
 * from the charge payFURL returns (Payment::update()), never from the body.
 */
class IndexTest extends TestCase
{
    private const KEY = 'signing-key';
    private const BODY = '{"meta":{"type":"transaction"},"data":{"chargeId":"ch_1"}}';

    private string $body = self::BODY;
    private ?string $signature = null;
    private string $storeKey = self::KEY;
    private ?Order $order = null;
    private Payment&MockObject $payment;
    private OrderRepositoryInterface&MockObject $orders;
    private array $response = [];

    protected function setUp(): void
    {
        $this->payment = $this->createMock(Payment::class);
        $this->orders = $this->createMock(OrderRepositoryInterface::class);
        $this->order = $this->order(Order::STATE_PAYMENT_REVIEW);
    }

    public function testASignedWebhookSettlesAnOrderInPaymentReview(): void
    {
        $this->payment->expects($this->once())->method('update');
        $this->orders->expects($this->once())->method('save')->with($this->order);

        $this->assertSame([200, 'updated'], $this->post());
    }

    public function testAWrongSignatureChangesNothing(): void
    {
        $this->signature = base64_encode(hash_hmac('sha256', self::BODY, 'another key', true));
        $this->payment->expects($this->never())->method('update');

        $this->assertSame([401, 'invalid signature'], $this->post());
    }

    public function testNoSigningKeyOnTheStoreRefusesEveryWebhook(): void
    {
        $this->storeKey = '';
        $this->signature = base64_encode(hash_hmac('sha256', self::BODY, '', true));
        $this->payment->expects($this->never())->method('update');

        $this->assertSame([401, 'invalid signature'], $this->post());
    }

    public function testAnOrderNotInPaymentReviewIsLeftAlone(): void
    {
        $this->order = $this->order(Order::STATE_PROCESSING);
        $this->payment->expects($this->never())->method('update');

        $this->assertSame([200, 'ignored: order is not in payment review'], $this->post());
    }

    public function testAChargeNoOrderHoldsIsIgnored(): void
    {
        $this->order = null;

        $this->assertSame([200, 'ignored: no order holds this charge'], $this->post());
    }

    public function testAWebhookWithoutAChargeIsIgnored(): void
    {
        $this->body = '{"meta":{"type":"customer"},"data":{"customerId":"cus_1"}}';

        $this->assertSame([200, 'ignored: not a charge'], $this->post());
    }

    public function testAFailedUpdateAsksPayfurlToRetry(): void
    {
        $this->payment->method('update')->willThrowException(new \RuntimeException('API down'));
        $this->orders->expects($this->never())->method('save');

        $this->assertSame([500, 'update failed'], $this->post());
    }

    private function order(string $state): Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getState')->willReturn($state);
        $order->method('getStoreId')->willReturn(2);
        $order->method('getIncrementId')->willReturn('000000042');
        $order->method('getPayment')->willReturn($this->payment);
        return $order;
    }

    /** @return array{0: int, 1: string} the response's status code and text */
    private function post(): array
    {
        $request = $this->createMock(HttpRequest::class);
        $request->method('getContent')->willReturn($this->body);
        $request->method('getHeader')->with(Index::SIGNATURE_HEADER)
            ->willReturn($this->signature ?? base64_encode(hash_hmac('sha256', $this->body, self::KEY, true)));

        $raw = $this->createMock(Raw::class);
        $raw->method('setHttpResponseCode')->willReturnCallback(function ($code) use ($raw) {
            $this->response[0] = $code;
            return $raw;
        });
        $raw->method('setContents')->willReturnCallback(function ($text) use ($raw) {
            $this->response[1] = $text;
            return $raw;
        });
        $rawFactory = $this->createMock(RawFactory::class);
        $rawFactory->method('create')->willReturn($raw);

        $config = $this->createMock(Config::class);
        $config->method('getWebhookSigningKey')->with(2)->willReturn($this->storeKey);

        $criteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $criteriaBuilder->method('addFilter')->with('txn_id', 'ch_1')->willReturnSelf();
        $criteriaBuilder->method('setPageSize')->willReturnSelf();
        $criteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));
        $found = $this->createMock(TransactionSearchResultInterface::class);
        $found->method('getItems')->willReturn($this->order ? [new DataObject(['order_id' => 7])] : []);
        $transactions = $this->createMock(TransactionRepositoryInterface::class);
        $transactions->method('getList')->willReturn($found);
        $this->orders->method('get')->with(7)->willReturn($this->order);

        (new Index($request, $rawFactory, $config, $transactions, $this->orders, $criteriaBuilder,
            $this->createMock(LoggerInterface::class)))->execute();

        ksort($this->response);
        return $this->response;
    }
}
