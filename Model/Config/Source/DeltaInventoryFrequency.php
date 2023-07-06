<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Source;

/**
 * Cron Frequency for delta inventory import
 */
class DeltaInventoryFrequency implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected static $_options;

    /**
     * Cron frequency options for delta inventory
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = [
                [
                    'label' => __('Every 10 minutes'),
                    'value' => 10,
                ],
                [
                    'label' => __('Every 20 minutes'),
                    'value' => 20,
                ],
                [
                    'label' => __('Every 30 minutes'),
                    'value' => 30,
                ],
                [
                    'label' => __('Every 1 hour'),
                    'value' => 60,
                ],
                [
                    'label' => __('Every 2 hours'),
                    'value' => 120,
                ],
                [
                    'label' => __('Every 3 hours'),
                    'value' => 180,
                ],
                [
                    'label' => __('Every 6 hours'),
                    'value' => 360,
                ],
                [
                    'label' => __('Every 12 hours'),
                    'value' => 720,
                ],
            ];
        }
        return self::$_options;
    }
}
