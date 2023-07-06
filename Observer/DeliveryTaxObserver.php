<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * DeliveryTaxObserver class to copy custom field from quote to order
 */
class DeliveryTaxObserver implements ObserverInterface
{

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');

        $order->setData('retail_delivery_tax_amount', $quote->getData('retail_delivery_tax_amount'));

        return $this;
    }
}
