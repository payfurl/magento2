<?php

namespace Payfurl\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Payfurl\Payment\Model\Payfurl;
use Payfurl\Payment\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Payfurl\Payment\Model\Adapter\PayfurlAdapter;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'payfurl';
    protected $config;
    protected $checkoutSession;
    protected $payfurlAdapter;
    protected $savedPaymentMethods = [];

    public function __construct(
        Config $config,
        CheckoutSession $checkoutSession,
        PayfurlAdapter $payfurlAdapter
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->payfurlAdapter = $payfurlAdapter;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        if ($this->config->isActive() && $this->getCurrentPublicKey()) {
            return [
                'payment' => [
                    Payfurl::METHOD_CODE => [
                        'enabled' => $this->config->isActive(),
                        'env' => $this->config->getEnv(),
                        'publicKey' => $this->getCurrentPublicKey(),
                        'title' => $this->config->getTitle(),
                        'getSavedPayments' => $this->getSavedPayments(),
                        'payfurlSaveMyPayment' => false
                    ]
                ]
            ];
        }
        return [];
    }

    protected function getCurrentPublicKey()
    {
        if ($this->config->getEnv() == 'prod') {
            return $this->config->getLivePublicKey();
        } else {
            return $this->config->getSandboxPublicKey();
        }
    }

    protected function getSavedPayments()
    {
        if (!$this->config->canGetSavedPayments()) {
            return false;
        }

        if (empty($this->savedPaymentMethods)) {
            $customerPayfurlId = $this->getCustomerPayfurlId();

            if (!$customerPayfurlId) {
                return false;
            }

            $data = [
                'CustomerId' => $customerPayfurlId
            ];

            $savedMethods = $this->payfurlAdapter->getCustomerPaymentMethods($data);
            if ($savedMethods && !isset($savedMethods['errorCode'])) {
                foreach ($savedMethods as $number => $method) {
                    $this->savedPaymentMethods[$number] = [
                        'paymentMethodId' => $method['paymentMethodId'],
                        'cardNumber' => $method['card']['cardNumber'],
                        'cardType' => $method['card']['type'],
                        'cardHolder' => $method['card']['cardholder']
                    ];
                }
            }
        }

        return $this->savedPaymentMethods;
    }

    protected function getCustomerPayfurlId()
    {
        $customer = $this->checkoutSession->getQuote()->getCustomer();
        if ($customer->getId() != null) {
            return $customer->getCustomAttribute('payfurl_payment_id') ?
                $customer->getCustomAttribute('payfurl_payment_id')->getValue() : '';
        }
        return '';
    }
}
