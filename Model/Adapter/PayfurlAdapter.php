<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Model\Adapter;

use payFURL\Sdk as PayFurlSDK;

/**
 * Class PayfurlAdapter
 * @codeCoverageIgnore
 */
class PayfurlAdapter
{
    /**
     * @var \Payfurl\Payment\Model\Config
     */
    private $config;

    /**
     * @param \Payfurl\Payment\Model\Config $config
     */
    public function __construct(
        \Payfurl\Payment\Model\Config $config
    ) {
        $this->config = $config;
        PayFurlSDK\Config::initialise($this->config->getSecretKey(), $this->config->getEnv());
    }

    /**
     * @param $data
     * @return array|mixed
     */
    public function chargeByToken($data)
    {
        $svc = new PayFurlSDK\Charge();
        try {
            return $svc->CreateWithToken($data);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }

    /**
     * @param $data
     * @return array|mixed
     */
    public function getTransactionInfo($data)
    {
        $svc = new PayFurlSDK\Charge();
        try {
            return $svc->Single($data);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }

    /**
     * @param $data
     * @return array|mixed
     * @throws \Exception
     */
    public function refund($data)
    {
        $svc = new PayFurlSDK\Charge();
        try {
            return $svc->Refund($data);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }

    public function getCustomerPaymentMethods($data)
    {
        $svc = new PayFurlSDK\Customer();
        try {
            return $svc->CustomerPaymentMethods($data);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }

    public function chargeByPaymentMethod($data)
    {
        $svc = new PayFurlSDK\Charge();
        try {
            return $svc->CreateWithPaymentMethod($data);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }

    public function chargeByCustomerToken($data, $customerPayfurlId)
    {
        try {
            if ($customerPayfurlId) {
                return $this->chargeForExistPayFurlCustomer($data, $customerPayfurlId);
            } else {
                return $this->chargeForNewPayFurlCustomer($data);
            }
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }

    protected function chargeForExistPayFurlCustomer($data, $customerPayfurlId)
    {
        $customerSdk = new PayFurlSDK\Customer();
        $chargeSdk = new PayFurlSDK\Charge();

        $data['CustomerId'] = $customerPayfurlId;
        if ($data['payfurlSaveMyPayment']) {
            $response = $customerSdk->CreatePaymentMethodWithToken($data);
            $data['PaymentMethodId'] = $response['paymentMethodId'];
            return $chargeSdk->CreateWithPaymentMethod($data);
        } else {
            // login customer but use a new card
            //return $chargeSdk->CreateWithCustomer($data); # issue charge a default card
            return $chargeSdk->CreateWithToken($data);
        }
    }

    protected function chargeForNewPayFurlCustomer($data)
    {
        $chargeSdk = new PayFurlSDK\Charge();

        if ($data['payfurlSaveMyPayment']) {
            $customerSdk = new PayFurlSDK\Customer();
            $response = $customerSdk->CreateWithToken($data);
            $data['CustomerId'] = $response['customerId'];
            return $chargeSdk->CreateWithCustomer($data);
        } else {
            // not save card
            return $chargeSdk->CreateWithToken($data);
        }
    }
}
