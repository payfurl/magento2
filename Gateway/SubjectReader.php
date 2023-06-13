<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper;

/**
 * Class SubjectReader
 */
class SubjectReader
{
    /**
     * Reads response object from subject
     *
     * @param array $subject
     * @return object
     */
    public function readResponseData(array $subject)
    {
        $response = Helper\SubjectReader::readResponse($subject);
        if (!isset($response['object'])) {
            throw new \InvalidArgumentException('Response object does not exist');
        }

        return $response['object'];
    }

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject)
    {
        return Helper\SubjectReader::readPayment($subject);
    }

    /**
     * Reads transaction from the subject.
     *
     * @param array $subject
     * @return Transaction
     * @throws \InvalidArgumentException if the subject doesn't contain transaction details.
     */
    public function readTransaction(array $response)
    {
        if (!isset($response['object'])) {
            throw new \InvalidArgumentException('Response object does not exist.');
        }

        return $response['object'];
    }

    /**
     * Reads amount from subject
     *
     * @param array $subject
     * @return mixed
     */
    public function readAmount(array $subject)
    {
        return Helper\SubjectReader::readAmount($subject);
    }
}
