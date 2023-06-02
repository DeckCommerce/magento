<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Sales\AbstractController;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Magento\Sales\Model\OrderFactory;

/**
 * OrderLoader Controller
 */
class OrderLoader extends \Magento\Sales\Controller\AbstractController\OrderLoader
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
     * OrderLoader constructor.
     * @param OrderFactory $orderFactory
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param Registry $registry
     * @param UrlInterface $url
     * @param ForwardFactory $resultForwardFactory
     * @param RedirectFactory $redirectFactory
     * @param OrderHistory $orderHistory
     * @param DeckHelper $helper
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderViewAuthorizationInterface $orderAuthorization,
        Registry $registry,
        UrlInterface $url,
        ForwardFactory $resultForwardFactory,
        RedirectFactory $redirectFactory,
        OrderHistory $orderHistory,
        DeckHelper $helper
    ) {
        $this->helper       = $helper;
        $this->orderHistory = $orderHistory;

        parent::__construct(
            $orderFactory,
            $orderAuthorization,
            $registry,
            $url,
            $resultForwardFactory,
            $redirectFactory
        );
    }

    /**
     * Build Magento order from Deck Commerce API data
     *
     * @param RequestInterface $request
     * @return bool|Forward|Redirect
     */
    public function load(RequestInterface $request)
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::load($request);
        }

        $orderId = $request->getParam('order_id');
        if (!$orderId) {
            /** @var Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        try {
            $order = $this->orderHistory->getOrder($orderId);
        } catch (\Exception $e) {
            /** @var Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        if ($order && !empty($order->getData())) {
            $this->registry->register('current_order', $order);
            return true;
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->redirectFactory->create();
        return $resultRedirect->setUrl($this->url->getUrl('*/*/history'));
    }
}
