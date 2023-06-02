<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request;

/**
 * OrderHistory model
 */
class OrderHistory implements OrderHistoryInterface
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
    public function build($pageNumber, $pageSize, $customerId = null, $orderId = null)
    {
        $request = [];
        if ($customerId || $orderId) {
            $request = [
                "PageNumber"  => $pageNumber,
                "PageSize"    => $pageSize,
                "CustomerID"  => $customerId,
                "OrderNumber" => $orderId
            ];
        }

        return $request;
    }
}
