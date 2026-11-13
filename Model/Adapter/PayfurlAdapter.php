<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Payfurl\Payment\Model\Adapter;

use payFURL\Sdk\ClientOptions;
use payFURL\Sdk\Environment;
use payFURL\Sdk\Exception\PayfurlException;
use payFURL\Sdk\Model\Address;
use payFURL\Sdk\Model\NewChargeCustomer;
use payFURL\Sdk\Model\NewChargePaymentMethod;
use payFURL\Sdk\Model\NewChargeToken;
use payFURL\Sdk\Model\NewCustomerToken;
use payFURL\Sdk\Model\NewPaymentMethodToken;
use payFURL\Sdk\Model\Order;
use payFURL\Sdk\Model\PaymentMethodData;
use payFURL\Sdk\Model\ProductItem;
use payFURL\Sdk\PayfurlClient;
use Payfurl\Payment\Model\Config;

/**
 * The payFURL API with one store's keys (payfurl/phpsdk 3). Each call takes the request builders' array and returns
 * the API's response as an array, or `errorMessage` and `errorCode` when it failed, so the validators, handlers and
 * the checkout's config read what they always have.
 */
class PayfurlAdapter
{
    /**
     * The browser SDK the checkout loads (view/frontend/requirejs-config.js; .github/workflows/sdk-bump.yml updates
     * both). /info/providers lists PayPal only for version 4 or later.
     */
    public const BROWSER_SDK_VERSION = '4.11.2';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var int|string|null
     */
    private $storeId;

    /**
     * @var PayfurlClient|null
     */
    private $client;

    /**
     * @param Config $config
     * @param int|string|null $storeId the order's store, so a multi-website install uses that website's keys
     */
    public function __construct(Config $config, $storeId = null)
    {
        $this->config = $config;
        $this->storeId = $storeId;
    }

    /**
     * Whether both keys work for the environment: the admin's "Validate" button. Each key calls /info/providers,
     * the one endpoint that takes the public key too.
     *
     * @param string $publicKey
     * @param string $secretKey
     * @param string $environment
     * @param ClientOptions|null $options unit tests pass a fake transport
     * @return bool
     */
    public static function keysWork(
        string $publicKey,
        string $secretKey,
        string $environment,
        ?ClientOptions $options = null
    ): bool {
        foreach ([$secretKey, $publicKey] as $key) {
            try {
                if (self::createClient($key, $environment, $options)->info->providers()->hasCardProviders === null) {
                    return false;
                }
            } catch (PayfurlException | \InvalidArgumentException $exception) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $data `Token` and the charge's fields
     * @return array
     */
    public function chargeByToken($data)
    {
        return $this->call(fn() => $this->client()->charges->createWithToken(
            new NewChargeToken(...self::charge($data), token: (string) $data['Token'])
        )->toArray());
    }

    /**
     * @param array $data `ChargeId`
     * @return array
     */
    public function getTransactionInfo($data)
    {
        return $this->call(fn() => $this->client()->charges->get((string) $data['ChargeId'])->toArray());
    }

    /**
     * @param array $data `ChargeId`, and `Amount` and `Comment` for a partial refund
     * @return array
     */
    public function refund($data)
    {
        $amount = (float) ($data['Amount'] ?? 0);
        return $this->call(fn() => $this->client()->charges->refund(
            (string) $data['ChargeId'],
            $amount > 0 ? $amount : null,
            self::string($data, 'Comment')
        )->toArray());
    }

    /**
     * @param array $data `CustomerId`
     * @return array the payment methods, each as an array
     */
    public function getCustomerPaymentMethods($data)
    {
        return $this->call(fn() => array_map(
            static fn(PaymentMethodData $paymentMethod) => $paymentMethod->toArray(),
            $this->client()->customers->paymentMethods((string) $data['CustomerId'])
        ));
    }

    /**
     * @param array $data `PaymentMethodId` and the charge's fields
     * @return array
     */
    public function chargeByPaymentMethod($data)
    {
        return $this->call(fn() => $this->client()->charges->createWithPaymentMethod(
            new NewChargePaymentMethod(...self::charge($data), paymentMethodId: (string) $data['PaymentMethodId'])
        )->toArray());
    }

    /**
     * A logged-in customer's card charge. With `payfurlSaveMyPayment` the card is saved on their payFURL customer
     * (created with their first saved card) and that is charged; without it, the token is charged as a guest's.
     *
     * @param array $data `Token`, `payfurlSaveMyPayment` and the charge's fields
     * @param string $customerPayfurlId the customer's payFURL customer, or '' before their first saved card
     * @return array
     */
    public function chargeByCustomerToken($data, $customerPayfurlId)
    {
        return $this->call(function () use ($data, $customerPayfurlId) {
            $charges = $this->client()->charges;
            $token = (string) $data['Token'];
            if (empty($data['payfurlSaveMyPayment'])) {
                return $charges->createWithToken(new NewChargeToken(...self::charge($data), token: $token))->toArray();
            }

            if ($customerPayfurlId) {
                $paymentMethod = $this->client()->customers->addPaymentMethodWithToken(
                    (string) $customerPayfurlId,
                    new NewPaymentMethodToken(token: $token)
                );
                if (!$paymentMethod->paymentMethodId) {
                    return ['errorMessage' => 'The card could not be saved.', 'errorCode' => 0];
                }
                return $charges->createWithPaymentMethod(new NewChargePaymentMethod(
                    ...self::charge($data),
                    paymentMethodId: $paymentMethod->paymentMethodId
                ))->toArray();
            }

            $customer = $this->client()->customers->createWithToken(new NewCustomerToken(
                token: $token,
                reference: self::string($data, 'Reference'),
                firstName: self::string($data, 'FirstName'),
                lastName: self::string($data, 'LastName'),
                email: self::string($data, 'Email'),
                phone: self::string($data, 'Phone'),
                address: self::address($data['Address'] ?? null)
            ));
            return $charges->createWithCustomer(
                new NewChargeCustomer(...self::charge($data), customerId: (string) $customer->customerId)
            )->toArray();
        });
    }

    /**
     * What the checkout can offer for the quote.
     *
     * @param array $data `amount` and `currency`
     * @return array
     */
    public function getProvidersInfo($data)
    {
        $amount = $data['amount'] ?? null;
        return $this->call(fn() => $this->client()->info->providers(
            is_numeric($amount) ? (float) $amount : null,
            self::string($data, 'currency'),
            self::BROWSER_SDK_VERSION
        )->toArray());
    }

    /**
     * The client for the store's keys, made on the first call: a store without keys still renders its checkout.
     *
     * @return PayfurlClient
     */
    protected function client(): PayfurlClient
    {
        return $this->client ??= self::createClient(
            (string) $this->config->getSecretKey($this->storeId),
            $this->config->getEnv($this->storeId),
            $this->clientOptions()
        );
    }

    /**
     * How the client sends requests: the SDK's defaults. Unit tests return a fake transport.
     *
     * @return ClientOptions|null
     */
    protected function clientOptions(): ?ClientOptions
    {
        return null;
    }

    /**
     * @param string $key
     * @param string $environment the configured environment; anything else is sandbox, as with SDK 2
     * @param ClientOptions|null $options
     * @return PayfurlClient
     */
    private static function createClient(string $key, string $environment, ?ClientOptions $options): PayfurlClient
    {
        return new PayfurlClient(
            $key,
            Environment::tryFrom(strtolower($environment)) ?? Environment::Sandbox,
            $options ?? new ClientOptions()
        );
    }

    /**
     * Runs an API call; a failure becomes the `errorMessage`/`errorCode` array the validators check.
     *
     * @param callable $call
     * @return array
     */
    private function call(callable $call): array
    {
        try {
            return $call();
        } catch (PayfurlException $exception) {
            return ['errorMessage' => $exception->getMessage(), 'errorCode' => $exception->getCode()];
        } catch (\InvalidArgumentException $exception) {
            // The store has no secret key.
            return ['errorMessage' => $exception->getMessage(), 'errorCode' => 0];
        }
    }

    /**
     * The fields every charge request shares, from PaymentDataBuilder and OrderDataBuilder.
     *
     * @param array $data
     * @return array<string, mixed> named arguments for the charge request
     */
    private static function charge(array $data): array
    {
        return [
            'amount' => (float) $data['Amount'],
            'currency' => self::string($data, 'Currency'),
            'reference' => self::string($data, 'Reference'),
            'capture' => isset($data['Capture']) ? (bool) $data['Capture'] : null,
            'email' => self::string($data, 'Email'),
            'phone' => self::string($data, 'Phone'),
            'firstName' => self::string($data, 'FirstName'),
            'lastName' => self::string($data, 'LastName'),
            'address' => self::address($data['Address'] ?? null),
            'order' => self::order($data['Order'] ?? null),
        ];
    }

    /**
     * @param mixed $address
     * @return Address|null
     */
    private static function address($address): ?Address
    {
        if (!is_array($address)) {
            return null;
        }
        return new Address(
            line1: self::string($address, 'Line1'),
            line2: self::string($address, 'Line2'),
            city: self::string($address, 'City'),
            country: self::string($address, 'Country'),
            postalCode: self::string($address, 'PostalCode'),
            state: self::string($address, 'State')
        );
    }

    /**
     * @param mixed $order
     * @return Order|null
     */
    private static function order($order): ?Order
    {
        if (!is_array($order)) {
            return null;
        }
        $items = [];
        foreach ((array) ($order['Items'] ?? []) as $item) {
            $items[] = new ProductItem(
                productCode: self::string($item, 'ProductCode'),
                commodityCode: self::string($item, 'CommodityCode'),
                description: self::string($item, 'Description'),
                quantity: isset($item['Quantity']) ? (int) $item['Quantity'] : null,
                unitOfMeasure: self::string($item, 'UnitOfMeasure'),
                amount: isset($item['Amount']) ? (float) $item['Amount'] : null,
                taxAmount: isset($item['TaxAmount']) ? (float) $item['TaxAmount'] : null
            );
        }
        return new Order(
            orderNumber: self::string($order, 'OrderNumber'),
            freightAmount: isset($order['FreightAmount']) ? (float) $order['FreightAmount'] : null,
            dutyAmount: isset($order['DutyAmount']) ? (float) $order['DutyAmount'] : null,
            items: $items
        );
    }

    /**
     * A value as a string, or null when it is missing or empty.
     *
     * @param array $data
     * @param string $key
     * @return string|null
     */
    private static function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
