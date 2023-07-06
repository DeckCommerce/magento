<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Import;

use DeckCommerce\Integration\Helper\Data as DeckHelper;

/**
 * DeltaInventory Import Model
 */
class DeltaInventory extends AbstractInventory
{

    /**
     * @var string
     */
    protected $inventoryType = DeckHelper::INVENTORY_TYPE_DELTA;
}
