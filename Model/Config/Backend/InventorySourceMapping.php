<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

/**
 * PaymentMethodsMapping Config Backend model
 */
class InventorySourceMapping extends ArraySerialized
{

    /**
     * Provess config validation on save action
     *
     * @return InventorySourceMapping
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $mappingValue = $this->getValue();
        if (is_array($mappingValue)) {
            unset($mappingValue['__empty']);
        }
        $isMsiEnabled = $this->getDataByPath('groups/inventory_check/fields/msi_enabled/value');
        if (!is_array($mappingValue) || !$isMsiEnabled) {
            return parent::beforeSave();
        }

        if (empty($mappingValue)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Mapped MSI Source/Warehouse list can't be empty."
                    . " Please add at least one \"MSI Source/Warehouse Mapping\" value.")
            );
        }

        $this->validateForDuplicates($mappingValue);

        return parent::beforeSave();
    }

    /**
     * Validate inventory source mapping for duplicated values
     *
     * @param $mappingValue
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateForDuplicates($mappingValue)
    {
        $duplicatedSources = $this->searchDuplicates($mappingValue, 'magento_source');
        if (count($duplicatedSources) > 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Duplicated Magento source value(s) found in a mapping: \"%1\". All source values should be unique.",
                    implode(', ', $duplicatedSources)
                )
            );
        }

        $duplicatedDcWhCodes = $this->searchDuplicates($mappingValue, 'dc_warehouse');
        if (count($duplicatedDcWhCodes) > 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Duplicated Deck Commerce warehouse code(s) found in a mapping: \"%1\". All warehouse code values should be unique.",
                    implode(', ', $duplicatedDcWhCodes)
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
