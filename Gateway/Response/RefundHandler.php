<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Response;

use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class RefundHandler extends TransactionDataHandler
{
       /**
     * @param Payment $orderPayment
     * @param $transaction
     * @return void
     */
    protected function setTransactionId(Payment $orderPayment, $transaction)
    {
        $orderPayment->setTransactionId($transaction["chargeId"]."-".time()."-Refund");
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return true;
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
        return !(bool)$orderPayment->getCreditmemo()->getInvoice()->canRefund();
    }
}
