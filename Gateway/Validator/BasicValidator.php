<?php

namespace Payfurl\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\ResultInterface;

class BasicValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{

    /**
     * Performs domain-related validation for business object
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        return $this->createResult(true, $validationSubject);
    }
}
