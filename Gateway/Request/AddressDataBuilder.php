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
class AddressDataBuilder implements BuilderInterface
{
    use Formatter;

    const INDEX = "Address";
    const LINE1 = 'Line1';

    const LINE2 = 'Line2';

    const CITY = "City";

    const COUNTRY = "Country";

    const POSTAL_CODE = "PostalCode";

    const STATE = "State";
    const PHONE = "Phone";

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
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $shippingAddress = $order->getShippingAddress();
        return [
            self::INDEX => 1,
            self::LINE1 => $shippingAddress->getStreetLine1(),
            self::LINE2 => $shippingAddress->getStreetLine2(),
            self::CITY => $shippingAddress->getCity(),
            self::COUNTRY => $shippingAddress->getCountryId(),
            self::POSTAL_CODE => $shippingAddress->getPostcode(),
            self::STATE => $shippingAddress->getRegionCode(),
            self::PHONE => $shippingAddress->getTelephone()
        ];
    }
}
