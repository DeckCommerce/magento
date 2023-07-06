<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Observer;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Export\Order as DeckOrder;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * AfterOrderSubmit observer
 */
class AfterOrderSubmit implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var DeckOrder
     */
    protected $deckOrder;

    /**
     * @var array
     */
    protected $ignoredOrderStatuses = [
        Order::STATE_CANCELED,
        Order::STATE_HOLDED,
        Order::STATE_COMPLETE,
        Order::STATE_CLOSED
    ];

    /**
     * AfterOrderSubmit constructor.
     * @param DeckHelper $helper
     * @param DeckOrder $deckOrder
     */
    public function __construct(
        DeckHelper $helper,
        DeckOrder $deckOrder
    ) {
        $this->helper    = $helper;
        $this->deckOrder = $deckOrder;
    }

    /**
     * Send order to DeckCommerce once it's placed
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->getOrderSendImmediately()) {
            return;
        }
        $orders = $this->getEventOrders(
            $observer->getEvent()
        );
        if (empty($orders)) {
            return;
        }
        foreach ($orders as $order) {
            $this->processOrder($order);
        }
    }

    /**
     * Get order(s) from event
     *
     * @param Event $event
     * @return array|mixed|null
     */
    protected function getEventOrders(Event $event)
    {
        $order = $event->getData('order');
        if ($order !== null) {
            return [$order];
        }

        return $event->getData('orders');
    }

    /**
     * Send order to Deck Commerce
     *
     * @param OrderInterface $order
     * @return void
     */
    private function processOrder($order)
    {
        /** @var  $order Order */
        $orderId = $order->getEntityId();
        if ($orderId === null) {
            return;
        }
        if (in_array($order->getState(), $this->ignoredOrderStatuses)) {
            return;
        }
        try {
            $this->deckOrder->send($order);

            if ($order->getCustomerId()) {
                $this->helper->cleanOrdersHistoryCache($order->getCustomerId());
            }
        } catch (\Exception $e) {
            return;
        }
    }
}
