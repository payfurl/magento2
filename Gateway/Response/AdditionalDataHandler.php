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
    const PAYFURL_TYPE = "payfurlType";
    const CARD_TYPE = "CARD";

    const PAYPAL_TYPE = "PAYPAL";

    const PAYPAL_EMAIL = "paypalEmail";

    const CARD_NUMBER = 'cardNumber';

    const CARD_HOLDER = 'cardHolder';

    const EXPIRY_DATE = 'expiryDate';

    const CC_TYPE = "type";
    const STATUS = "status";
    const DATE_ADDED = "dateAdded";
    const CURRENCY = "currency";
    const PROVIDER_NAME = "providerName";

    const TRANSACTION = "transaction";

    const REFUND_TRANSACTION = "refund";

    const CHARGE_ID = "chargeId";
    const PROVIDER_CHARGE_ID = "providerChargeId";
    const AMOUNT = "amount";
    const PROVIDER_ID = "providerId";
    const REFERENCE = "reference";
    const SUCCESS_DATE = "successDate";
    const VOID_DATE = "voidDate";

    const REFUND_AMOUNT = "refundAmount";
    const REFUND_DATE = "refundDateAdded";
    const REFUND_COMMENT = "refundComment";

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

            if (!isset($paymentInformation['type'])
                || ($paymentInformation['type'] != self::CARD_TYPE && $paymentInformation['type'] != self::PAYPAL_TYPE)
            ) {
                return $additionalData;
            }

            $additionalData[self::PAYFURL_TYPE] = $paymentInformation['type'];

            $this->getCustomerCardInformation($additionalData, $paymentInformation);

            $this->getPaypalInformation($additionalData, $paymentInformation);

            $additionalData[self::STATUS] = $transaction['status'];
            $additionalData[self::DATE_ADDED] = $transaction['dateAdded'];
            $additionalData[self::CURRENCY] = strtoupper($transaction['currency']);

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
            $additionalData[self::CARD_NUMBER] = $cardDetail["cardNumber"];
            $additionalData[self::CARD_HOLDER] = $cardDetail["cardholder"];
            $additionalData[self::EXPIRY_DATE] = $cardDetail["expiryDate"];
            $additionalData[self::CC_TYPE] = $cardDetail["type"];
        }
    }

    /**
     * @param $additionalData
     * @param $paymentInformation
     * @return void
     */
    private function getPaypalInformation(&$additionalData, $paymentInformation)
    {
        if ($paymentInformation["type"] == self::PAYPAL_TYPE) {
            $additionalData[self::PAYPAL_EMAIL] = $paymentInformation["email"];
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
            $additionalData[self::CHARGE_ID] = $transaction['chargeId'];
            $additionalData[self::PROVIDER_CHARGE_ID] = $transaction['providerChargeId'];
            $additionalData[self::AMOUNT] = $transaction['amount'];
            $additionalData[self::PROVIDER_ID] = $transaction['providerId'];
            $additionalData[self::REFERENCE] = $transaction['reference'];
            $additionalData[self::SUCCESS_DATE] = $transaction['successDate'];
            $additionalData[self::VOID_DATE] = $transaction['voidDate'];

            if ($type == self::REFUND_TRANSACTION && !empty($transaction["refunds"])) {
                $lastRefundTransaction = end($transaction["refunds"]);
                $additionalData[self::REFUND_AMOUNT] = $lastRefundTransaction["amount"];
                $additionalData[self::REFUND_DATE] = $lastRefundTransaction["dateAdded"];
                $additionalData[self::REFUND_COMMENT] = $lastRefundTransaction["comment"];
            }
        }
    }
}
