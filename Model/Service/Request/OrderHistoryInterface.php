<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request;

/**
 *  Service Request OrderHistoryInterface
 */
interface OrderHistoryInterface
{

    /**
     * Build data for orders history API request
     *
     * @param int $pageNumber
     * @param int $pageSize
     * @param null|int $customerId
     * @param null|int $orderId
     * @return array
     */
    public function build($pageNumber, $pageSize, $customerId = null, $orderId = null);
}
