<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Ui\Component\Order\Listing\Column\ExportStatus;

use DeckCommerce\Integration\Model\Export\Order;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Deck Commerce export status options
 */
class Options implements OptionSourceInterface
{

    /**
     * Prepare Deck Commerce export status options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Order::STATUS_PENDING,
                'label' => __('Pending...'),
            ],
            [
                'value' => Order::STATUS_SUCCESS,
                'label' => __('Synced.'),
            ],
            [
                'value' => Order::STATUS_FAILED,
                'label' => __('FAILED!'),
            ],
            [
                'value' => Order::STATUS_SKIPPED,
                'label' => __('Skipped'),
            ],
        ];
    }
}
