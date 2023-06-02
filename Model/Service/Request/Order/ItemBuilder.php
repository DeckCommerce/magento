<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\UrlInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation;
use Magento\GiftMessage\Helper\Message as GiftMessageHelper;

/**
 * Order ItemBuilder model
 */
class ItemBuilder
{
    const TAX_TYPE                 = "USSalesTotal";
    const ADJUSTMENT_ITEM_DISCOUNT = "ItemDiscount";

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * Tax calculation tool
     *
     * @var Calculation
     */
    protected $calculationTool;

    /**
     * @var GiftMessageHelper
     */
    protected $giftMessageHelper;

    /**
     * @var array
     */
    protected $roundingDeltas;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GetStockItemConfigurationInterface
     */
    protected $getStockItemConfiguration;

    /**
     * ItemBuilder constructor.
     *
     * @param DeckHelper $helper
     * @param Calculation $calculationTool
     * @param GiftMessageHelper $giftMessageHelper
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     */
    public function __construct(
        DeckHelper $helper,
        Calculation $calculationTool,
        GiftMessageHelper $giftMessageHelper,
        GetStockItemConfigurationInterface $getStockItemConfiguration
    ) {
        $this->helper                    = $helper;
        $this->calculationTool           = $calculationTool;
        $this->giftMessageHelper         = $giftMessageHelper;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
    }

    /**
     * Prepare order items data for export to DeckCommerce
     *
     * @param OrderInterface $order
     * @return array
     */
    public function build(OrderInterface $order)
    {
        return [
            "OrderItems" => $this->getOrderItems($order)
        ];
    }

    /**
     * Get product options such as configurable attributes
     *
     * @param Item $orderItem
     * @return array
     */
    protected function getItemProductOptions($orderItem)
    {
        $result  = [];
        $options = $orderItem->getProductOptions();
        if ($options) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (!empty($options['attributes_info'])) {
                $result = array_merge($options['attributes_info'], $result);
            }
        }
        return $result;
    }

    /**
     * Get configurable product options and fill corresponding Deck Commerce fields:
     * Custom1, Custom2, Custom3, Custom4, Custom5 and ignore if number of attributes more than 5
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param Item $orderItem
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getProductOptionsData($product, $orderItem)
    {
        $result  = [];
        $options = $this->getItemProductOptions($orderItem);
        if (empty($options)) {
            return $result;
        }

        $customNumber = 1;
        foreach ($options as $option) {
            if (isset($option['option_id']) && $option['option_id']) {
                $attributeId = $option['option_id'];
                if ($attributeId === $this->helper->getSizeAttributeId()) {
                    $result["ProductSize"] = $option['value'];
                }

                if ($customNumber > 5) {
                    continue;
                }
                $result["Custom{$customNumber}"] = sprintf('%s: %s', $option['label'], $option['value']);
                $customNumber++;
            }
        }

        if (empty($result)) {
            $size = $this->getAttributeText($product, 'size');
            if ($size) {
                $result["Custom{$customNumber}"] = sprintf('%s: %s', 'Size', $size);
                $customNumber++;
            }
            $color = $this->getAttributeText($product, 'color');
            if ($color) {
                $result["Custom{$customNumber}"] = sprintf('%s: %s', 'Color', $color);
            }
        }

        return $result;
    }

    /**
     * Get child product of configurable item
     * or get product of order item for another types
     *
     * @param Item $orderItem
     * @return Product
     */
    protected function getOrderItemProduct($orderItem)
    {
        if ($orderItem->getProductType() === Configurable::TYPE_CODE) {
            /** @var Item $childItem */
            foreach ($orderItem->getChildrenItems() as $childItem) {
                return $childItem->getProduct();
            }
        }

        return $orderItem->getProduct();
    }

    /**
     * Get order item gift message
     *
     * @param Item $orderItem
     * @return \Magento\GiftMessage\Model\Message|string
     */
    protected function getGiftMessage($orderItem)
    {
        if ($orderItem->getGiftMessageId()) {
            $giftMessage = $this->giftMessageHelper->getGiftMessage($orderItem->getGiftMessageId());

            return $giftMessage->getMessage();
        }

        return "";
    }

    /**
     * Get product attribute option value
     *
     * @param Product $product
     * @param string $attributeCode
     * @return string
     */
    protected function getAttributeText($product, $attributeCode)
    {
        if ($product->getData($attributeCode)) {
            return $product->getAttributeText($attributeCode);
        }

        return '';
    }

    /**
     * Prepare order items, split by quantity.
     * Each item has quantity = 1
     *
     * @param Item $orderItem
     * @return string[]
     */
    protected function getOrderItemsByQty($orderItem)
    {
        $orderItems = [];

        $product = $this->getOrderItemProduct($orderItem);
        $upcAttributeCode = $this->helper->getOrderItemUpcAttribute();
        $itemsQty = $orderItem->getQtyOrdered();

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $productName = html_entity_decode($orderItem->getName());

        $itemData = [
            "OrderItemTypeID"     => $product->getTypeId() === Giftcard::TYPE_GIFTCARD ? "GiftCard" : "RegularIItem",
            "SKU"                 => $orderItem->getSku(),
            "GTIN"                => $upcAttributeCode ? $product->getData($upcAttributeCode) : $orderItem->getSku(),
            "ImageURL"            => $this->getImageUrl($product),
            "MSRP"                => $this->getMsrp($product),
            "StyleNumber"         => $productName,
            "ProductSize"         => $this->getAttributeText($product, 'size'),
            "GiftMessage"         => $this->getGiftMessage($orderItem),
            "UnlimitedBackorder"  => $this->canBackorder($orderItem),
            "ShippingReferenceID" => 0,
            "TaxCode"             => "",
            "Extended" => [
                [
                    "Key"   => "Returnable",
                    "Value" => $product->getData('is_returnable') ? "Yes" : "No"
                ]
            ],
            "Custom1"  => "",
            "Custom2"  => "",
            "Custom3"  => "",
            "Custom4"  => "",
            "Custom5"  => "",
        ];

        $optionsData = $this->getProductOptionsData($product, $orderItem);
        $itemData = array_merge($itemData, $optionsData);

        for ($i = 1; $i <= (int) $itemsQty; $i++) {
            $itemData["OrderItemTaxes"]  = $this->getOrderItemTaxes($orderItem);
            $itemData["ItemAdjustments"] = $this->getItemAdjustments($orderItem);
            $itemData["NetPrice"]        = $this->getNetPrice($orderItem);
            $itemData["GrossPrice"]      = $this->getGrossPrice($orderItem);

            $orderItems[] = $itemData;
        }

        return $orderItems;
    }

    /**
     * Check if order item can be used for backorder
     *
     * @param $orderItem
     * @return bool
     */
    protected function canBackorder($orderItem)
    {
        $websiteCode = $orderItem->getStore()->getWebsite()->getCode();

        return $this->helper->canBackorder($websiteCode, $orderItem->getSku());
    }

    /**
     * Get image for product
     *
     * @param Product $product
     * @return string
     */
    protected function getImageUrl(Product $product)
    {
        if (!$product->getImage()) {
            return '';
        }
        $baseUrl = $product->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return $baseUrl . 'catalog/product' . $product->getImage();
    }

    /**
     * Get MSRP price
     *
     * @param Product $product
     * @return string
     */
    protected function getMsrp(Product $product)
    {
        return $this->helper->formatPrice($product->getMsrp());
    }

    /**
     * Get Net price
     * Adjustment amount before tax
     *
     * @param Item $orderItem
     * @return string
     */
    protected function getNetPrice(Item $orderItem)
    {
        return $this->helper->formatPrice($orderItem->getPrice());
    }

    /**
     * Get tax, rounded by qty 1
     *
     * @param Item $orderItem
     * @return int
     */
    protected function getItemRoundedTax($orderItem)
    {
        return $orderItem->getData('rounded_tax_amount') ?: 0;
    }

    /**
     * Adjustment amount after tax
     *
     * @param Item $orderItem
     * @return string
     */
    protected function getGrossPrice(Item $orderItem)
    {
        $grossPrice = $orderItem->getPrice() + $this->getItemRoundedTax($orderItem);

        return $this->helper->formatPrice($grossPrice);
    }

    /**
     * Round price based on previous rounding operation delta
     *
     * @param float $price
     * @param int $qty
     * @param int $key
     * @param string $type
     * @return float
     */
    protected function deltaRound($price, $qty, $key, $type)
    {
        if ($price && $qty > 0) {
            $price = $price / $qty;
            $key .= $type;
            // initialize the delta to a small number to avoid non-deterministic behavior with rounding of 0.5
            $delta = isset($this->roundingDeltas[$key][$qty]) ?
                $this->roundingDeltas[$key][$qty] :
                0.000001;
            $price += $delta;
            $roundPrice = $this->calculationTool->round($price);
            $this->roundingDeltas[$key][$qty] = $price - $roundPrice;
            $price = $roundPrice;
        }
        return $price;
    }

    /**
     * Get order item taxes
     *
     * @param Item $orderItem
     * @return array
     */
    protected function getOrderItemTaxes(Item $orderItem)
    {
        $taxAmount = $this->deltaRound(
            $orderItem->getBaseTaxAmount(),
            (int) $orderItem->getQtyOrdered(),
            $orderItem->getItemId(),
            self::TAX_TYPE,
        );

        $orderItem->setData('rounded_tax_amount', $taxAmount);

        return [
            [
                "TaxType" => "USSalesTotal",
                "Amount"  => $taxAmount
            ]
        ];
    }

    /**
     * Get order item adjustments
     *
     * @param Item $orderItem
     * @return array
     */
    protected function getItemAdjustments(Item $orderItem)
    {
        $itemAdjustments = [];
        if ($orderItem->getAppliedRuleIds()) {
            $ruleIds = array_unique(explode(',', $orderItem->getAppliedRuleIds()));
            foreach ($ruleIds as $ruleId) {
                $ruleDiscount = $this->getDiscountItemAdjustment($ruleId, $orderItem);
                if (!empty($ruleDiscount)) {
                    $itemAdjustments[] = $ruleDiscount;
                }
            }
        }

        return $itemAdjustments;
    }

    /**
     * Get discount item adjustment
     *
     * @param int $ruleId
     * @param Item $orderItem
     * @return array
     */
    protected function getDiscountItemAdjustment($ruleId, $orderItem)
    {
        $additionalData = $orderItem->getAdditionalData();
        $additionalData = $this->helper->jsonDecode($additionalData) ?: [];
        $additionalData = $additionalData['discounts'][$ruleId] ?? [];

        if (!empty($additionalData) || $orderItem->getBaseDiscountAmount() > 0) {
            $discountAmount = $additionalData['base_amount'] ?? 0;
            if ($discountAmount > 0) {
                $netPrice = $this->deltaRound(
                    $discountAmount,
                    (int) $orderItem->getQtyOrdered(),
                    $orderItem->getItemId(),
                    self::ADJUSTMENT_ITEM_DISCOUNT
                );

                return [
                    "NetPrice"       => $this->helper->formatPrice($netPrice),
                    "GrossPrice"     => $this->helper->formatPrice($netPrice),
                    "AdjustmentType" => "ItemDiscount",
                    "CouponCode"     => $additionalData['coupon_code'] ?? "",
                    "DiscountText"   => $additionalData['label'] ?? ""
                ];
            }
        }

        return [];
    }

    /**
     * Get Magento order items split by qty to separate items
     *
     * @param OrderInterface $order
     * @return array
     */
    protected function getOrderItems(OrderInterface $order)
    {
        $orderItems = [];
        /** @var Item $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getProductType() === Type::TYPE_BUNDLE && $orderItem->getHasChildren()) {
                foreach ($orderItem->getChildrenItems() as $childItem) {
                    $childItem->setAppliedRuleIds($orderItem->getAppliedRuleIds());
                    $orderItems[] = $this->getOrderItemsByQty($childItem);
                }
                continue;
            }

            $orderItems[] = $this->getOrderItemsByQty($orderItem);
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $orderItems = call_user_func_array('array_merge', $orderItems);

        return $orderItems;
    }
}
