<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Sales\Guest;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\Guest\OrderLoader;
use Magento\Sales\Controller\Guest\OrderViewAuthorization;

/**
 * PrintShipment Controller
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class PrintShipment extends \Magento\Sales\Controller\Guest\PrintShipment
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
     * @param OrderViewAuthorization $orderAuthorization
     * @param \Magento\Framework\Registry $registry
     * @param PageFactory $resultPageFactory
     * @param OrderLoader $orderLoader
     * @param OrderHistory $orderHistory
     * @param DeckHelper $helper
     */
    public function __construct(
        Context $context,
        OrderViewAuthorization $orderAuthorization,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        OrderLoader $orderLoader,
        OrderHistory $orderHistory,
        DeckHelper $helper
    ) {

        $this->helper = $helper;
        $this->orderHistory = $orderHistory;

        parent::__construct($context, $orderAuthorization, $registry, $resultPageFactory, $orderLoader);
    }

    /**
     * Execute guest print shipment action
     */
    public function execute()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::execute();
        }

        $result = $this->orderLoader->load($this->_request);
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }

        $order = $this->_coreRegistry->registry('current_order');
        $shipmentId = (int)$this->getRequest()->getParam('shipment_id');
        if ($shipmentId) {
            $shipment = $this->helper->getOrderShipmentById($order, $shipmentId);
        }

        if ($this->orderAuthorization->canView($order)) {
            if (isset($shipment)) {
                $this->_coreRegistry->register('current_shipment', $shipment);
            }
            return $this->resultPageFactory->create()->addHandle('print');
        } else {
            return $this->resultRedirectFactory->create()->setPath('sales/guest/form');
        }
    }
}
