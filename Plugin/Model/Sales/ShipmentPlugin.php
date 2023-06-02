<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Plugin\Model\Sales;

use Magento\Sales\Model\Order\Shipment;

/**
 * Order Shipment Plugin
 */
class ShipmentPlugin
{

    /**
     * Replace default Magento shipment tracks collection with tracks collection prepared from Deck Commerce data
     *
     * @param Shipment $subject
     * @param callable $proceed
     * @return float|mixed|null
     */
    public function aroundGetTracksCollection(Shipment $subject, callable $proceed)
    {
        if ($subject->hasData('deck_ship_tracks_collection')) {
            return $subject->getData('deck_ship_tracks_collection');
        } else {
            return $proceed();
        }
    }
}
