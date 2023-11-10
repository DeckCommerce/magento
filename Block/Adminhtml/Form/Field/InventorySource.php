<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
* Class InventorySource
 */
class InventorySource extends AbstractFieldArray
{

    private $magentoSourceRenderer;

    /**
     * Prepare dynamic rows
     *
     * @return void
     */
    protected function _prepareToRender()
    {

        $this->addColumn('magento_source', [
            'label' => __('Magento Source'),
            'renderer' => $this->getMagentoSourceRenderer(),
            'class' => 'required-entry'
        ]);
        $this->addColumn('dc_warehouse', [
            'label' => __('Deck Commerce Warehouse Code'),
            'class' => 'required-entry'
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');

        if (empty($this->getElement()->getValue())) {
            $defaultRows = $this->getDefaultRows();
            $this->getElement()->setValue($defaultRows);
        }
    }

    /**
     * Get default rows with inventory source values
     *
     * @return array
     * @throws LocalizedException
     */
    private function getDefaultRows()
    {
        $sources = $this->getMagentoSourceRenderer()->getOptions();
        $defaultRows = [];

        foreach ($sources as $index => $source) {
            $defaultRows['row_' . $index]  = [
                'magento_source' => $source['value'],
                'dc_warehouse' => ''
            ];
        }

        return $defaultRows;
    }

    /**
     * Get Magento source renderer
     *
     * @return SourceColumn|(SourceColumn&\Magento\Framework\View\Element\BlockInterface)|\Magento\Framework\View\Element\BlockInterface
     * @throws LocalizedException
     */
    private function getMagentoSourceRenderer()
    {
        if (!$this->magentoSourceRenderer) {
            $this->magentoSourceRenderer = $this->getLayout()->createBlock(
                SourceColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->magentoSourceRenderer;
    }

    /**
     * Prepare array row for dynamic config
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $magentoSource = $row->getMagentoSource();
        if ($magentoSource !== null) {
            $options['option_' . $this->getMagentoSourceRenderer()->calcOptionHash($magentoSource)]
                = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }
}
