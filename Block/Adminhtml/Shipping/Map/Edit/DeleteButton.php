<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Adminhtml\Shipping\Map\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Block to display DeleteButton
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{

    /**
     * Get Delete Methods Mapping button data
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getMethodsMapId()) {
            $data = [
                'label' => __('Delete Methods Mapping'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\''
                    . __('Are you sure you want to delete this shipping methods mapping?')
                    . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Get delete methods mapping URL
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['map_id' => $this->getMethodsMapId()]);
    }
}
