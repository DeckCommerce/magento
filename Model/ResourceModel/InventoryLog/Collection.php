<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\ResourceModel\InventoryLog;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\SalesArchive\Model\ArchivalList;

/**
 * InventoryLog Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \DeckCommerce\Integration\Model\InventoryLog::class,
            \DeckCommerce\Integration\Model\ResourceModel\InventoryLog::class
        );
    }
}
