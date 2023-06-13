<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class CustomerDataBuilder
 */
class CustomerDataBuilder implements BuilderInterface
{
    /**
     * Customer block name
     */
    const CUSTOMER = 'CustomerCode';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    private $customerRepository;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader, CustomerRepositoryInterface $customerRepository)
    {
        $this->subjectReader = $subjectReader;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        $customerId = $order->getCustomerId();
        $isLoginCustomer = (bool)$customerId;

        $returnData = [
            'loginCustomer' => $isLoginCustomer,
            'Email' => $order->getCustomerEmail()
        ];

        if ($isLoginCustomer) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $customerPayfurlId = $customer->getCustomAttribute('payfurl_payment_id') ?
                    $customer->getCustomAttribute('payfurl_payment_id')->getValue() : '';

                $returnData['customerPayfurlId'] = $customerPayfurlId;
                $returnData['FirstName'] = $customer->getFirstname();
                $returnData['LastName'] = $customer->getLastname();
                $returnData['Email'] = $customer->getEmail();
                $returnData['SetDefault'] = false;
            } catch (\Exception $e) {
                // cannot get customer
            }
        }

        return $returnData;
    }
}
