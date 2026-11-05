<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Payfurl\Payment\Model\Adapter\PayfurlAdapter;

/**
 * Class TransactionRefund
 */
class TransactionRefund extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data, PayfurlAdapter $adapter)
    {
        if (empty($data["ChargeId"])) {
            throw new LocalizedException(__('The payFURL charge to refund is unknown.'));
        }

        return $adapter->refund($data);
    }
}
