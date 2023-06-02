<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request;

/**
 * Service Request RmaBuilderInterface
 */
interface RmaBuilderInterface
{

    /**
     * Build data for RMA export
     *
     * @param string $orderId
     * @param string $rmaNumber
     * @param array $items
     * @return array
     */
    public function build($orderId, $rmaNumber, $items);
}
