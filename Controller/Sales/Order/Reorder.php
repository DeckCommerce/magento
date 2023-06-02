<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Sales\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Sales\Controller\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * Reorder Controller
 */
class Reorder extends \Magento\Sales\Controller\Order\Reorder implements OrderInterface
{
    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * Reorder constructor.
     * @param Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param Registry $registry
     * @param DeckHelper $helper
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        OrderLoaderInterface $orderLoader,
        Registry $registry,
        DeckHelper $helper,
        Cart $cart
    ) {
        $this->helper = $helper;
        $this->cart   = $cart;

        parent::__construct($context, $orderLoader, $registry);
    }

    /**
     * Execute reorder action
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::execute();
        }

        $result = $this->orderLoader->load($this->_request);
        if ($result instanceof ResultInterface) {
            return $result;
        }

        /** @var $order Order */
        $order = $this->_coreRegistry->registry('current_order');

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        foreach ($order->getItems() as $item) {
            try {
                $this->cart->addOrderItem($item);
            } catch (LocalizedException $e) {
                $useNotice = $this->_objectManager->get(Session::class)->getUseNotice(true);
                $useNotice
                        ? $this->messageManager->addNoticeMessage($e->getMessage())
                        : $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/history');
            } catch (\Exception $e) {
                $this->messageManager
                    ->addExceptionMessage($e, __('We can\'t add this item to your shopping cart right now.'));

                return $resultRedirect->setPath('checkout/cart');
            }
        }

        $this->cart->save();

        return $resultRedirect->setPath('checkout/cart');
    }
}
