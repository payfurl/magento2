<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Http\Client;

/**
 * Class TransactionCapture
 */
class TransactionCapture extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        /*Paypal - payment has been completed by client sdk*/
        if (isset($data["ChargeId"])) {
            $result = $this->adapterFactory->create()->getTransactionInfo($data);
        } elseif (isset($data["Token"])) {
            if (!empty($data['loginCustomer'])) {
                // this method will respond customerId from PayFURL
                $result = $this->adapterFactory->create()->chargeByCustomerToken($data, $data['customerPayfurlId']);
            } else {
                // guest checkout
                $result = $this->adapterFactory->create()->chargeByToken($data);
            }
        } elseif (isset($data['PaymentMethodId'])) {
            // select a saved payment method
            $result = $this->adapterFactory->create()->chargeByPaymentMethod($data);
        }

        return $result;
    }
}
