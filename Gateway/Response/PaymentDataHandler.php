<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Response;

use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class PaymentDataHandler
 */
class PaymentDataHandler extends AdditionalDataHandler
{
    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setCcTransId($transaction['chargeId']);
        $payment->setLastTransId($transaction['chargeId']);

        $additionalData = $this->getAdditionalDataByType($transaction);

        if (!empty($additionalData) && is_array($additionalData)) {
            foreach ($additionalData as $key => $value) {
                $payment->setAdditionalInformation($key, $value);
            }
        }

    }
}
