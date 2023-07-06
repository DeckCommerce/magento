<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Total;

use DeckCommerce\Integration\Helper\Data as DeckHelper;

/**
 * Class DeliveryTax
 */
class DeliveryTax extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @param DeckHelper $helper
     */
    public function __construct(DeckHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this|DeliveryTax
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $coloradoRetailDeliveryTaxAmount = $this->helper->getDeliveryTaxAmount();
        if ($coloradoRetailDeliveryTaxAmount > 0
            && $quote->getShippingAddress()
            && $quote->getShippingAddress()->getRegion() === "Colorado"
        ) {
            $quote->setData('retail_delivery_tax_amount', $coloradoRetailDeliveryTaxAmount);
            $total->setTotalAmount('retail_delivery_tax_amount', $coloradoRetailDeliveryTaxAmount);
            $total->setBaseTotalAmount('retail_delivery_tax_amount', $coloradoRetailDeliveryTaxAmount);
        }

        return $this;
    }
}
