<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Request;

use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;

/**
 * Payment Data Builder
 */
class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * The billing amount of the request. This value must be greater than 0,
     * and must match the currency format of the merchant account.
     */
    const AMOUNT = 'Amount';

    const CURRENCY = 'Currency';

    const REFERENCE = "Reference";

    const CAPTURE = "Capture";

    const IP = "ip";

    const TAX_AMOUNT = "TaxAmount";

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param SubjectReader $subjectReader
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Payfurl\Payment\Gateway\Data\OrderAdapter $order */
        $order = $paymentDO->getOrder();

        $payment = $paymentDO->getPayment();

        $orderId = $order->getOrderIncrementId();
        $billingAddress = $order->getBillingAddress();
        $phone = $billingAddress ? preg_replace('/[^0-9+]/', '', $billingAddress->getTelephone()) : '';

        $result = [
            self::AMOUNT => $this->formatPrice($order->getOrderGrandTotal()),
            self::CURRENCY => $order->getOrderCurrentCode(),
            self::CAPTURE => true,
            'Reference' => 'Order #'.$orderId,
            'Email' => $order->getCustomerEmail(),
            'Phone' => $phone,
            'FirstName' => $order->getCustomerFirstname(),
            'LastName' => $order->getCustomerLastname(),
        ];

        /*Token is used to charge*/
        if ($payment->getAdditionalInformation('payfurlToken')) {
            $result["Token"] = $payment->getAdditionalInformation('payfurlToken');
        }

        /*Paypal - transaction has been created by js skd*/
        if ($payment->getAdditionalInformation('payfurlChargeId')) {
            $result["ChargeId"] = $payment->getAdditionalInformation('payfurlChargeId');
        }

        if ($payment->getAdditionalInformation('paymentMethodId')) {
            $result["PaymentMethodId"] = $payment->getAdditionalInformation('paymentMethodId');
        }

        $result["payfurlSaveMyPayment"] = (bool)$payment->getAdditionalInformation('payfurlSaveMyPayment');

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $street = $billingAddress->getStreet();
            $result['Address'] = [
                'Line1' => $street[0],
                'City' => $billingAddress->getCity(),
                'Country' => $billingAddress->getCountryId(),
                'PostalCode' => $billingAddress->getPostcode(),
                'State' => $billingAddress->getRegionCode(),
            ];
        }

        $result['Order'] = [
            'OrderNumber' => $orderId,
            'FreightAmount' => $order->getShippingAmount(),
            'Items' => [],
        ];
        foreach ($order->getItems() as $item) {
            // magento bug???
            if (!$item->getPrice() || !$item->getQtyOrdered()) {
                continue;
            }

            $result['Order']['Items'][] = [
                'ProductCode' => $item->getSku(),
                'Description' => $item->getName(),
                'Quantity' => $item->getQtyOrdered(),
                'Amount' => $item->getPrice(),
                'TaxAmount' => $item->getTaxAmount(),
            ];
        }

        return $result;
    }
}
