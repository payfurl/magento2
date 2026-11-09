<?php
declare(strict_types=1);

namespace Payfurl\Payment\Test\Unit\Model\Adapter;

use payFURL\Sdk\ClientOptions;
use payFURL\Sdk\Http\FakeTransport;
use Payfurl\Payment\Model\Adapter\PayfurlAdapter;
use Payfurl\Payment\Model\Config;
use PHPUnit\Framework\TestCase;

/**
 * The adapter on payfurl/phpsdk 3's fake transport: the requests it sends with a store's keys, and what it returns.
 */
class PayfurlAdapterTest extends TestCase
{
    private const ORDER = [
        'Amount' => '33.00',
        'Currency' => 'AUD',
        'Capture' => true,
        'Reference' => 'Order #000000042',
        'Email' => 'jo@example.com',
        'FirstName' => 'Jo',
        'LastName' => 'Citizen',
        'Phone' => '0412345678',
        'Address' => ['Line1' => '1 George Street', 'Line2' => null, 'City' => 'Sydney', 'Country' => 'AU',
            'PostalCode' => '2000', 'State' => 'NSW'],
        'Order' => ['OrderNumber' => '000000042', 'FreightAmount' => '5.0000', 'DutyAmount' => 0,
            'Items' => [['ProductCode' => 'tee', 'CommodityCode' => null, 'Description' => 'Tee', 'Quantity' => '2.0000',
                'UnitOfMeasure' => null, 'Amount' => 14.0]]],
        'loginCustomer' => false,
    ];

    private FakeTransport $transport;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
    }

    public function testChargeByTokenUsesTheStoresKeysAndSendsTheOrder(): void
    {
        $this->transport->queue(201, ['chargeId' => 'ch_1', 'status' => 'SUCCESS', 'amount' => 33]);

        $result = $this->adapter(storeId: 2)->chargeByToken(self::ORDER + ['Token' => 'tok_1']);

        $this->assertSame(['chargeId' => 'ch_1', 'status' => 'SUCCESS', 'amount' => 33], $result);
        $request = $this->transport->lastRequest();
        $this->assertSame('https://sandbox-api-au.payfurl.com/charge/token', $request->url);
        $this->assertSame('sectest-store2-au', $request->headers['x-secretkey']);
        $this->assertEquals([
            'amount' => 33.0,
            'token' => 'tok_1',
            'currency' => 'AUD',
            'reference' => 'Order #000000042',
            'capture' => true,
            'email' => 'jo@example.com',
            'phone' => '0412345678',
            'firstName' => 'Jo',
            'lastName' => 'Citizen',
            'address' => ['line1' => '1 George Street', 'city' => 'Sydney', 'country' => 'AU', 'postalCode' => '2000',
                'state' => 'NSW'],
            'order' => ['orderNumber' => '000000042', 'freightAmount' => 5.0, 'dutyAmount' => 0.0,
                'items' => [['productCode' => 'tee', 'description' => 'Tee', 'quantity' => 2, 'amount' => 14.0]]],
        ], $this->transport->lastBody());
    }

    public function testProductionStoreUsesTheProductionHost(): void
    {
        $this->transport->queue(200, ['chargeId' => 'ch_1']);

        $this->adapter(storeId: 3, env: Config::ENV_LIVE)->getTransactionInfo(['ChargeId' => 'ch_1']);

        $this->assertSame('https://api-au.payfurl.com/charge/ch_1', $this->transport->lastRequest()->url);
        $this->assertSame('seclive-store3-au', $this->transport->lastRequest()->headers['x-secretkey']);
    }

    public function testApiErrorBecomesTheErrorArray(): void
    {
        $this->transport->queue(400, ['message' => 'Card declined', 'code' => 103]);

        $result = $this->adapter()->chargeByToken(self::ORDER + ['Token' => 'tok_1']);

        $this->assertSame(['errorMessage' => 'Card declined', 'errorCode' => 103], $result);
    }

    public function testNetworkErrorBecomesTheErrorArray(): void
    {
        $this->transport->queueFailure(timedOut: true);

        $result = $this->adapter()->chargeByToken(self::ORDER + ['Token' => 'tok_1']);

        $this->assertSame(0, $result['errorCode']);
        $this->assertSame('Operation timed out', $result['errorMessage']);
    }

    public function testStoreWithoutKeysReturnsAnErrorInsteadOfThrowing(): void
    {
        $result = $this->adapter(secretKey: '')->getProvidersInfo(['amount' => 10, 'currency' => 'AUD']);

        $this->assertSame(0, $result['errorCode']);
        $this->assertSame([], $this->transport->requests);
    }

    public function testRefundInFullAndInPart(): void
    {
        $this->transport->queue(200, ['status' => 'REFUND'])->queue(200, ['status' => 'PARTIAL_REFUND']);
        $adapter = $this->adapter();

        $adapter->refund(['ChargeId' => 'ch_1', 'Amount' => 0]);
        $this->assertSame('https://sandbox-api-au.payfurl.com/charge/ch_1', $this->transport->lastRequest()->url);

        $adapter->refund(['ChargeId' => 'ch_1', 'Amount' => '5.50']);
        $this->assertSame('https://sandbox-api-au.payfurl.com/charge/ch_1?amount=5.5', $this->transport->lastRequest()->url);
        $this->assertSame('DELETE', $this->transport->lastRequest()->method);
    }

    public function testCustomerPaymentMethodsAreArrays(): void
    {
        $this->transport->queue(200, [['paymentMethodId' => 'pm_1', 'card' => ['cardNumber' => '4111****1111']]]);

        $methods = $this->adapter()->getCustomerPaymentMethods(['CustomerId' => 'cus_1']);

        $this->assertSame([['paymentMethodId' => 'pm_1', 'card' => ['cardNumber' => '4111****1111']]], $methods);
        $this->assertSame('https://sandbox-api-au.payfurl.com/customer/cus_1/payment_method', $this->transport->lastRequest()->url);
    }

    public function testChargeByPaymentMethod(): void
    {
        $this->transport->queue(201, ['chargeId' => 'ch_1']);

        $this->adapter()->chargeByPaymentMethod(self::ORDER + ['PaymentMethodId' => 'pm_1']);

        $this->assertSame('https://sandbox-api-au.payfurl.com/charge/payment_method', $this->transport->lastRequest()->url);
        $this->assertSame('pm_1', $this->transport->lastBody()['paymentMethodId']);
    }

    public function testLoggedInCustomerWithoutSavingIsAGuestTokenCharge(): void
    {
        $this->transport->queue(201, ['chargeId' => 'ch_1']);

        $this->adapter()->chargeByCustomerToken(self::ORDER + ['Token' => 'tok_1'], 'cus_1');

        $this->assertCount(1, $this->transport->requests);
        $this->assertSame('https://sandbox-api-au.payfurl.com/charge/token', $this->transport->lastRequest()->url);
    }

    public function testFirstSavedCardCreatesTheCustomerThenChargesIt(): void
    {
        $this->transport->queue(201, ['customerId' => 'cus_new'])->queue(201, ['chargeId' => 'ch_1', 'customerId' => 'cus_new']);

        $result = $this->adapter()->chargeByCustomerToken(self::ORDER + ['Token' => 'tok_1', 'payfurlSaveMyPayment' => true], '');

        $this->assertSame('cus_new', $result['customerId']);
        [$customer, $charge] = $this->transport->requests;
        $this->assertSame('https://sandbox-api-au.payfurl.com/customer/token', $customer->url);
        $customerBody = json_decode((string) $customer->body, true);
        $this->assertSame('tok_1', $customerBody['token']);
        $this->assertSame('Order #000000042', $customerBody['reference']);
        $this->assertSame('https://sandbox-api-au.payfurl.com/charge/customer', $charge->url);
        $this->assertSame('cus_new', json_decode((string) $charge->body, true)['customerId']);
    }

    public function testNextSavedCardIsAddedToTheCustomerThenCharged(): void
    {
        $this->transport->queue(201, ['paymentMethodId' => 'pm_new'])->queue(201, ['chargeId' => 'ch_1']);

        $this->adapter()->chargeByCustomerToken(self::ORDER + ['Token' => 'tok_1', 'payfurlSaveMyPayment' => true], 'cus_1');

        [$paymentMethod, $charge] = $this->transport->requests;
        $this->assertSame('https://sandbox-api-au.payfurl.com/customer/cus_1/payment_method/token', $paymentMethod->url);
        $this->assertSame('https://sandbox-api-au.payfurl.com/charge/payment_method', $charge->url);
        $this->assertSame('pm_new', json_decode((string) $charge->body, true)['paymentMethodId']);
    }

    public function testSavedCardWithoutAPaymentMethodIsAnError(): void
    {
        $this->transport->queue(201, []);

        $result = $this->adapter()->chargeByCustomerToken(self::ORDER + ['Token' => 'tok_1', 'payfurlSaveMyPayment' => true], 'cus_1');

        $this->assertSame(['errorMessage' => 'The card could not be saved.', 'errorCode' => 0], $result);
        $this->assertCount(1, $this->transport->requests);
    }

    public function testProvidersInfoSendsTheBrowserSdkVersion(): void
    {
        $this->transport->queue(200, ['hasCardProviders' => true, 'hasPaypalProviders' => true]);

        $result = $this->adapter()->getProvidersInfo(['amount' => '33.0000', 'currency' => 'AUD']);

        $this->assertTrue($result['hasPaypalProviders']);
        $request = $this->transport->lastRequest();
        $this->assertSame('https://sandbox-api-au.payfurl.com/info/providers?amount=33&currency=AUD', $request->url);
        $this->assertSame(PayfurlAdapter::BROWSER_SDK_VERSION, $request->headers['sdk-version']);
    }

    public function testBrowserSdkVersionMatchesTheCheckoutsScript(): void
    {
        $requirejs = (string) file_get_contents(__DIR__ . '/../../../../view/frontend/requirejs-config.js');

        $this->assertStringContainsString('assets.payfurl.com/v' . PayfurlAdapter::BROWSER_SDK_VERSION . '/js/payfurl.js', $requirejs);
    }

    public function testKeysWorkChecksBothKeys(): void
    {
        $this->transport->queue(200, ['hasCardProviders' => true])->queue(200, ['hasCardProviders' => false]);

        $this->assertTrue(PayfurlAdapter::keysWork('pubtest-au', 'sectest-au', 'sandbox', $this->options()));

        [$secret, $public] = $this->transport->requests;
        $this->assertSame('sectest-au', $secret->headers['x-secretkey']);
        $this->assertSame('pubtest-au', $public->headers['x-publickey']);
        $this->assertArrayNotHasKey('x-secretkey', $public->headers);
    }

    public function testKeysWorkFailsOnARefusedKey(): void
    {
        $this->transport->queue(200, ['hasCardProviders' => true])->queue(401, '');

        $this->assertFalse(PayfurlAdapter::keysWork('pubtest-au', 'sectest-au', 'sandbox', $this->options()));
    }

    public function testKeysWorkFailsOnAnEmptyPublicKey(): void
    {
        $this->transport->queue(200, ['hasCardProviders' => true]);

        $this->assertFalse(PayfurlAdapter::keysWork('', 'sectest-au', 'sandbox', $this->options()));
        $this->assertCount(1, $this->transport->requests);
    }

    private function adapter(int $storeId = 1, string $env = Config::ENV_SANDBOX, ?string $secretKey = null): PayfurlAdapter
    {
        $config = $this->createMock(Config::class);
        $config->method('getEnv')->with($storeId)->willReturn($env);
        $config->method('getSecretKey')->with($storeId)
            ->willReturn($secretKey ?? ($env === Config::ENV_LIVE ? 'seclive' : 'sectest') . "-store$storeId-au");

        return new class ($config, $storeId, $this->options()) extends PayfurlAdapter {
            public function __construct(Config $config, $storeId, private readonly ClientOptions $options)
            {
                parent::__construct($config, $storeId);
            }

            protected function clientOptions(): ?ClientOptions
            {
                return $this->options;
            }
        };
    }

    private function options(): ClientOptions
    {
        return new ClientOptions(maxRetries: 0, transport: $this->transport);
    }
}
