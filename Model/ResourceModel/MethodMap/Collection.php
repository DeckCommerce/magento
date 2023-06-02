<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\ResourceModel\MethodMap;

/**
 * MethodMap Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'map_id';

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
            \DeckCommerce\Integration\Model\MethodMap::class,
            \DeckCommerce\Integration\Model\ResourceModel\MethodMap::class
        );
    }

    /**
     * Join Deck Commerce Method Name
     *
     * @return $this
     */
    public function joinDeckMethodName()
    {
        $this->getSelect()->joinLeft(
            ['deck_shipping_method' => $this->getTable('deck_shipping_method')],
            "main_table.deck_method_id = deck_shipping_method.deck_method_id",
            ['deck_method_name']
        );

        return $this;
    }

    /**
     * Specify table for is_enabled filter not to have conflict with same field from joined table
     *
     * @param array|string $field
     * @param null $condition
     * @return Collection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'is_enabled') {
            $field = 'main_table.is_enabled';
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * @return $this|Collection
     */
    protected function _beforeLoad()
    {
        parent::_beforeLoad();
        $this->joinDeckMethodName();

        return $this;
    }
}
