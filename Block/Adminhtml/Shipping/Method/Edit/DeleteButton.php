<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Adminhtml\Shipping\Method\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Block to display DeleteButton
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{

    /**
     * Get delete shipping method button data
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getDeckMethodId()) {
            $data = [
                'label' => __('Delete Shipping Method'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\''
                    . __('Are you sure you want to delete this shipping method ?')
                    . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Get delete shipping method URL
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('deck/shipping_method/delete', ['deck_method_id' => $this->getDeckMethodId()]);
    }
}
