<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Block;

use Magento\Framework\Phrase;

/**
 * Class ConfigurableInfo
 * @api
 * @since 100.0.2
 */
class OrderTransactionInfo extends \Magento\Payment\Block\Info
{
    const PAYFURL_TYPE = "payfurlType";
    const CARD_TYPE = "CARD";

    const PAYPAL_TYPE = "PAYPAL";

    const PAYPAL_EMAIL = "paypalEmail";

    const CARD_NUMBER = 'cardNumber';

    const CARD_HOLDER = 'cardHolder';

    const CC_TYPE = "type";

    const TRANSACTION_TYPE = "Transaction Type";

    const STATUS = "status";
    const DATE_ADDED = "dateAdded";

    const CURRENCY = "currency";
    const PROVIDER_NAME = "providerName";

    const TRANSACTION_PAYPAL_EMAIL_LABEL = "Paypal Email";

    const TRANSACTION_CARD_NUMBER_LABEL = 'Credit Card Number';

    const TRANSACTION_CARD_HOLDER = 'Card Holder';

    const TRANSACTION_CC_TYPE_LABEL = "Credit Card Type";

    const STATUS_LABEL = "Status";
    const DATE_ADDED_LABEL = "Date Added";

    const CURRENCY_LABEL = "Currency";
    const PROVIDER_NAME_LABEL = "Provider Name";
    /**
     * Prepare payment information
     *
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $areaCode = $this->_appState->getAreaCode();
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();

        if (!empty($payment->getAdditionalInformation(self::PAYFURL_TYPE))) {
            $this->setDataToTransfer(
                $transport,
                self::TRANSACTION_TYPE,
                $payment->getAdditionalInformation(self::PAYFURL_TYPE)
            );

            if ($payment->getAdditionalInformation(self::PAYFURL_TYPE) == self::PAYPAL_TYPE) {
                $this->setDataToTransfer(
                    $transport,
                    self::TRANSACTION_PAYPAL_EMAIL_LABEL,
                    $payment->getAdditionalInformation(self::PAYPAL_EMAIL)
                );
            }

            if ($payment->getAdditionalInformation(self::PAYFURL_TYPE) == self::CARD_TYPE) {
                $this->setDataToTransfer(
                    $transport,
                    self::TRANSACTION_CC_TYPE_LABEL,
                    $payment->getAdditionalInformation(self::CC_TYPE)
                );

                $this->setDataToTransfer(
                    $transport,
                    self::TRANSACTION_CARD_NUMBER_LABEL,
                    $payment->getAdditionalInformation(self::CARD_NUMBER)
                );

                $this->setDataToTransfer(
                    $transport,
                    self::TRANSACTION_CARD_HOLDER,
                    $payment->getAdditionalInformation(self::CARD_HOLDER)
                );
            }

            if ($areaCode == 'adminhtml') {

                $this->setDataToTransfer(
                    $transport,
                    self::STATUS_LABEL,
                    $payment->getAdditionalInformation(self::STATUS)
                );

                $this->setDataToTransfer(
                    $transport,
                    self::DATE_ADDED_LABEL,
                    $payment->getAdditionalInformation(self::DATE_ADDED)
                );

                $this->setDataToTransfer(
                    $transport,
                    self::CURRENCY_LABEL,
                    strtoupper($payment->getAdditionalInformation(self::CURRENCY))
                );

                $this->setDataToTransfer(
                    $transport,
                    self::PROVIDER_NAME_LABEL,
                    $payment->getAdditionalInformation(self::PROVIDER_NAME)
                );
            }
        }
        return $transport;
    }

    /**
     * Sets data to transport
     *
     * @param \Magento\Framework\DataObject $transport
     * @param string $field
     * @param string $value
     * @return void
     */
    protected function setDataToTransfer(
        \Magento\Framework\DataObject $transport,
        $field,
        $value
    ) {
        $transport->setData(
            (string)$this->getLabel($field),
            (string)$this->getValueView(
                $field,
                $value
            )
        );
    }

    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getValueView($field, $value)
    {
        return $value;
    }
}
