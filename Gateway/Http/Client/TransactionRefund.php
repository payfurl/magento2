<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Http\Client;

/**
 * Class TransactionCapture
 */
class TransactionRefund extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        /*get checkout only*/
        if ($data["ChargeId"]) {
            $result = $this->adapterFactory->create()->refund($data);
        }
        return $result;
    }
}
