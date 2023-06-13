<?php

namespace Payfurl\Payment\Model;

/**
 * Pay In Store payment method model
 */
class Payfurl extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'payfurl';
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

}
