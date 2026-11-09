<?php

namespace Payfurl\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;

class PayfurlAssignAdditionalDataObserver extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    public const TOKEN_OBJECT_STRING = 'payfurlToken';
    public const PAYFURL_CHARGE_ID = 'payfurlChargeId';
    public const PAYMENT_METHOD_ID = 'paymentMethodId';

    public const SAVE_MY_PAYMENT = 'payfurlSaveMyPayment';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::TOKEN_OBJECT_STRING,
        self::PAYMENT_METHOD_ID,
        self::SAVE_MY_PAYMENT,
        self::PAYFURL_CHARGE_ID
    ];

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
