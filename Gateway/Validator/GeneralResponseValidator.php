<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class GeneralResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var ErrorCodeProvider
     */
    private $errorCodeProvider;

    /**
     * Constructor
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     * @param ErrorCodeProvider $errorCodeProvider
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader,
        ErrorCodeProvider $errorCodeProvider
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->errorCodeProvider = $errorCodeProvider;
    }

    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        $response = $this->subjectReader->readResponseData($validationSubject);

        $isValid = true;

        $errorMessages = [];

        if (!empty($response['errorMessage'])) {
            $errorMessages[] = $response['errorMessage'];
            $isValid = false;
        }

        $errorCodes = $this->errorCodeProvider->getErrorCodes($response);

        if (!empty($errorCodes)) {
            $isValid = false;
        }
        return $this->createResult($isValid, $errorMessages, $errorCodes);
    }
}
