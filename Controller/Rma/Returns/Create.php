<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Rma\Returns;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Rma\Model\Item as RmaItem;
use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use DeckCommerce\Integration\Model\Export\Rma as RmaExport;

/**
 * RMA Create Controller class
 */
class Create extends \Magento\Rma\Controller\Returns\Create implements HttpPostActionInterface
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var OrderHistory
     */
    protected $orderHistory;

    /**
     * Core session model
     * @var \Magento\Framework\Session\Generic
     */
    protected $_session;

    /**
     * @var RmaExport
     */
    protected $rmaExport;

    /**
     * @var \Magento\Framework\Message\Factory
     */
    protected $messageFactory;

    /**
     * Create constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param DeckHelper $helper
     * @param OrderHistory $orderHistory
     * @param RmaExport $rmaExport
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Framework\Message\Factory $messageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        DeckHelper $helper,
        OrderHistory $orderHistory,
        RmaExport $rmaExport,
        \Magento\Framework\Session\Generic $session,
        \Magento\Framework\Message\Factory $messageFactory
    ) {
        parent::__construct($context, $coreRegistry);

        $this->helper = $helper;
        $this->orderHistory = $orderHistory;
        $this->_session = $session;
        $this->messageFactory = $messageFactory;
        $this->rmaExport = $rmaExport;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if (!$this->helper->isRmaExportEnabled()) {
            parent::execute();
            return;
        }

        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $this->_redirect($this->_url->getUrl('sales/order/history'));
            return;
        }

        try {
            $order = $this->orderHistory->getOrder($orderId);
        } catch (\Exception $e) {
            $this->_redirect($this->_url->getUrl('sales/order/history'));
            return;
        }

        if ($order && !empty($order->getData())) {
            $this->_coreRegistry->register('current_order', $order);
        }

        $post = $this->getRequest()->getPostValue();
        if ($post && !empty($post['items'])) {
            $this->processSendRequest($post, $orderId);
        }

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Start New Return'));

        if ($block = $this->_view->getLayout()->getBlock('customer.account.link.back')) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        $this->_view->renderLayout();
    }

    /**
     * Send create RMA request
     *
     * @param array $post
     * @param int $orderId
     */
    protected function processSendRequest($post, $orderId)
    {
        try {
            $items = $post['items'];
            foreach ($items as $itemId => $item) {
                if (!isset($item[RmaItem::ORDER_ITEM_ID]) || empty($item[RmaItem::ORDER_ITEM_ID])) {
                    unset($items[$itemId]);
                } elseif (!isset($item[RmaItem::REASON]) || empty($item[RmaItem::REASON])) {
                    $this->_session->setRmaFormData($post);
                    $this->messageManager->addError(
                        __('Reason of SKU: %1 is not selected.', $item['sku'])
                    );
                    $url = $this->_url->getUrl('*/*/create', ['order_id' => $orderId]);
                    $this->getResponse()->setRedirect($this->_redirect->error($url));
                    return;
                }
            }

            if (empty($items)) {
                $this->_session->setRmaFormData($post);
                $this->messageManager->addError(
                    __('Please select items.')
                );
                $url = $this->_url->getUrl('*/*/create', ['order_id' => $orderId]);
                $this->getResponse()->setRedirect($this->_redirect->error($url));
                return;
            }

            $result = $this->rmaExport->send($orderId, $items);
            if (!$result) {
                throw new \Exception('Unable to create return.');
            }

            $this->helper->cleanOrderCache($orderId);

        } catch (\Exception $e) {
            $this->_session->setRmaFormData($post);
            $this->messageManager->addError(
                __('We can\'t create a return. Please try again later.')
            );
            $url = $this->_url->getUrl('*/*/create', ['order_id' => $orderId]);
            $this->getResponse()->setRedirect($this->_redirect->error($url));
            return;
        }

        $this->messageManager->addSuccess(__('Return #%1 for Order: #%2 has been submitted.', $result, $orderId));
        $url = $this->_url->getUrl('*/*/returns', ['order_id' => $orderId]);
        $this->getResponse()->setRedirect($this->_redirect->success($url));
    }
}
