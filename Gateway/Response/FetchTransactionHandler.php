<?php
declare(strict_types=1);

namespace Payfurl\Payment\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Payfurl\Payment\Gateway\SubjectReader;
use Payfurl\Payment\Gateway\Validator\ChargeResponseValidator;

/**
 * Settles an order held in Payment Review from the charge's current status (Order\Payment::update()).
 */
class FetchTransactionHandler implements HandlerInterface
{
    private const DENIED_STATUSES = ['FAILED', 'AUTHORISE_CANCELLED'];

    public function __construct(private readonly SubjectReader $subjectReader)
    {
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $this->subjectReader->readPayment($handlingSubject)->getPayment();
        $status = $this->subjectReader->readTransaction($response)['status'] ?? '';

        if ($status === ChargeResponseValidator::STATUS_SUCCESS) {
            $payment->setIsTransactionApproved(true);
        } elseif (in_array($status, self::DENIED_STATUSES, true)) {
            $payment->setIsTransactionDenied(true);
        }
    }
}
