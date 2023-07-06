<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Rma\Guest;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Export\Rma as RmaExport;

/**
 * RMA View Controller class
 */
class View extends \Magento\Rma\Controller\Guest\View
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var RmaExport
     */
    protected $rmaExport;

    /**
     * View constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Rma\Helper\Data $rmaHelper
     * @param \Magento\Sales\Helper\Guest $salesGuestHelper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param DeckHelper $helper
     * @param RmaExport $rmaExport
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
        RmaExport $rmaExport
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
    }

    /**
     * RMA view page
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|void
     * @throws \DeckCommerce\Integration\Model\Service\Exception\WebapiException
     * @throws \Magento\Framework\Exception\InputException
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

        $entityId = $this->getRequest()->getParam('entity_id');

        $order = $this->_coreRegistry->registry('current_order');
        $orderId = $order->getId();
        if (!$orderId || !$entityId) {
            return $this->resultRedirectFactory->create()->setPath('sales/order/history');
        }

        list($orderId, $rmaId) = explode('_rma_', $entityId);

        $rma = $this->helper->getRmaByIncrementId($order, $entityId);
        if ($rma) {
            $this->_coreRegistry->register('current_rma', $rma);
        } else {
            $this->_redirect('*/*/history');
            return;
        }

        if (!$this->processCancelRma($rmaId, $orderId)) {
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
