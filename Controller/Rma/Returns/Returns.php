<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Rma\Returns;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use Magento\Customer\Model\Session;

/**
 * RMA Returns Controller class
 */
class Returns extends \Magento\Rma\Controller\Returns\Returns
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
     * Returns constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param Session $session
     * @param DeckHelper $helper
     * @param OrderHistory $orderHistory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        Session $session,
        DeckHelper $helper,
        OrderHistory $orderHistory
    ) {
        parent::__construct($context, $coreRegistry);

        $this->session = $session;
        $this->helper = $helper;
        $this->orderHistory = $orderHistory;
    }

    /**
     * @return false|void|null
     */
    public function execute()
    {
        if (!$this->helper->isRmaExportEnabled()) {
            return parent::execute();
        }

        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId || !$this->_isEnabledOnFront()) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $this->_forward('noroute');
            return false;
        }

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
            $this->_redirect('sales/order/history');
            return;
        }

        $this->_view->loadLayout();
        $layout = $this->_view->getLayout();

        if ($navigationBlock = $layout->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('sales/order/history');
        }

        $this->_view->renderLayout();
    }
}
