<?php
declare(strict_types=1);

namespace Payfurl\Payment\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Payfurl\Payment\Model\Adapter\PayfurlAdapter;

/**
 * Reads a charge's current status for "Get Payment Update" and payFURL webhooks.
 */
class TransactionFetch extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data, PayfurlAdapter $adapter)
    {
        if (empty($data['ChargeId'])) {
            throw new LocalizedException(__('The payFURL charge is unknown.'));
        }

        return $adapter->getTransactionInfo(['ChargeId' => $data['ChargeId']]);
    }
}
