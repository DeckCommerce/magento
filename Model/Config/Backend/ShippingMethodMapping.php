<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2024 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

/**
 * ShippingMethodMapping Config Backend model
 */
class ShippingMethodMapping extends ArraySerialized
{

    /**
     * Process config validation on save action
     *
     * @return ShippingMethodMapping
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $mappingValue = $this->getValue();
        if (is_array($mappingValue)) {
            unset($mappingValue['__empty']);
        }
        $isMappingEnabled = $this->getDataByPath('groups/order/fields/use_shipping_methods_mapping/value');
        if (!is_array($mappingValue) || !$isMappingEnabled) {
            return parent::beforeSave();
        }

        if (empty($mappingValue)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Mapped Magento / Deck Commerce Shipping Methods mapping list can't be empty."
                    . " Please add at least one \"Magento/Deck Commerce Shipping Method mapping\" value.")
            );
        }

        $this->validateForDuplicates($mappingValue);

        return parent::beforeSave();
    }

    /**
     * Validate shipping methods mapping for duplicated values
     *
     * @param $mappingValue
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateForDuplicates($mappingValue)
    {
        $duplicatedMethods = $this->searchDuplicates($mappingValue, 'magento_shipping_method');
        if (count($duplicatedMethods) > 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Duplicated Magento shipping method value(s) found in a mapping: \"%1\". All such values should be unique.",
                    implode(', ', $duplicatedMethods)
                )
            );
        }
    }

    /**
     * Search duplicated keys and values in mapping array
     *
     * @param $mappingValue
     * @param $field
     * @return int[]|string[]
     */
    protected function searchDuplicates($mappingValue, $field)
    {
        $sourceValues = array_column((array) $mappingValue, $field);
        return array_keys(array_filter(array_count_values($sourceValues), fn($count) => $count > 1));
    }
}
