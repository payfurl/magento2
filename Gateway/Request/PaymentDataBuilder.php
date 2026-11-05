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
 * Payment Data Builder. Order items come from OrderDataBuilder.
 */
class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * The billing amount of the request. This value must be greater than 0,
     * and must match the currency format of the merchant account.
     */
    public const AMOUNT = 'Amount';

    public const CURRENCY = 'Currency';

    public const REFERENCE = "Reference";

    public const CAPTURE = "Capture";

    /** Read by AbstractTransaction to pick the store's keys; never sent to payFURL. */
    public const STORE_ID = 'StoreId';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param SubjectReader $subjectReader
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
        $payment = $paymentDO->getPayment();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        // Charged in the order (display) currency; ChargeResponseValidator checks the charge against the same values.
        $result = [
            self::AMOUNT => $this->formatPrice($order->getGrandTotal()),
            self::CURRENCY => $order->getOrderCurrencyCode(),
            self::CAPTURE => true,
            self::REFERENCE => 'Order #' . $order->getIncrementId(),
            self::STORE_ID => $order->getStoreId(),
            'Email' => $order->getCustomerEmail(),
            'FirstName' => $order->getCustomerFirstname(),
            'LastName' => $order->getCustomerLastname(),
        ];

        /*Token is used to charge*/
        if ($payment->getAdditionalInformation('payfurlToken')) {
            $result["Token"] = $payment->getAdditionalInformation('payfurlToken');
        }

        /*Paypal, wallets, checkout methods - the charge was created by the browser SDK*/
        if ($payment->getAdditionalInformation('payfurlChargeId')) {
            $result["ChargeId"] = $payment->getAdditionalInformation('payfurlChargeId');
        }

        if ($payment->getAdditionalInformation('paymentMethodId')) {
            $result["PaymentMethodId"] = $payment->getAdditionalInformation('paymentMethodId');
        }

        $result["payfurlSaveMyPayment"] = (bool)$payment->getAdditionalInformation('payfurlSaveMyPayment');

        $billingAddress = $paymentDO->getOrder()->getBillingAddress();
        if ($billingAddress) {
            $result['Phone'] = $billingAddress->getTelephone();
            $result['Address'] = [
                'Line1' => $billingAddress->getStreetLine1(),
                'Line2' => $billingAddress->getStreetLine2(),
                'City' => $billingAddress->getCity(),
                'Country' => $billingAddress->getCountryId(),
                'PostalCode' => $billingAddress->getPostcode(),
                'State' => $billingAddress->getRegionCode(),
            ];
        }

        return $result;
    }
}
