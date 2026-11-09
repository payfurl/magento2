<?php
declare(strict_types=1);

namespace Payfurl\Payment\Test\Unit\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Payfurl\Payment\Gateway\Http\Client\TransactionCapture;
use Payfurl\Payment\Model\Adapter\PayfurlAdapter;
use Payfurl\Payment\Model\Adapter\PayfurlAdapterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TransactionCaptureTest extends TestCase
{
    private PayfurlAdapter&MockObject $adapter;
    private ?int $adapterStoreId = null;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(PayfurlAdapter::class);
        $this->adapter->method('getCustomerPaymentMethods')
            ->with(['CustomerId' => 'cus_mine'])
            ->willReturn([['paymentMethodId' => 'pm_mine']]);
    }

    public function testChargesASavedCardOfTheLoggedInCustomer(): void
    {
        $this->adapter->expects($this->once())->method('chargeByPaymentMethod')->willReturn(['chargeId' => 'ch_1']);

        $response = $this->capture(['PaymentMethodId' => 'pm_mine', 'loginCustomer' => true, 'customerPayfurlId' => 'cus_mine']);

        $this->assertSame(['chargeId' => 'ch_1'], $response['object']);
    }

    public function testRefusesSomeoneElsesSavedCard(): void
    {
        $this->adapter->expects($this->never())->method('chargeByPaymentMethod');
        $this->expectException(ClientException::class);

        $this->capture(['PaymentMethodId' => 'pm_theirs', 'loginCustomer' => true, 'customerPayfurlId' => 'cus_mine']);
    }

    public function testRefusesASavedCardForAGuest(): void
    {
        $this->adapter->expects($this->never())->method('chargeByPaymentMethod');
        $this->expectException(ClientException::class);

        $this->capture(['PaymentMethodId' => 'pm_mine', 'loginCustomer' => false]);
    }

    public function testFetchesABrowserChargeAndUsesTheOrdersStoreKeys(): void
    {
        $this->adapter->expects($this->once())->method('getTransactionInfo')
            ->with(['ChargeId' => 'ch_browser'])
            ->willReturn(['chargeId' => 'ch_browser']);

        $this->capture(['ChargeId' => 'ch_browser', 'StoreId' => 3]);

        $this->assertSame(3, $this->adapterStoreId);
    }

    public function testFailsWithoutPaymentDetails(): void
    {
        $this->expectException(ClientException::class);

        $this->capture(['Amount' => '10.00']);
    }

    private function capture(array $request): array
    {
        $factory = $this->createMock(PayfurlAdapterFactory::class);
        $factory->method('create')->willReturnCallback(function (array $args) {
            $this->adapterStoreId = $args['storeId'];
            return $this->adapter;
        });
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn($request);

        $client = new TransactionCapture(
            $this->createMock(LoggerInterface::class),
            $this->createMock(Logger::class),
            $factory
        );

        return $client->placeRequest($transfer);
    }
}
