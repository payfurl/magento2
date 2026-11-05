<?php
declare(strict_types=1);

namespace Payfurl\Payment\Test\Unit\Gateway\Validator;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Validator\Result;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Payfurl\Payment\Gateway\SubjectReader;
use Payfurl\Payment\Gateway\Validator\ChargeResponseValidator;
use Payfurl\Payment\Gateway\Validator\ErrorCodeProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A charge only pays an order when payFURL says it succeeded (or is still pending, which Magento leaves unpaid in
 * Payment Review) for this order's amount, currency and reference, and no other order holds it.
 */
class ChargeResponseValidatorTest extends TestCase
{
    private const ORDER_ID = '42';

    /** @var DataObject[] transactions already stored with the charge's txn_id */
    private array $storedTransactions = [];

    public static function charges(): array
    {
        return [
            'server charge that succeeded' => [[], null],
            'browser charge with no reference' => [['reference' => null], null],
            'pending PayID charge' => [['status' => 'PENDING'], null],
            'pending checkout charge' => [['status' => 'PENDING_CHECKOUT'], null],
            'failed' => [['status' => 'FAILED'], 'charge_status_failed'],
            'authorised, not captured' => [['status' => 'AUTHORISE'], 'charge_status_authorise'],
            'cancelled' => [['status' => 'AUTHORISE_CANCELLED'], 'charge_status_authorise_cancelled'],
            'refunded' => [['status' => 'REFUND'], 'charge_status_refund'],
            'no status' => [['status' => null], 'charge_status_unknown'],
            'cheaper' => [['amount' => 1.00], 'charge_amount_mismatch'],
            'a cent short' => [['amount' => 99.99], 'charge_amount_mismatch'],
            'amount as a string' => [['amount' => '100.00'], null],
            'other currency' => [['currency' => 'NZD'], 'charge_currency_mismatch'],
            'currency in lower case' => [['currency' => 'aud'], null],
            'another order\'s charge' => [['reference' => 'Order #000000099'], 'charge_reference_mismatch'],
            'no charge id' => [['chargeId' => null], 'charge_missing'],
        ];
    }

    #[DataProvider('charges')]
    public function testCharge(array $override, ?string $expectedError): void
    {
        $result = $this->validate(array_merge($this->charge(), $override));

        $this->assertSame($expectedError === null, $result->isValid());
        $this->assertSame($expectedError === null ? [] : [$expectedError], $result->getErrorCodes());
    }

    public function testAnErrorFromTheApiFails(): void
    {
        $result = $this->validate(['errorMessage' => 'Declined', 'errorCode' => 51]);

        $this->assertFalse($result->isValid());
        $this->assertSame([51], $result->getErrorCodes());
    }

    public function testAChargeAnotherOrderHoldsFails(): void
    {
        $this->storedTransactions = [new DataObject(['order_id' => '7'])];

        $result = $this->validate($this->charge(), null);

        $this->assertFalse($result->isValid());
        $this->assertSame(['charge_already_used'], $result->getErrorCodes());
    }

    public function testAChargeThisOrderHoldsPasses(): void
    {
        $this->storedTransactions = [new DataObject(['order_id' => self::ORDER_ID])];

        $this->assertTrue($this->validate($this->charge(), self::ORDER_ID)->isValid());
    }

    private function charge(): array
    {
        return [
            'chargeId' => 'ch_1',
            'status' => 'SUCCESS',
            'amount' => 100,
            'currency' => 'AUD',
            'reference' => 'Order #000000123',
        ];
    }

    /**
     * @param array $charge payFURL's charge response
     * @param string|null $orderId null while the order is being placed
     */
    private function validate(array $charge, ?string $orderId = null): Result
    {
        $order = new DataObject([
            'entity_id' => $orderId,
            'increment_id' => '000000123',
            'grand_total' => 100.0,
            'order_currency_code' => 'AUD',
        ]);
        $paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDO->method('getPayment')->willReturn(new DataObject(['order' => $order]));

        $resultFactory = $this->createMock(ResultInterfaceFactory::class);
        $resultFactory->method('create')->willReturnCallback(
            fn(array $args) => new Result($args['isValid'], $args['failsDescription'], $args['errorCodes'])
        );

        $searchResult = $this->createMock(TransactionSearchResultInterface::class);
        $searchResult->method('getItems')->willReturnCallback(fn() => $this->storedTransactions);
        $repository = $this->createMock(TransactionRepositoryInterface::class);
        $repository->method('getList')->willReturn($searchResult);
        $criteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $criteriaBuilder->method('addFilter')->with('txn_id', $charge['chargeId'] ?? '')->willReturnSelf();
        $criteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        $validator = new ChargeResponseValidator(
            $resultFactory,
            new SubjectReader(),
            new ErrorCodeProvider(),
            $repository,
            $criteriaBuilder
        );

        return $validator->validate(['response' => ['object' => $charge], 'payment' => $paymentDO]);
    }
}
