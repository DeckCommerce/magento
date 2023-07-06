<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Observer;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

/**
 * Class ApplyDeliveryTax to use Colorado Retail Delivery Tax
 */
class ApplyDeliveryTax implements ObserverInterface
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
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {

        /** @var $total \Magento\Quote\Model\Quote\Address\Total */
        $total = $observer->getData('total');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');

        $coloradoRetailDeliveryTaxAmount = $this->helper->getDeliveryTaxAmount();
        if ($coloradoRetailDeliveryTaxAmount > 0
            && $quote->getShippingAddress()
            && $quote->getShippingAddress()->getRegion() === "Colorado"
        ) {
            $total->addTotalAmount('tax', $coloradoRetailDeliveryTaxAmount);
            $quote->setData('retail_delivery_tax_amount', $coloradoRetailDeliveryTaxAmount);
        }

        return $this;
    }
}
