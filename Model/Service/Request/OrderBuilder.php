<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Export\Order as DeckOrder;
use DeckCommerce\Integration\Model\Service\Request\Order\ItemBuilder;
use DeckCommerce\Integration\Model\Service\Request\Order\PaymentBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * OrderBuilder model
 */
class OrderBuilder implements OrderBuilderInterface
{
    const ORDER_SOURCE = 'web';

    const ORDER_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var ItemBuilder
     */
    protected $itemBuilder;

    /**
     * @var PaymentBuilder
     */
    protected $paymentBuilder;

    /**
     * OrderBuilder constructor.
     *
     * @param DeckHelper $helper
     * @param ItemBuilder $itemBuilder
     * @param PaymentBuilder $paymentBuilder
     */
    public function __construct(
        DeckHelper $helper,
        ItemBuilder $itemBuilder,
        PaymentBuilder $paymentBuilder
    ) {
        $this->helper         = $helper;
        $this->itemBuilder    = $itemBuilder;
        $this->paymentBuilder = $paymentBuilder;
    }

    /**
     * Build data for order export
     *
     * @param OrderInterface $order
     * @return array
     */
    public function build(OrderInterface $order)
    {
        $result = [
            "OrderNumber"         => $order->getIncrementId(),
            "CustomerID"          => $order->getCustomerId() ?: DeckOrder::GUEST_CUSTOMER_ID,
            "OrderDateUTC"        => gmdate(self::ORDER_DATE_FORMAT, strtotime($order->getCreatedAt())),
            "ShippingMethod"      => $this->helper->getMappedDeckShippingMethod($order->getShippingMethod()),
            "OrderSource"         => self::ORDER_SOURCE,
            "CustomerOrderLocale" => $this->helper->getOrderLocale($order),
            "SourceIp"            => $order->getRemoteIp(),

            "OrderNetTotal"    => $this->getOrderNetTotal($order),
            "OrderGrossTotal"  => $this->getOrderGrossTotal($order),
            "OrderAdjustments" => $this->getOrderAdjustments($order),

            "MerchandiseNetTotal"   => $this->getMerchandiseNetTotal($order),
            "MerchandiseGrossTotal" => $this->getMerchandiseGrossTotal($order),

            "AdjustedMerchandiseNetTotal"   => $this->getAdjustedMerchandiseNetTotal($order),
            "AdjustedMerchandiseGrossTotal" => $this->getAdjustedMerchandiseGrossTotal($order),

            "ShippingNetTotal"   => $this->getShippingNetTotal($order),
            "ShippingGrossTotal" => $this->getShippingGrossTotal($order),

            "AdjustedShippingNetTotal"   => $this->getAdjustedShippingNetTotal($order),
            "AdjustedShippingGrossTotal" => $this->getAdjustedShippingGrossTotal($order),

            "GiftCards"        => null,
            "CustomAttributes" => [],

            "OrderTaxes" => $this->getTaxes($order),

            "ShippingMethods" => $this->getShippingMethods($order),

            "BillingAddress"  => $this->getAddress($order->getBillingAddress()),
            "ShippingAddress" => $this->getAddress($order->getShippingAddress())
        ];

        $result = array_merge($result, $this->itemBuilder->build($order), $this->paymentBuilder->build($order));

        return $result;
    }

    /**
     * AdjustedMerchandiseNetTotal + AdjustedShippingNetTotal
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getOrderNetTotal(OrderInterface $order)
    {
        $result =
            (float) $this->getAdjustedMerchandiseNetTotal($order) +
            (float) $this->getAdjustedShippingNetTotal($order);

        return $this->helper->formatPrice($result);
    }

    /**
     * Total for all items after discount (item level discount, pro-rated order level discount) but before tax
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getAdjustedMerchandiseNetTotal(OrderInterface $order)
    {
        $result = $order->getSubtotal() - abs($order->getDiscountAmount() + $order->getShippingDiscountAmount());
        $result = max($result, 0);

        return $this->helper->formatPrice($result);
    }

    /**
     * Total for all items after discount (item level discount, pro-rated order level discount) and after tax
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getAdjustedMerchandiseGrossTotal(OrderInterface $order)
    {
        $result =
            (float) $this->getAdjustedMerchandiseNetTotal($order)
            + $order->getTaxAmount()
            - $order->getShippingTaxAmount()
            - $this->getRetailDeliveryTaxAmount($order);

        return $this->helper->formatPrice($result);
    }

    /**
     * Shipping Total minus shipping discount but before tax
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getAdjustedShippingNetTotal(OrderInterface $order)
    {
        $result = $order->getShippingAmount() - $order->getShippingDiscountAmount();

        return $this->helper->formatPrice($result);
    }

    /**
     * AdjustedMerchandiseGrossTotal + AdjustedShippingGrossTotal
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getOrderGrossTotal(OrderInterface $order)
    {
        return $this->helper->formatPrice($order->getGrandTotal())
            + $this->helper->formatPrice($order->getBaseCustomerBalanceAmount())
            + $this->helper->formatPrice($order->getBaseRewardCurrencyAmount());
    }

    /**
     * List of order adjustments if any. This would typically be shipping discounts.
     * It should be noted that it is expected that any order level discounts are pro-rated before being passed into OMS.
     *
     * @param OrderInterface $order
     * @return array
     */
    protected function getOrderAdjustments(OrderInterface $order)
    {
        $adjustments = [];
        $shippingDiscount = $this->helper->formatPrice($order->getShippingDiscountAmount());
        $adjustments[] = [
            "Amount"         => $shippingDiscount,
            "AdjustmentType" => "ShippingDiscount",
            "CouponCode"     => "",
            "DiscountText"   => "Shipping Discount",
            "CampaignID"     => "",
            "PromotionID"    => ""
        ];

        return $adjustments;
    }

    /**
     * Total for all items before tax
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getMerchandiseNetTotal(OrderInterface $order)
    {
        return $this->helper->formatPrice($order->getSubtotal());
    }

    /**
     * Total for all items after tax
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getMerchandiseGrossTotal(OrderInterface $order)
    {
        $result = $order->getSubtotal()
            + $order->getTaxAmount()
            - $order->getShippingTaxAmount()
            - $this->getRetailDeliveryTaxAmount($order);

        return $this->helper->formatPrice($result);
    }

    /**
     * Shipping Total before tax
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getShippingNetTotal(OrderInterface $order)
    {
        return $this->helper->formatPrice($order->getShippingAmount());
    }

    /**
     * Shipping Total after tax
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getShippingGrossTotal(OrderInterface $order)
    {
        $result = $order->getShippingAmount()
            + $order->getShippingTaxAmount()
            + $this->getRetailDeliveryTaxAmount($order);

        return $this->helper->formatPrice($result);
    }

    /**
     * Shipping Total minus shipping discount and after tax
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getAdjustedShippingGrossTotal(OrderInterface $order)
    {
        $result = $order->getShippingAmount()
            + $order->getShippingTaxAmount()
            + $this->getRetailDeliveryTaxAmount($order)
            - $order->getShippingDiscountAmount();

        return $this->helper->formatPrice($result);
    }

    /**
     * Get Colorado Retail Delivery Tax amount
     *
     * @param OrderInterface $order
     * @return mixed
     */
    protected function getRetailDeliveryTaxAmount($order)
    {
        return $this->helper->formatPrice($order->getData('retail_delivery_tax_amount'));
    }

    /**
     * List of order level taxes. This will primarily be shipping tax.
     *
     * @param OrderInterface $order
     * @return array
     */
    protected function getTaxes(OrderInterface $order)
    {
        $taxes[] = [
            "TaxType" => "USShippingTotal",
            "Amount"  => $this->helper->formatPrice($order->getShippingTaxAmount())
        ];

        if ($order->getData('retail_delivery_tax_amount') > 0) {
            $taxes[] = [
                "TaxType" => "RetailDeliveryFee",
                "Amount"  => $this->getRetailDeliveryTaxAmount($order)
            ];
        }

        return $taxes;
    }

    /**
     * Get shipping methods data for Deck Commerce export
     *
     * @param OrderInterface $order
     * @return array
     */
    protected function getShippingMethods(OrderInterface $order)
    {
        $result = [
            "ReferenceID"        => null,
            "DwShipmentNo"       => $order->getShippingAddress() ? $order->getShippingAddress()->getId() : '',
            "ShippingMethod"     => $this->helper->getMappedDeckShippingMethod($order->getShippingMethod()),
            "NetTotal"           => $this->getShippingNetTotal($order),
            "GrossTotal"         => $this->getShippingGrossTotal($order),
            "AdjustedNetTotal"   => $this->getAdjustedShippingNetTotal($order),
            "AdjustedGrossTotal" => $this->getAdjustedShippingGrossTotal($order),
            "Taxes"              => $this->getTaxes($order)
        ];

        return $result;
    }

    /**
     * Get address data for Deck Commerce export
     *
     * @param \Magento\Sales\Model\Order\Address $address
     * @return array
     */
    protected function getAddress($address)
    {
        return [
            "FirstName"   => $address->getFirstname(),
            "LastName"    => $address->getLastname(),
            "Address1"    => $address->getStreetLine(1),
            "Address2"    => $address->getStreetLine(2),
            "City"        => $address->getCity(),
            "Province"    => $address->getRegionCode(),
            "PostalCode"  => $address->getPostcode(),
            "Country"     => $address->getCountryId(),
            "Email"       => $address->getEmail(),
            "Phone"       => $address->getTelephone(),
            "Salutation"  => null,
            "CompanyName" => $address->getCompany(),
            "Suffix"      => $address->getSuffix()
        ];
    }
}
