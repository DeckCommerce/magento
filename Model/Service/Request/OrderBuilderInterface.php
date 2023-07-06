<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Service Request OrderBuilderInterface
 */
interface OrderBuilderInterface
{

    /**
     * Build data for order export
     *
     * @param OrderInterface $order
     * @return array
     */
    public function build(OrderInterface $order);
}
