<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Sales\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Helper\Sales\Reorder as DeckReorderHelper;
use DeckCommerce\Integration\Model\Data\Collection as DeckDataCollection;
use DeckCommerce\Integration\Model\Import\OrderHistory;

/**
 * Order History Block
 */
class History extends \Magento\Sales\Block\Order\History
{

    /**
     * @var string
     */
    protected $_template = 'DeckCommerce_Integration::order/history.phtml';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var array
     */
    protected $orders;

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
     * History constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param OrderHistory $orderHistory
     * @param DeckHelper $helper
     * @param DeckReorderHelper $deckReorderHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        OrderHistory $orderHistory,
        DeckHelper $helper,
        DeckReorderHelper $deckReorderHelper,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->orderHistory = $orderHistory;
        $this->helper = $helper;
        $this->deckReorderHelper = $deckReorderHelper;

        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /**
     * Get Deck API orders instead of default Magento orders
     *
     * @param null $pageNumber
     * @param null $pageSize
     * @return array|bool|DeckDataCollection
     */
    public function getOrders($pageNumber = null, $pageSize = null)
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::getOrders();
        }

        return $this->getDeckApiOrders($pageNumber, $pageSize);
    }

    /**
     * Get orders from Deck
     *
     * @param int $pageNumber
     * @param int $pageSize
     * @return array
     */
    protected function getDeckApiOrders($pageNumber, $pageSize)
    {
        $customerId = $this->_customerSession->getCustomerId();
        if (!$this->orders && $pageNumber && $pageSize) {
            $this->orders = $this->orderHistory->getOrdersHistory($customerId, null, $pageNumber, $pageSize);
        }

        return $this->orders;
    }

    /**
     * Prepare layout
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::_prepareLayout();
        }

        if (!$this->orders) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'sales.order.history.pager',
                ['cacheable' => false]
            );

            $reqParams = $this->_request->getParams();
            $pageNumber = (array_key_exists($pager->getPageVarName(), $reqParams)) ?
                $reqParams[$pager->getPageVarName()] : 1;
            $pageSize = (array_key_exists($pager->getLimitVarName(), $reqParams)) ?
                $reqParams[$pager->getLimitVarName()] : 10;

            $orders = $this->getOrders($pageNumber, $pageSize);
            $orders
                ->setCurPage($pageNumber)
                ->setPageSize($pageSize);

            if ($orders) {
                $pager->setCollection($orders);
                $this->deckReorderHelper->setOrders($orders);
            }

            $this->setChild('pager', $pager);
        }

        return $this;
    }

    /**
     * Get Returns view URL
     *
     * @param object $order
     * @return string
     */
    public function getReturnsUrl($order)
    {
        return $this->getUrl('rma/returns/returns', ['order_id' => $order->getId()]);
    }
}
