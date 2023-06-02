<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Cron;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\FullInventory as ImportInventory;

/**
 * FullInventoryImportScheduled Cron model
 */
class FullInventoryImportScheduled
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var ImportInventory
     */
    protected $importInventory;

    /**
     * FullInventoryImportScheduled constructor.
     * @param DeckHelper $helper
     * @param ImportInventory $importInventory
     */
    public function __construct(
        DeckHelper $helper,
        ImportInventory $importInventory
    ) {
        $this->helper = $helper;
        $this->importInventory = $importInventory;
    }

    /**
     * Execute full inventory import
     *
     * @return array|false
     */
    public function execute()
    {
        if ($this->helper->isInventoryImportEnabled($this->helper::INVENTORY_TYPE_FULL)) {
            try {
                $this->importInventory->execute();
                return
                    ['message' => __('Full inventory import from DeckCommerce has been finished')];
            } catch (\Exception $e) {
                return
                    ['message' => __('Unable to import full Inventory Import From DeckCommerce. ' . $e->getMessage())];
            }
        }

        return false;
    }
}
