<?php
declare(strict_types=1);

namespace Payfurl\Payment\Test\Unit\Gateway\Response;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Payfurl\Payment\Controller\Webhook\Index as Webhook;
use Payfurl\Payment\Gateway\Response\FetchTransactionHandler;
use Payfurl\Payment\Gateway\Response\PaymentDataHandler;
use Payfurl\Payment\Gateway\SubjectReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A pending charge leaves the invoice unpaid (Payment Review) until the fetched status approves or denies it.
 */
class PaymentStatusHandlersTest extends TestCase
{
    public static function statuses(): array
    {
        // status => [pending at capture, approved on update, denied on update]
        return [
            'SUCCESS' => ['SUCCESS', false, true, false],
            'PENDING' => ['PENDING', true, false, false],
            'PENDING_CHECKOUT' => ['PENDING_CHECKOUT', true, false, false],
            'FAILED' => ['FAILED', false, false, true],
            'AUTHORISE_CANCELLED' => ['AUTHORISE_CANCELLED', false, false, true],
        ];
    }

    #[DataProvider('statuses')]
    public function testStatus(string $status, bool $pending, bool $approved, bool $denied): void
    {
        $response = ['object' => ['chargeId' => 'ch_1', 'status' => $status]];

        $orderPayment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()
            ->onlyMethods(['setCcTransId', 'setLastTransId', 'setAdditionalInformation'])->getMock();
        (new PaymentDataHandler(new SubjectReader()))->handle(['payment' => $this->paymentDO($orderPayment)], $response);
        $this->assertSame($pending, (bool)$orderPayment->getIsTransactionPending());

        $payment = new DataObject();
        (new FetchTransactionHandler(new SubjectReader()))->handle(['payment' => $this->paymentDO($payment)], $response);
        $this->assertSame($approved, (bool)$payment->getIsTransactionApproved());
        $this->assertSame($denied, (bool)$payment->getIsTransactionDenied());
    }

    public function testWebhookSignatureMatchesWebhooksSender(): void
    {
        $body = '{"meta":{"type":"transaction"},"data":{"chargeId":"ch_1"}}';
        $signature = base64_encode(hash_hmac('sha256', $body, 'key', true));

        $this->assertTrue(Webhook::isSignedBy($body, $signature, 'key'));
        $this->assertFalse(Webhook::isSignedBy($body, $signature, 'other key'));
        $this->assertFalse(Webhook::isSignedBy($body . ' ', $signature, 'key'));
        $this->assertFalse(Webhook::isSignedBy($body, '', 'key'));
    }

    private function paymentDO(object $payment): PaymentDataObjectInterface
    {
        $paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDO->method('getPayment')->willReturn($payment);

        return $paymentDO;
    }
}
