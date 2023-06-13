<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Response;

use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerDataHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    private $customerRepository;

    /**
     * TransactionDataHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->subjectReader = $subjectReader;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        if ($paymentDO->getPayment() instanceof Payment) {

            $transaction = $this->subjectReader->readTransaction($response);

            // set customerId from PayFURL
            $order = $paymentDO->getOrder();

            $customerId = $order->getCustomerId();

            $isLoginCustomer = (bool)$customerId;
            if ($isLoginCustomer) {
                try {
                    $customer = $this->customerRepository->getById($customerId);
                    $customerPayfurlId = $customer->getCustomAttribute('payfurl_payment_id') ?
                        $customer->getCustomAttribute('payfurl_payment_id')->getValue() : '';
                    if (!$customerPayfurlId && isset($transaction['customerId'])) {
                        $customer->setCustomAttribute('payfurl_payment_id', $transaction['customerId']);
                        $this->customerRepository->save($customer);
                    }
                } catch (\Exception $e) {
                    // cannot get customer
                }
            }
        }
    }
}
