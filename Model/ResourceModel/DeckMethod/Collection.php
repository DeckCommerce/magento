<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\ResourceModel\DeckMethod;

/**
 * DeckMethod Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'deck_method_id';

    /**
     * Is store filter with admin store
     *
     * @var bool
     */
    protected $_isStoreFilterWithAdmin = true;

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \DeckCommerce\Integration\Model\DeckMethod::class,
            \DeckCommerce\Integration\Model\ResourceModel\DeckMethod::class
        );
    }
}
