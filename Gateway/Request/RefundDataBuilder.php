<?php
namespace Payfurl\Payment\Gateway\Request;

use InvalidArgumentException;
use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;

class RefundDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param LoggerInterface $logger
     */
    public function __construct(
        SubjectReader $subjectReader,
        LoggerInterface $logger
    ) {
        $this->subjectReader = $subjectReader;
        $this->logger = $logger;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $amount = null;

        if (!empty($payment->getCreditmemo()) && !empty($payment->getCreditmemo()->getGrandTotal())) {
            $amount = $payment->getCreditmemo()->getGrandTotal();
        }

        try {
            $amount = $this->formatPrice($amount);
        } catch (InvalidArgumentException $e) {
            $this->logger->critical($e->getMessage());
        }

        /**
         * we should remember that Payment sets Capture txn id of current Invoice into ParentTransactionId Field
         * We should also support previous implementations of Magento -
         * and cut off '-capture' postfix from transaction ID to support backward compatibility
         */
        $txnId = str_replace(
            '-' . TransactionInterface::TYPE_CAPTURE,
            '',
            $payment->getParentTransactionId()
        );

        return [
            'ChargeId' => $txnId,
            PaymentDataBuilder::AMOUNT => $amount
        ];
    }
}
