<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Import;

use DeckCommerce\Integration\Helper\Data as DeckHelper;

/**
 * FullInventory Import Model
 */
class FullInventory extends AbstractInventory
{

    /**
     * @var string
     */
    protected $inventoryType = DeckHelper::INVENTORY_TYPE_FULL;
}
