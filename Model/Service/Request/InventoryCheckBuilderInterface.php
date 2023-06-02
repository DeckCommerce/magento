<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request;

/**
 * Request Request InventoryCheckBuilderInterface
 */
interface InventoryCheckBuilderInterface
{

    /**
     * Prepare data for check inventory API request
     *
     * @param array $skus
     * @param string $inventoryID
     * @return array
     */
    public function build($skus, $inventoryID);
}
