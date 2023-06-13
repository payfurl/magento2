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

    const INDEX = "Order";
    const ORDER_NUMBER = 'OrderNumber';

    const FREIGHT_AMOUNT = 'FreightAmount';

    const DUTY_AMOUNT = "DutyAmount";

    const ITEMS = "Items";

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
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        return [
            self::ORDER_NUMBER => $order->getOrderIncrementId(),
            self::FREIGHT_AMOUNT => $order->getShippingAmount(),
            self::DUTY_AMOUNT => 0,
            self::INDEX => [
                self::ITEMS => $this->buildOrderItems($order)
            ]
        ];
    }

    /**
     * @param $order
     * @return array
     */
    public function buildOrderItems($order): array
    {
        $results = [];
        foreach ($order->getItems() as $item) {
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
     * Payfurl only allows 12 character for ProductCode, 26 characters for description
     *
     * @param $string
     * @param $length
     * @return false|string
     */
    public function generateValidDataForOrderItem($string, $length)
    {
        return substr($string, 0, $length);
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
            'ProductCode' => $this->generateValidDataForOrderItem($item->getSku(), 12),
            'CommodityCode' => null,
            'Description' => $this->generateValidDataForOrderItem($item->getName(), 12),
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
