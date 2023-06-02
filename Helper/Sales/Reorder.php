<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Helper\Sales;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Data\Collection as DeckDataCollection;
use Magento\Sales\Model\Order;

/**
 * Reorder helper
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Reorder extends \Magento\Sales\Helper\Reorder
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Data\Collection
     */
    protected $orders;

    /**
     * @var  \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * Reorder constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param DeckHelper $helper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        DeckHelper $helper,
        \Magento\Framework\Registry $registry
    ) {
        $this->helper = $helper;
        $this->registry = $registry;

        parent::__construct($context, $customerSession, $orderRepository);
    }

    /**
     * Check is it possible to reorder
     *
     * @param int $orderId
     * @return bool
     */
    public function canReorder($orderId)
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::canReorder($orderId);
        }

        if ($this->registry->registry('current_order')) {
            $order = $this->registry->registry('current_order');
        } elseif ($this->order) {
            $order = $this->order;
        } else {
            return true;
        }
        if (!$this->isAllowed($order->getStore())) {
            return false;
        }
        if ($this->customerSession->isLoggedIn()) {
            return $order->canReorder();
        } else {
            return true;
        }
    }

    /**
     * Set Deck Commerce orders to be available in Reorder
     *
     * @param DeckDataCollection $orders
     */
    public function setOrders($orders)
    {
        $this->orders = $orders;
    }

    /**
     * @param Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
}
