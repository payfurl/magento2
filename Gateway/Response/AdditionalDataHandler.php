<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Response;

use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class PaymentDataHandler
 */
class AdditionalDataHandler implements HandlerInterface
{
    public const PAYFURL_TYPE = "payfurlType";
    public const CARD_TYPE = "CARD";

    public const CUSTOMER_EMAIL = "customerEmail";

    public const CARD_NUMBER = 'cardNumber';

    public const CARD_HOLDER = 'cardHolder';

    public const EXPIRY_DATE = 'expiryDate';

    public const CC_TYPE = "type";
    public const STATUS = "status";
    public const DATE_ADDED = "dateAdded";
    public const CURRENCY = "currency";
    public const PROVIDER_NAME = "providerName";

    public const TRANSACTION = "transaction";

    public const REFUND_TRANSACTION = "refund";

    public const CHARGE_ID = "chargeId";
    public const PROVIDER_CHARGE_ID = "providerChargeId";
    public const AMOUNT = "amount";
    public const PROVIDER_ID = "providerId";
    public const REFERENCE = "reference";
    public const SUCCESS_DATE = "successDate";
    public const VOID_DATE = "voidDate";

    public const REFUND_AMOUNT = "refundAmount";
    public const REFUND_DATE = "refundDateAdded";
    public const REFUND_COMMENT = "refundComment";

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var string
     */
    protected string $transactionType;

    /**
     * Constructor
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
        string $transactionType = "transaction"
    ) {
        $this->subjectReader = $subjectReader;
        $this->transactionType = $transactionType;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $additionalData = $this->getAdditionalDataByType($transaction, $this->transactionType);
        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $additionalData
        );
    }

    /**
     * Get additional data for sales_order_payment/sales_payment_transaction table
     *
     * @param $transaction
     * @param $type
     * @return array
     */
    public function getAdditionalDataByType($transaction, $type = null): array
    {
        $additionalData = [];

        if (isset($transaction["paymentInformation"]) && !empty($transaction["paymentInformation"])) {
            $paymentInformation = $transaction["paymentInformation"];

            if (!isset($paymentInformation['type'])) {
                return $additionalData;
            }

            $additionalData[self::PAYFURL_TYPE] = $paymentInformation['type'];

            $this->getCustomerCardInformation($additionalData, $paymentInformation);

            $this->getPaypalInformation($additionalData, $paymentInformation);

            $additionalData[self::STATUS] = $transaction['status'] ?? null;
            $additionalData[self::DATE_ADDED] = $transaction['dateAdded'] ?? null;
            $additionalData[self::CURRENCY] = strtoupper((string)($transaction['currency'] ?? ''));

            if (!empty($transaction["provider"]) && !empty($transaction["provider"]["name"])) {
                $additionalData[self::PROVIDER_NAME] = $transaction['provider']["name"];
            }

            $this->getTransactionRawDetailData($additionalData, $transaction, $type);

        }

        return $additionalData;
    }

    /**
     * @param $additionalData
     * @param $paymentInformation
     * @return void
     */
    private function getCustomerCardInformation(&$additionalData, $paymentInformation)
    {
        if (!empty($paymentInformation["card"])) {
            $cardDetail = $paymentInformation["card"];
            $additionalData[self::CARD_NUMBER] = $cardDetail["cardNumber"] ?? null;
            $additionalData[self::CARD_HOLDER] = $cardDetail["cardholder"] ?? null;
            $additionalData[self::EXPIRY_DATE] = $cardDetail["expiryDate"] ?? null;
            $additionalData[self::CC_TYPE] = $cardDetail["type"] ?? null;
        }
    }

    /**
     * @param $additionalData
     * @param $paymentInformation
     * @return void
     */
    private function getPaypalInformation(&$additionalData, $paymentInformation)
    {
        if ($paymentInformation["type"] != self::CARD_TYPE) {
            $additionalData[self::CUSTOMER_EMAIL] = $paymentInformation["email"] ?? null;
        }
    }

    /**
     * @param $additionalData
     * @param $transaction
     * @param $type
     * @return void
     */
    private function getTransactionRawDetailData(&$additionalData, $transaction, $type)
    {
        if (!empty($type)) {
            $additionalData[self::CHARGE_ID] = $transaction['chargeId'] ?? null;
            $additionalData[self::PROVIDER_CHARGE_ID] = $transaction['providerChargeId'] ?? null;
            $additionalData[self::AMOUNT] = $transaction['amount'] ?? null;
            $additionalData[self::PROVIDER_ID] = $transaction['providerId'] ?? null;
            $additionalData[self::REFERENCE] = $transaction['reference'] ?? null;
            $additionalData[self::SUCCESS_DATE] = $transaction['successDate'] ?? null;
            $additionalData[self::VOID_DATE] = $transaction['voidDate'] ?? null;

            if ($type == self::REFUND_TRANSACTION && !empty($transaction["refunds"])) {
                $lastRefundTransaction = end($transaction["refunds"]);
                $additionalData[self::REFUND_AMOUNT] = $lastRefundTransaction["amount"] ?? null;
                $additionalData[self::REFUND_DATE] = $lastRefundTransaction["dateAdded"] ?? null;
                $additionalData[self::REFUND_COMMENT] = $lastRefundTransaction["comment"] ?? null;
            }
        }
    }
}
