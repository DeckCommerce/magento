<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Sales\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order\Config;
use Magento\Store\Model\StoreManagerInterface;
use DeckCommerce\Integration\Helper\Sales\Reorder as DeckReorderHelper;

/**
 * Recent orders Block
 */
class Recent extends \Magento\Sales\Block\Order\Recent
{
    /**
     * @var OrderHistory
     */
    protected $orderHistory;

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var DeckReorderHelper
     */
    protected $deckReorderHelper;

    /**
     * Recent constructor.
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param Session $customerSession
     * @param Config $orderConfig
     * @param OrderHistory $orderHistory
     * @param DeckHelper $helper
     * @param DeckReorderHelper $deckReorderHelper
     * @param array $data
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        Context $context,
        CollectionFactory $orderCollectionFactory,
        Session $customerSession,
        Config $orderConfig,
        OrderHistory $orderHistory,
        DeckHelper $helper,
        DeckReorderHelper $deckReorderHelper,
        array $data = [],
        StoreManagerInterface $storeManager = null
    ) {
        $this->orderHistory = $orderHistory;
        $this->helper = $helper;
        $this->deckReorderHelper = $deckReorderHelper;
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data, $storeManager);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            parent::_construct();
            return;
        }

        if ($this->hasData('template')) {
            $this->setTemplate($this->getData('template'));
        }
        $this->getRecentOrders();
    }

    /**
     * Get recently placed orders. By default, they will be limited by 5.
     */
    private function getRecentOrders()
    {
        $customerId = $this->_customerSession->getCustomerId();

        $orders = $this->orderHistory->getOrdersHistory($customerId, null, 1, self::ORDER_LIMIT);
        $this->setOrders($orders);
        $this->deckReorderHelper->setOrders($orders);
    }

    /**
     * Get order view URL
     *
     * @param object $order
     * @return string
     */
    public function getReturnsUrl($order)
    {
        return $this->getUrl('rma/returns/returns', ['order_id' => $order->getId()]);
    }
}
