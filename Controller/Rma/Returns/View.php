<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Rma\Returns;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Export\Rma as RmaExport;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use Magento\Customer\Model\Session;

/**
 * RMA View Controller class
 */
class View extends \Magento\Rma\Controller\Returns\View
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var OrderHistory
     */
    protected $orderHistory;

    /**
     * @var RmaExport
     */
    protected $rmaExport;

    /**
     * View constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param Session $session
     * @param DeckHelper $helper
     * @param OrderHistory $orderHistory
     * @param RmaExport $rmaExport
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        Session $session,
        DeckHelper $helper,
        OrderHistory $orderHistory,
        RmaExport $rmaExport
    ) {
        parent::__construct($context, $coreRegistry);

        $this->session = $session;
        $this->helper = $helper;
        $this->orderHistory = $orderHistory;
        $this->rmaExport = $rmaExport;
    }

    /**
     * RMA view page
     *
     * @throws \DeckCommerce\Integration\Model\Service\Exception\WebapiException
     */
    public function execute()
    {
        if (!$this->helper->isRmaExportEnabled()) {
            parent::execute();
            return;
        }

        $entityId = $this->getRequest()->getParam('entity_id');
        if (!$entityId || !$this->_isEnabledOnFront()) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $this->_forward('noroute');
            return;
        }

        list($orderId, $rmaId) = explode('_rma_', $entityId);

        try {
            $order = $this->orderHistory->getOrder($orderId);
        } catch (\Exception $e) {
            $this->_redirect($this->_url->getUrl('sales/order/history'));
            return;
        }

        $customerId = $this->session->getCustomerId();
        if ($order &&
            !empty($order->getData()) &&
            $order->getCustomerId() &&
            $order->getCustomerId() == $customerId
        ) {
            $this->_coreRegistry->register('current_order', $order);
        } else {
            $this->_redirect('*/*/history');
            return;
        }

        if (!$this->processCancelRma($rmaId, $orderId)) {
            return;
        }

        $rma = $this->helper->getRmaByIncrementId($order, $entityId);
        if ($rma) {
            $this->_coreRegistry->register('current_rma', $rma);
        } else {
            $this->_redirect('*/*/history');
            return;
        }

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(
            __('Return #%1', $this->_coreRegistry->registry('current_rma')->getIncrementId())
        );

        $this->_view->renderLayout();
    }

    /**
     * Cancel RMA by request
     *
     * @param string $rmaId
     * @param string $orderId
     * @return bool
     * @throws \DeckCommerce\Integration\Model\Service\Exception\WebapiException
     */
    protected function processCancelRma($rmaId, $orderId)
    {
        if ($this->getRequest()->getParam('cancel') && $rmaId) {
            $result = $this->rmaExport->cancel($rmaId);
            if (!$result) {
                $this->messageManager->addError(
                    __('We can\'t cancel a return. Please try again later.')
                );
                $url = $this->_url->getUrl('sales/order/view', ['order_id' => $orderId]);
                $this->getResponse()->setRedirect($this->_redirect->error($url));
                return false;
            }

            $this->helper->cleanOrderCache($orderId);

            $this->messageManager->addSuccess(__('Return #%1 for Order: #%2 has been canceled.', $rmaId, $orderId));
            $url = $this->_url->getUrl('*/*/returns', ['order_id' => $orderId]);
            $this->getResponse()->setRedirect($this->_redirect->success($url));
            return false;
        }

        return true;
    }
}
