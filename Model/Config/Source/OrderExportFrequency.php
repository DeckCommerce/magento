<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Source;

/**
 * Cron Frequency for order export
 */
class OrderExportFrequency implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected static $_options;

    /**
     * Cron frequency options for order export
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = [
                [
                    'label' => __('Every 5 minutes'),
                    'value' => 5,
                ],
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
                    'label' => __('Every 60 minutes'),
                    'value' => 60,
                ],
            ];
        }
        return self::$_options;
    }
}
