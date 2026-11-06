<?php
declare(strict_types=1);

namespace Payfurl\Payment\Test\Unit\Gateway\Request;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Payfurl\Payment\Gateway\Request\OrderDataBuilder;
use Payfurl\Payment\Gateway\Request\PaymentDataBuilder;
use Payfurl\Payment\Gateway\Request\RefundDataBuilder;
use Payfurl\Payment\Gateway\SubjectReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * What the module sends payFURL for an order: the charge (PaymentDataBuilder), its items (OrderDataBuilder) and a
 * refund (RefundDataBuilder).
 */
class RequestBuildersTest extends TestCase
{
    private array $additionalInformation = [];
    private array $items = [];
    private bool $pricesIncludeTax = false;

    public function testTheChargeIsForTheOrderTotalInItsCurrencyWithItsNumber(): void
    {
        $this->additionalInformation = ['payfurlToken' => 'tok_1', 'payfurlChargeId' => 'ch_1', 'paymentMethodId' => 'pm_1'];

        $request = (new PaymentDataBuilder(new SubjectReader()))->build($this->subject());

        $this->assertSame('130.50', $request[PaymentDataBuilder::AMOUNT]);
        $this->assertSame('NZD', $request[PaymentDataBuilder::CURRENCY]);
        // ChargeResponseValidator checks a server charge's reference against this.
        $this->assertSame('Order #000000123', $request[PaymentDataBuilder::REFERENCE]);
        // AbstractTransaction picks the store's API keys with it.
        $this->assertSame(3, $request[PaymentDataBuilder::STORE_ID]);
        $this->assertSame(['tok_1', 'ch_1', 'pm_1'], [$request['Token'], $request['ChargeId'], $request['PaymentMethodId']]);
        $this->assertFalse($request['payfurlSaveMyPayment']);
        // As the shopper entered it: no +61 is added.
        $this->assertSame('0412 345 678', $request['Phone']);
    }

    public function testItemsKeepTheirFullSkuAndName(): void
    {
        $this->items = [$this->item('configurable-hoodie-blue-xl', 'Configurable Hoodie, Blue, XL', 1, 59.90)];

        [$item] = $this->orderItems();

        $this->assertSame('configurable-hoodie-blue-xl', $item['ProductCode']);
        $this->assertSame('Configurable Hoodie, Blue, XL', $item['Description']);
        $this->assertSame([1, 59.90], [$item['Quantity'], $item['Amount']]);
    }

    public function testTaxThatDoesNotSplitEvenlyGoesOnOneExtraLineSoTheTotalMatches(): void
    {
        $this->items = [$this->item('tee', 'Tee', 3, 10.00, tax: 1.00)];

        $items = $this->orderItems();

        $this->assertSame([[2, 10.33], [1, 10.34]], array_map(fn($i) => [$i['Quantity'], round($i['Amount'], 2)], $items));
        $this->assertEqualsWithDelta(31.00, $this->total($items), 0.001);
    }

    public function testADiscountIsTakenOffEachUnit(): void
    {
        $this->items = [$this->item('tee', 'Tee', 2, 10.00, discount: 3.00)];

        $items = $this->orderItems();

        $this->assertSame([[2, 8.50]], array_map(fn($i) => [$i['Quantity'], round($i['Amount'], 2)], $items));
    }

    public function testPricesIncludingTaxAreSentAsTheyAre(): void
    {
        $this->pricesIncludeTax = true;
        $this->items = [$this->item('tee', 'Tee', 3, 10.00, tax: 1.00, priceInclTax: 11.00)];

        $items = $this->orderItems();

        $this->assertSame([[3, 11.00]], array_map(fn($i) => [$i['Quantity'], $i['Amount']], $items));
    }

    public function testARefundIsForTheCreditMemoOfTheCapturedChargeWithTheOrdersStoreKeys(): void
    {
        $request = (new RefundDataBuilder(new SubjectReader(), $this->createMock(LoggerInterface::class)))
            ->build($this->subject(creditMemoTotal: 25.5, parentTransactionId: 'ch_1-capture'));

        $this->assertSame(['ChargeId' => 'ch_1', 'Amount' => '25.50', 'StoreId' => 3], $request);
    }

    private function orderItems(): array
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->with('tax/calculation/price_includes_tax')->willReturn($this->pricesIncludeTax);

        return (new OrderDataBuilder(new SubjectReader(), $scopeConfig))->build($this->subject())['Order']['Items'];
    }

    private function total(array $items): float
    {
        return array_sum(array_map(fn($i) => $i['Quantity'] * $i['Amount'], $items));
    }

    private function item(string $sku, string $name, int $qty, float $price, float $tax = 0, float $discount = 0,
                          ?float $priceInclTax = null): Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn($name);
        $item->method('getQtyOrdered')->willReturn($qty);
        $item->method('getPrice')->willReturn($price);
        $item->method('getPriceInclTax')->willReturn($priceInclTax ?? $price);
        $item->method('getTaxAmount')->willReturn($tax);
        $item->method('getDiscountAmount')->willReturn($discount);
        return $item;
    }

    private function subject(?float $creditMemoTotal = null, string $parentTransactionId = ''): array
    {
        $order = $this->createMock(Order::class);
        $order->method('getGrandTotal')->willReturn(130.5);
        $order->method('getOrderCurrencyCode')->willReturn('NZD');
        $order->method('getIncrementId')->willReturn('000000123');
        $order->method('getStoreId')->willReturn(3);
        $order->method('getShippingAmount')->willReturn(5.0);
        $order->method('getAllVisibleItems')->willReturn($this->items);

        $payment = $this->createMock(Payment::class);
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getAdditionalInformation')
            ->willReturnCallback(fn($key) => $this->additionalInformation[$key] ?? null);
        $payment->method('getParentTransactionId')->willReturn($parentTransactionId);
        if ($creditMemoTotal !== null) {
            $creditMemo = $this->createMock(Creditmemo::class);
            $creditMemo->method('getGrandTotal')->willReturn($creditMemoTotal);
            $payment->method('getCreditmemo')->willReturn($creditMemo);
        }

        $billing = $this->createMock(AddressAdapterInterface::class);
        $billing->method('getTelephone')->willReturn('0412 345 678');
        $orderAdapter = $this->createMock(OrderAdapterInterface::class);
        $orderAdapter->method('getBillingAddress')->willReturn($billing);
        $orderAdapter->method('getStoreId')->willReturn(3);

        $paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDO->method('getPayment')->willReturn($payment);
        $paymentDO->method('getOrder')->willReturn($orderAdapter);

        return ['payment' => $paymentDO];
    }
}
