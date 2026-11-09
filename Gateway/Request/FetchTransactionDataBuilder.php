<?php
declare(strict_types=1);

namespace Payfurl\Payment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Payfurl\Payment\Gateway\SubjectReader;

class FetchTransactionDataBuilder implements BuilderInterface
{
    public function __construct(private readonly SubjectReader $subjectReader)
    {
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $order = $this->subjectReader->readPayment($buildSubject)->getOrder();

        return [
            'ChargeId' => $buildSubject['transactionId'] ?? null,
            'StoreId' => $order->getStoreId(),
        ];
    }
}
