<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Data;

/**
 * Custom data collection class
 */
class Collection extends \Magento\Framework\Data\Collection
{

    /**
     * Set collection totals count
     *
     * @param $totalRecords
     */
    public function setTotalCount($totalRecords)
    {
        $this->_totalRecords = $totalRecords;
    }

    /**
     * Stub for db addFieldToFilter
     *
     * @param array|string $field
     * @param null $condition
     * @return $this|Collection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        return $this;
    }
}
