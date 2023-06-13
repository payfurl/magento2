<?php

namespace Payfurl\Payment\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class PayfurlLayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['renders']['children'] as $key => $value) {
            if ($key === 'payfurl') continue;
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['renders']['children'][$key]);
        }
        return $jsLayout;
    }
}
