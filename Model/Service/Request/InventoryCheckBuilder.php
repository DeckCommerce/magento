<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Model\Service\Request;

/**
 * InventoryCheckBuilder model
 */
class InventoryCheckBuilder implements InventoryCheckBuilderInterface
{
    /**
     * Prepare data for check inventory API request
     *
     * @param array $skus
     * @param string $inventoryID
     * @return array
     */
    public function build($skus, $inventoryID)
    {
        $items = [];
        foreach ($skus as $sku) {
            $items[] = [
                "InventoryFeedName" => $inventoryID,
                "SKU"               => $sku
            ];
        }

        return [
            "Items" => $items
        ];
    }
}
