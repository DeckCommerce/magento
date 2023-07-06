<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Sales\Shipping;

/**
 * Shipping Items block
 */
class Items extends \Magento\Shipping\Block\Items
{

    /**
     * Get print shipment URL
     *
     * @param object $shipment
     * @return string
     */
    public function getPrintShipmentUrl($shipment)
    {
        return $this->getUrl('*/*/printShipment', [
            'shipment_id' => $shipment->getId(),
            'order_id'    => $shipment->getOrder()->getId()
        ]);
    }
}
