<?php
declare(strict_types=1);

namespace Payfurl\Payment\Gateway\Validator;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Payfurl\Payment\Gateway\SubjectReader;

/**
 * Checks a charge before Magento invoices it: the charge must have succeeded (or still be pending, which leaves
 * the invoice unpaid and the order in Payment Review), and its amount, currency and reference must match this
 * order. A charge another order already holds is rejected, so a charge ID posted from the browser can't be reused.
 */
class ChargeResponseValidator extends GeneralResponseValidator
{
    public const STATUS_SUCCESS = 'SUCCESS';
    public const PENDING_STATUSES = ['PENDING', 'PENDING_CHECKOUT'];

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader,
        ErrorCodeProvider $errorCodeProvider,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($resultFactory, $subjectReader, $errorCodeProvider);
    }

    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        $result = parent::validate($validationSubject);
        if (!$result->isValid()) {
            return $result;
        }

        $charge = $this->subjectReader->readResponseData($validationSubject);
        $order = $this->subjectReader->readPayment($validationSubject)->getPayment()->getOrder();

        $error = $this->findError((array)$charge, $order);

        // An unmapped code is logged as "Payment Error: <code>" and the shopper sees the generic decline message.
        return $error === null ? $this->createResult(true) : $this->createResult(false, [], [$error]);
    }

    /**
     * Returns why the charge can't pay this order, or null when it can.
     *
     * @param array $charge
     * @param \Magento\Sales\Model\Order $order
     * @return string|null
     */
    private function findError(array $charge, $order): ?string
    {
        $chargeId = (string)($charge['chargeId'] ?? '');
        if ($chargeId === '') {
            return 'charge_missing';
        }

        $status = (string)($charge['status'] ?? '');
        if ($status !== self::STATUS_SUCCESS && !in_array($status, self::PENDING_STATUSES, true)) {
            return 'charge_status_' . strtolower($status ?: 'unknown');
        }

        if (strtoupper((string)($charge['currency'] ?? '')) !== strtoupper((string)$order->getOrderCurrencyCode())) {
            return 'charge_currency_mismatch';
        }

        if (round((float)($charge['amount'] ?? 0), 2) !== round((float)$order->getGrandTotal(), 2)) {
            return 'charge_amount_mismatch';
        }

        // Charges created by the browser SDK (PayPal, wallets, checkout methods) carry no reference.
        $reference = (string)($charge['reference'] ?? '');
        if ($reference !== '' && $reference !== 'Order #' . $order->getIncrementId()) {
            return 'charge_reference_mismatch';
        }

        if ($this->isUsedByAnotherOrder($chargeId, $order->getEntityId())) {
            return 'charge_already_used';
        }

        return null;
    }

    /**
     * @param string $chargeId
     * @param int|string|null $orderId null while the order is being placed
     * @return bool
     */
    private function isUsedByAnotherOrder(string $chargeId, $orderId): bool
    {
        $criteria = $this->searchCriteriaBuilder->addFilter('txn_id', $chargeId)->create();
        foreach ($this->transactionRepository->getList($criteria)->getItems() as $transaction) {
            if ((string)$transaction->getOrderId() !== (string)$orderId) {
                return true;
            }
        }

        return false;
    }
}
