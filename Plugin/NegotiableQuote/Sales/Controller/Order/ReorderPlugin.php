<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace DeckCommerce\Integration\Plugin\NegotiableQuote\Sales\Controller\Order;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Magento\Sales\Model\Order\Item;
use DeckCommerce\Integration\Helper\Data as HelperData;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Class ReorderPlugin.
 *
 * Is used for customer reorder functionality.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReorderPlugin
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    private $resultFactory;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Reorder
     */
    private $reorder;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * Reorder constructor.
     *
     * @param Cart $cart
     * @param OrderLoaderInterface $orderLoader
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\OrderFactory $salesOrderFactory
     * @param HelperData $helper
     * @param Reorder|null $reorder
     * @param ProductCollectionFactory|null $productCollectionFactory
     */
    public function __construct(
        Cart $cart,
        OrderLoaderInterface $orderLoader,
        Context $context,
        OrderRepositoryInterface $orderRepository,
        AddressRepositoryInterface $addressRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        HelperData $helper,
        Reorder $reorder = null,
        ProductCollectionFactory $productCollectionFactory = null
    ) {
        $this->cart = $cart;
        $this->orderLoader = $orderLoader;
        $this->messageManager = $context->getMessageManager();
        $this->resultFactory = $context->getResultFactory();
        $this->orderRepository = $orderRepository;
        $this->addressRepository = $addressRepository;
        $this->logger = $logger;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->helper = $helper;
        $this->reorder = $reorder ?: ObjectManager::getInstance()->get(Reorder::class);
        $this->productCollectionFactory = $productCollectionFactory ?:
            ObjectManager::getInstance()->get(ProductCollectionFactory::class);
    }

    /**
     * Reorder process for b2b.
     *
     * @param \Magento\Sales\Controller\Order\Reorder $subject
     * @param \Closure $proceed
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Exception
     */
    public function aroundExecute(
        \Magento\Sales\Controller\Order\Reorder $subject,
        \Closure $proceed
    ) {
        $orderId = $subject->getRequest()->getParam('order_id');
        $replaceCart = $subject->getRequest()->getParam('replace_cart');

        if (!$this->reorder->canReorder($orderId)) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $result->forward('noroute');
        }

        if ($orderId !== null && $replaceCart !== null) {
            $this->cart->truncate();
        }

        $result = $this->orderLoader->load($subject->getRequest());

        if ($result instanceof ResultInterface) {
            return $result;
        }

        if (!$this->helper->isOrderHistoryEnabled()) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);
        } else {
            $order = $this->salesOrderFactory->create()->loadByIncrementId($orderId);
        }

        $this->addItemsToCart($this->cart, $order->getItemsCollection(), (string)$order->getStoreId());
        $this->cart->save();

        if ($order->getShippingAddress()) {
            $this->cart->getQuote()->getShippingAddress()->setShippingMethod($order->getShippingMethod());

            $this->orderAddressToQuoteAddress(
                $this->cart->getQuote()->getShippingAddress(),
                $order->getShippingAddress()
            );
        }

        $this->orderAddressToQuoteAddress(
            $this->cart->getQuote()->getBillingAddress(),
            $order->getBillingAddress()
        );

        $this->cart->getQuote()->getPayment()->setMethod($order->getPayment()->getMethod())->save();

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('checkout/cart');
    }

    /**
     * Adds order items to cart.
     *
     * @param Cart $cart
     * @param ItemCollection $orderItems
     * @param string $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    private function addItemsToCart(Cart $cart, ItemCollection $orderItems, string $storeId): void
    {
        $orderItemProductIds = [];
        /** @var \Magento\Sales\Model\Order\Item[] $orderItemsByProductId */
        $orderItemsByProductId = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($orderItems->getItems() as $item) {
            if ($item->getParentItem() === null) {
                $orderItemProductIds[] = $item->getProductId();
                $orderItemsByProductId[$item->getProductId()][$item->getId()] = $item;
            }
        }

        $products = $this->getOrderProducts($storeId, $orderItemProductIds);

        // compare founded sku and throw an error if some sku not exists
        $productsNotFound = array_diff($orderItemProductIds, array_keys($products));
        if (!empty($productsNotFound)) {
            foreach ($productsNotFound as $productId) {
                /** @var \Magento\Sales\Model\Order\Item $orderItemProductNotFound */
                $orderItemProductNotFound = $orderItemsByProductId[$productId];
                $this->addSkuNotFoundError(current($orderItemProductNotFound));
            }
        }

        foreach ($orderItemsByProductId as $productId => $orderItems) {
            if (!isset($products[$productId])) {
                continue;
            }
            $product = $products[$productId];
            foreach ($orderItems as $orderItem) {
                if ($product->isDisabled()) {
                    $this->addSkuNotFoundError($orderItem);
                    continue;
                }
                $this->addItemToCart($orderItem, $cart, clone $product);
            }
        }
    }

    /**
     * Get order products by store id and order item product ids.
     *
     * @param string $storeId
     * @param int[] $orderItemProductIds
     * @return Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrderProducts(string $storeId, array $orderItemProductIds): array
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStore($storeId)
            ->addIdFilter($orderItemProductIds)
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');

        return $collection->getItems();
    }

    /**
     * Add sku not found error message
     *
     * @param Item $orderItem
     * @return void
     */
    private function addSkuNotFoundError(Item $orderItem): void
    {
        $this->messageManager->addError(__('Product with SKU %1 not found', $orderItem->getSku()));
    }

    /**
     * Adds order item product to cart.
     *
     * @param OrderItemInterface $orderItem
     * @param Cart $cart
     * @param ProductInterface $product
     */
    private function addItemToCart(OrderItemInterface $orderItem, Cart $cart, ProductInterface $product)
    {
        $info = $orderItem->getProductOptionByCode('info_buyRequest');
        if (!$info) {
            $info = [];
        }
        $info = new \Magento\Framework\DataObject($info);
        $info->setQty($orderItem->getQtyOrdered());

        try {
            $cart->addProduct($product, $info);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager
                ->addErrorMessage(__('Product with SKU %1 not found in catalog', $orderItem->getSku()));
            $this->logger->critical($e);
        }
    }

    /**
     * Populates quote address based on order address info.
     *
     * @param AddressInterface $quoteAddress
     * @param OrderAddressInterface $orderAddress
     * @return void
     */
    private function orderAddressToQuoteAddress(AddressInterface $quoteAddress, OrderAddressInterface $orderAddress)
    {
        try {
            $addressData = $this->addressRepository->getById($orderAddress->getCustomerAddressId());
            $quoteAddress->importCustomerAddressData($addressData);
            $quoteAddress->save();
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
        } catch (NoSuchEntityException $e) {
            // If no such entity, skip
        }
    }
}
