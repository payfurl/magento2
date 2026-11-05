<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfurl\Payment\Gateway\Request;

use Payfurl\Payment\Gateway\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\ScopeInterface;

/**
 * Payment Data Builder
 */
class OrderDataBuilder implements BuilderInterface
{
    use Formatter;

    public const INDEX = "Order";
    public const ORDER_NUMBER = 'OrderNumber';

    public const FREIGHT_AMOUNT = 'FreightAmount';

    public const DUTY_AMOUNT = "DutyAmount";

    public const ITEMS = "Items";

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param SubjectReader $subjectReader
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        SubjectReader $subjectReader,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->subjectReader->readPayment($buildSubject)->getPayment()->getOrder();

        // The SDK reads OrderNumber, FreightAmount and DutyAmount from inside Order.
        return [
            self::INDEX => [
                self::ORDER_NUMBER => $order->getIncrementId(),
                self::FREIGHT_AMOUNT => $order->getShippingAmount(),
                self::DUTY_AMOUNT => 0,
                self::ITEMS => $this->buildOrderItems($order),
            ],
        ];
    }

    /**
     * Visible items only: a configurable product's child item would otherwise be counted again.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function buildOrderItems($order): array
    {
        $results = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $totalTaxAmount = $item->getTaxAmount();
            $totalDiscountAmount = $item->getDiscountAmount();
            $itemPrice = $item->getPrice();

            if ($this->isProductPriceIncludingTax($order->getStoreId())) {
                $totalTaxAmount = 0;
                $itemPrice = $item->getPriceInclTax();
            }

            $taxAmountPerItem = 0;
            $taxAmountAdj = 0;

            if ($totalTaxAmount > 0) {
                $taxAmountPerItem = round((float) ($totalTaxAmount / $item->getQtyOrdered()), 2);
                $taxAmountAdj = $totalTaxAmount - ($taxAmountPerItem * $item->getQtyOrdered());
            }

            $discountAmountPerItem = 0;
            $discountAmountAdj = 0;

            if ($totalDiscountAmount > 0) {
                $discountAmountPerItem = round((float) ($totalDiscountAmount / $item->getQtyOrdered()), 2);
                $discountAmountAdj = $totalDiscountAmount - ($discountAmountPerItem * $item->getQtyOrdered());
            }

            /*item price will be sent to Payfurl - Payfurl will use it to recalculate order total*/
            $itemPriceForPayfurlApi = $itemPrice + $taxAmountPerItem - $discountAmountPerItem;

            if ($item->getQtyOrdered() == 1 || !$this->needToSplitItem($discountAmountAdj, $taxAmountAdj)) {
                $results[] = $this->generatePayfurlItemData($item, $item->getQtyOrdered(), $itemPriceForPayfurlApi);
            } else {
                /*
                 * in the case that we cannot split the tax and discount to calculate for item amount,
                 * we need split this item to two item,
                 * one of them will include the taxAmountAdj and discountAmountAdj
                 */

                /* item without adjustment*/
                $results[] = $this->generatePayfurlItemData($item, $item->getQtyOrdered() - 1, $itemPriceForPayfurlApi);

                /*item price with adjustment*/
                $itemPriceForPayfurlApiWithAdj = $itemPrice + ($taxAmountPerItem + $taxAmountAdj) -
                    ($discountAmountPerItem + $discountAmountAdj);

                /*item with adjustment*/
                $results[] = $this->generatePayfurlItemData($item, 1, $itemPriceForPayfurlApiWithAdj);
            }
        }
        return $results;
    }

    /**
     * @param $item
     * @param $qty
     * @param $amount
     * @return array
     */
    private function generatePayfurlItemData($item, $qty, $amount): array
    {
        return [
            'ProductCode' => $item->getSku(),
            'CommodityCode' => null,
            'Description' => $item->getName(),
            'Quantity' => $qty,
            'UnitOfMeasure' => null,
            'Amount' => $amount
        ];
    }


    /**
     * is product price including tax
     *
     * @param $storeId
     * @return mixed
     */
    private function isProductPriceIncludingTax($storeId)
    {
        return $this->scopeConfig->getValue(
            "tax/calculation/price_includes_tax",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $discountAmountAdj
     * @param $taxAmountAdj
     * @return bool
     */
    private function needToSplitItem($discountAmountAdj, $taxAmountAdj): bool
    {
        if (abs($discountAmountAdj) < 0.000001 && abs($taxAmountAdj) < 0.000001) {
            return false;
        }
        return true;
    }
}
