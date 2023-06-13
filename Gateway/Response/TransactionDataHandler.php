<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Response;

use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Customer\Api\CustomerRepositoryInterface;

class TransactionDataHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * TransactionDataHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        if ($paymentDO->getPayment() instanceof Payment) {

            $transaction = $this->subjectReader->readTransaction($response);

            /** @var Payment $orderPayment */
            $orderPayment = $paymentDO->getPayment();
            $this->setTransactionId(
                $orderPayment,
                $transaction
            );

            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction());
            $closed = $this->shouldCloseParentTransaction($orderPayment);
            $orderPayment->setShouldCloseParentTransaction($closed);
        }
    }

    /**
     * @param Payment $orderPayment
     * @param $transaction
     * @return void
     */
    protected function setTransactionId(Payment $orderPayment, $transaction)
    {
        $orderPayment->setTransactionId($transaction["chargeId"]);
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return false;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment)
    {
        return false;
    }
}
