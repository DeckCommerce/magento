<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Sales\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;

/**
 * PrintShipment Controller
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class PrintShipment extends \Magento\Sales\Controller\Order\PrintShipment
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
     * PrintShipment constructor.
     * @param Context $context
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param \Magento\Framework\Registry $registry
     * @param PageFactory $resultPageFactory
     * @param OrderHistory $orderHistory
     * @param DeckHelper $helper
     */
    public function __construct(
        Context $context,
        OrderViewAuthorizationInterface $orderAuthorization,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        OrderHistory $orderHistory,
        DeckHelper $helper
    ) {

        $this->helper       = $helper;
        $this->orderHistory = $orderHistory;

        parent::__construct($context, $orderAuthorization, $registry, $resultPageFactory);
    }

    /**
     * Print Shipment Action
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::execute();
        }

        $orderId = $this->getRequest()->getParam('order_id');
        $shipmentId = $this->getRequest()->getParam('shipment_id');

        $order = $this->orderHistory->getOrder($orderId);

        $shipment = $this->helper->getOrderShipmentById($order, $shipmentId);

        if ($order && $this->orderAuthorization->canView($order)) {
            $this->_coreRegistry->register('current_order', $order);
            if (isset($shipment)) {
                $this->_coreRegistry->register('current_shipment', $shipment);
            }
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->addHandle('print');
            return $resultPage;
        } else {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            if ($this->_objectManager->get(\Magento\Customer\Model\Session::class)->isLoggedIn()) {
                $resultRedirect->setPath('*/*/history');
            } else {
                $resultRedirect->setPath('sales/guest/form');
            }
            return $resultRedirect;
        }
    }
}
