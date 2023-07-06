<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Source;

use DeckCommerce\Integration\Helper\Data as DeckHelper;

/**
 * Rma Is enabled type
 */
class RmaIsEnabled extends \Magento\Config\Model\Config\Source\Yesno
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * RmaIsEnabled constructor.
     * @param DeckHelper $helper
     */
    public function __construct(
        DeckHelper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->helper->isRmaModuleEnabled()) {
            return [['value' => 0, 'label' => __('Not available in Community Edition')]];
        }

        return [['value' => 1, 'label' => __('Yes')], ['value' => 0, 'label' => __('No')]];
    }
}
