<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Cron;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\DeltaInventory as ImportInventory;

/**
 * DeltaInventoryImportScheduled Cron model
 */
class DeltaInventoryImportScheduled
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
     * DeltaInventoryImportScheduled constructor.
     * @param DeckHelper $helper
     * @param ImportInventory $importInventory
     */
    public function __construct(
        DeckHelper $helper,
        ImportInventory $importInventory
    ) {
        $this->helper          = $helper;
        $this->importInventory = $importInventory;
    }

    /**
     * Execute delta inventory import
     *
     * @return array|false
     */
    public function execute()
    {
        if ($this->helper->isInventoryImportEnabled($this->helper::INVENTORY_TYPE_DELTA)) {
            try {
                $this->importInventory->execute();
                return
                    ['message' => __('Delta inventory import from DeckCommerce has been finished')];
            } catch (\Exception $e) {
                return
                    ['message' => __('Unable to import delta Inventory Import From DeckCommerce. ' . $e->getMessage())];
            }
        }

        return false;
    }
}
