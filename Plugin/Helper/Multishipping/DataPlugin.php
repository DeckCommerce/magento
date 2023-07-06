<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Plugin\Helper\Multishipping;

use DeckCommerce\Integration\Helper\Data as HelperData;
use Magento\Multishipping\Helper\Data as MultishippingHelper;

/**
 * Multishipping helper plugin class
 */
class DataPlugin
{

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * DataPlugin constructor.
     * @param HelperData $helper
     */
    public function __construct(HelperData $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Check if multishipping checkout is available
     * There should be a valid quote in checkout session. If not, only the config value will be returned
     *
     * @param MultishippingHelper $subject
     * @param $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsMultishippingCheckoutAvailable(MultishippingHelper $subject, $result)
    {
        /*if ($this->helper->isEnabled()) {
            return false;
        }*/

        return $result;
    }
}
