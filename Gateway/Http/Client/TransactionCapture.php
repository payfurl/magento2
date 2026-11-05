<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Payfurl\Payment\Model\Adapter\PayfurlAdapter;

/**
 * Charges the order, or fetches the charge the browser SDK created. ChargeResponseValidator then checks the result.
 */
class TransactionCapture extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data, PayfurlAdapter $adapter)
    {
        /*Paypal, wallets, checkout methods - payment has been completed by client sdk*/
        if (!empty($data["ChargeId"])) {
            return $adapter->getTransactionInfo(['ChargeId' => $data['ChargeId']]);
        }

        if (!empty($data["Token"])) {
            if (!empty($data['loginCustomer'])) {
                // this method will respond customerId from PayFURL
                return $adapter->chargeByCustomerToken($data, $data['customerPayfurlId'] ?? '');
            }
            // guest checkout
            return $adapter->chargeByToken($data);
        }

        if (!empty($data['PaymentMethodId'])) {
            $this->assertOwnPaymentMethod($data, $adapter);
            return $adapter->chargeByPaymentMethod($data);
        }

        throw new LocalizedException(__('No payment details were received. Please try again.'));
    }

    /**
     * A saved payment method can only be charged for the logged-in customer it was saved for.
     *
     * @param array $data
     * @param PayfurlAdapter $adapter
     * @throws LocalizedException
     */
    private function assertOwnPaymentMethod(array $data, PayfurlAdapter $adapter): void
    {
        $customerPayfurlId = $data['customerPayfurlId'] ?? '';
        if (empty($data['loginCustomer']) || $customerPayfurlId === '') {
            throw new LocalizedException(__('This saved payment method can\'t be used. Please choose another.'));
        }

        $methods = $adapter->getCustomerPaymentMethods(['CustomerId' => $customerPayfurlId]);
        if (isset($methods['errorCode']) || isset($methods['errorMessage'])) {
            throw new LocalizedException(__('Your saved payment methods could not be loaded. Please try again.'));
        }

        foreach ((array)$methods as $method) {
            if (($method['paymentMethodId'] ?? null) === $data['PaymentMethodId']) {
                return;
            }
        }

        throw new LocalizedException(__('This saved payment method can\'t be used. Please choose another.'));
    }
}
