<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\Order\Config as OrderConfig;

/**
 * Sales Order State config source model
 */
class OrderState implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var OrderConfig
     */
    protected $orderConfig;

    /**
     * @var string[]
     */
    protected $statusStateLabelMapping = [
        "Rejected" => "Canceled | Rejected",
        "On Hold"  => "On Hold | Kount Review | Kount Decline"
    ];

    /**
     * @param OrderConfig $orderConfig
     */
    public function __construct(
        OrderConfig $orderConfig
    ) {
        $this->orderConfig = $orderConfig;
    }

    /**
     * Get Order States list
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $orderStatuses = $this->orderConfig->getStates();
        foreach ($orderStatuses as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => $this->statusStateLabelMapping[(string) $label] ?? $label
            ];
        }
        return $options;
    }
}
