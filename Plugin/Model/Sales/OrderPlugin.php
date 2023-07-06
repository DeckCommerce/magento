<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Plugin\Model\Sales;

use DeckCommerce\Integration\Helper\Data as HelperData;
use Magento\Sales\Model\Order;

/**
 * Sales Order Plugin
 */
class OrderPlugin
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
     * Replace default Magento order shipments collection with shipments collection prepared from Deck Commerce data
     *
     * @param Order $subject
     * @param callable $proceed
     * @return float|mixed|null
     */
    public function aroundGetShipmentsCollection(Order $subject, callable $proceed)
    {
        if ($subject->hasData('deck_shipments_collection')) {
            return $subject->getData('deck_shipments_collection');
        } else {
            return $proceed();
        }
    }

    /**
     * Replace default Magento order tracks collection with tracks collection prepared from Deck Commerce data
     *
     * @param Order $subject
     * @param callable $proceed
     * @return float|mixed|null
     */
    public function aroundGetTracksCollection(Order $subject, callable $proceed)
    {
        if ($subject->hasData('deck_order_tracks_collection')) {
            return $subject->getData('deck_order_tracks_collection');
        } else {
            return $proceed();
        }
    }

    /**
     * Don't show default invoices if Deck Commerce order history is active
     *
     * @param Order $subject
     * @param callable $proceed
     * @return false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundHasInvoices(Order $subject, callable $proceed)
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return $proceed();
        } else {
            return false;
        }
    }

    /**
     * Don't show default creditmemos if Deck Commerce order history is active
     *
     * @param Order $subject
     * @param callable $proceed
     * @return false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundHasCreditmemos(Order $subject, callable $proceed)
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return $proceed();
        } else {
            return false;
        }
    }
}
