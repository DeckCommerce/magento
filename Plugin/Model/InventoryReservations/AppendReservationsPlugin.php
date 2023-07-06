<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Plugin\Model\InventoryReservations;

use DeckCommerce\Integration\Helper\Data as HelperData;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;

/**
 * Append Inventory Reservations Plugin
 */
class AppendReservationsPlugin
{

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * OrderPlugin constructor.
     *
     * @param HelperData $helper
     */
    public function __construct(HelperData $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Disable inventory reservations if it's managed by DeckCommerce
     *
     * @param AppendReservationsInterface $subject
     * @param \Closure $proceed
     * @param ReservationInterface[] $reservations
     *
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(AppendReservationsInterface $subject, \Closure $proceed, array $reservations)
    {
        if ($this->helper->isEnabled()) {
            return;
        }

        $proceed($reservations);
    }
}
