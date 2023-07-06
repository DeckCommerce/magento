<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Plugin\Helper\Rma;

use DeckCommerce\Integration\Helper\Data as HelperData;
use Magento\Framework\Registry;
use Magento\Rma\Helper\Data as RmaHelper;
use Magento\Sales\Model\Order;

/**
 * Rma helper plugin class
 */
class DataPlugin
{

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * DataPlugin constructor.
     *
     * @param HelperData $helper
     * @param Registry $coreRegistry
     */
    public function __construct(
        HelperData $helper,
        Registry $coreRegistry
    ) {
        $this->helper = $helper;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Checks for ability to create RMA
     *
     * @param RmaHelper $subject
     * @param callable $proceed
     * @param int|Order $order
     * @param bool $forceCreate - set yes when you don't need to check config setting (for admin side)
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCanCreateRma(RmaHelper $subject, callable $proceed, $order, $forceCreate = false)
    {
        if (!$this->helper->isRmaExportEnabled()) {
            return $proceed($order, $forceCreate);
        }

        $order = $this->coreRegistry->registry('current_order');

        return (bool) $order->getData('can_return');
    }

    /**
     * Return Deck Commerce order items (if enabled)
     *
     * @param RmaHelper $subject
     * @param callable $proceed
     * @param $orderId
     * @param false $onlyParents
     * @return mixed
     */
    public function aroundGetOrderItems(RmaHelper $subject, callable $proceed, $orderId, $onlyParents = false)
    {
        if (!$this->helper->isRmaExportEnabled()) {
            return $proceed($orderId, $onlyParents);
        }

        /** @var Order $order */
        $order = $this->coreRegistry->registry('current_order');
        if (!$order) {
            return [];
        }

        return $order->getItems();
    }
}
