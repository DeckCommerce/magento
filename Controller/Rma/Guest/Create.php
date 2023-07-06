<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Rma\Guest;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Rma\Model\Item as RmaItem;
use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use DeckCommerce\Integration\Model\Export\Rma as RmaExport;

/**
 * RMA Create Controller class
 */
class Create extends \Magento\Rma\Controller\Guest\Create implements HttpPostActionInterface
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
     * Create constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Rma\Helper\Data $rmaHelper
     * @param \Magento\Sales\Helper\Guest $salesGuestHelper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param DeckHelper $helper
     * @param RmaExport $rmaExport
     * @param \Magento\Framework\Session\Generic $session
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Rma\Helper\Data $rmaHelper,
        \Magento\Sales\Helper\Guest $salesGuestHelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        DeckHelper $helper,
        RmaExport $rmaExport,
        \Magento\Framework\Session\Generic $session
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $rmaHelper,
            $salesGuestHelper,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultForwardFactory
        );

        $this->helper = $helper;
        $this->rmaExport = $rmaExport;
        $this->_session = $session;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function execute()
    {
        if (!$this->helper->isRmaExportEnabled()) {
            return parent::execute();
        }

        $result = $this->salesGuestHelper->loadValidOrder($this->_request);
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }

        $order = $this->_coreRegistry->registry('current_order');
        $orderId = $order->getId();
        if (!$orderId) {
            return $this->resultRedirectFactory->create()->setPath('sales/order/history');
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
