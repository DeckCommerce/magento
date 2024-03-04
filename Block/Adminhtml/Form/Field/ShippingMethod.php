<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2024 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class to generate dynamic rows for Shipping Methods mapping
 */
class ShippingMethod extends AbstractFieldArray
{

    /**
     * Prepare dynamic rows
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('magento_shipping_method', [
            'label' => __('Magento Shipping Method'),
            'class' => 'required-entry'
        ]);
        $this->addColumn('dc_shipping_method', [
            'label' => __('Deck Commerce Shipping Method'),
            'class' => 'required-entry'
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Mapping');
    }
}
