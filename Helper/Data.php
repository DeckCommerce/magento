<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Helper;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Logger\Logger as DeckLogger;
use DeckCommerce\Integration\Model\Data\Collection;
use DeckCommerce\Integration\Model\ResourceModel\MethodMap\CollectionFactory as MethodMapCollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class Helper Data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends Config
{

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var CarrierFactory
     */
    protected $carrierFactory;

    /**
     * @var StockResolverInterface
     */
    protected $stockResolver;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var MethodMapCollectionFactory
     */
    protected $methodMapCollectionFactory;

    /**
     * @var DefaultSourceProviderInterface
     */
    protected $defaultSourceProvider;

    /**
     * @var GetStockItemConfigurationInterface
     */
    protected $getStockItemConfiguration;

    /**
     * @var DeckLogger
     */
    protected $deckLogger;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepositoryInterface;

    /**
     * @var null | int
     */
    protected $sizeAttributeId = null;

    /**
     * @var null | array
     */
    protected $methodsMap = null;

    /**
     * @var ModuleManager|null
     */
    protected $moduleManager = null;

    /**
     * Data constructor.
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param CarrierFactory $carrierFactory
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param MethodMapCollectionFactory $methodMapCollectionFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param DeckLogger $deckLogger
     * @param Json $jsonSerializer
     * @param AttributeRepositoryInterface $attributeRepositoryInterface
     * @param ModuleManager $moduleManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        CacheInterface $cache,
        SerializerInterface $serializer,
        CarrierFactory $carrierFactory,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager,
        MethodMapCollectionFactory $methodMapCollectionFactory,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        DeckLogger $deckLogger,
        Json $jsonSerializer,
        AttributeRepositoryInterface $attributeRepositoryInterface,
        ModuleManager $moduleManager
    ) {
        $this->cache                        = $cache;
        $this->serializer                   = $serializer;
        $this->carrierFactory               = $carrierFactory;
        $this->stockResolver                = $stockResolver;
        $this->storeManager                 = $storeManager;
        $this->methodMapCollectionFactory   = $methodMapCollectionFactory;
        $this->defaultSourceProvider        = $defaultSourceProvider;
        $this->getStockItemConfiguration    = $getStockItemConfiguration;
        $this->deckLogger                   = $deckLogger;
        $this->jsonSerializer               = $jsonSerializer;
        $this->attributeRepositoryInterface = $attributeRepositoryInterface;
        $this->moduleManager                = $moduleManager;
        parent::__construct($context, $encryptor);
    }

    /**
     * Get Product Size Attribute Id
     *
     * @return int|null
     */
    public function getSizeAttributeId()
    {
        if ($this->sizeAttributeId === null) {
            try {
                $sizeAttr = $this->attributeRepositoryInterface->get(Product::ENTITY, 'size');
                $this->sizeAttributeId = (int) $sizeAttr->getAttributeId();
            } catch (NoSuchEntityException $e) {
                return $this->sizeAttributeId;
            }
        }

        return $this->sizeAttributeId;
    }

    /**
     * Get shipping methods map: magento_shipping_method_code => deck_method_name
     * Example: fedex_FEDEX_2_DAY => FedEx Two Day
     *
     * @return array
     */
    public function getShippingMethodsMap()
    {
        if ($this->methodsMap === null) {
            $this->methodsMap = [];
            $collection = $this->methodMapCollectionFactory->create();
            $collection->addFieldToFilter('is_enabled', 1);
            foreach ($collection as $mapItem) {
                $this->methodsMap[$mapItem->getMethod()] = $mapItem->getDeckMethodName();
            }
        }

        return $this->methodsMap;
    }

    /**
     * Get Magento mapped shipping method code by Deck Commerce shipping method
     *
     * @param string $deckShippingMethod
     * @return false|int|string
     */
    public function getMappedShippingMethod($deckShippingMethod)
    {
        return array_search($deckShippingMethod, $this->getShippingMethodsMap());
    }

    /**
     * Get Deck Commerce shipping method by Magento shipping method (to be used in Order export)
     * If Deck Commerce method can't be found in the mapping table then the default shipping method will be used
     *
     * @param string $magentoShippingMethod
     * @param string $scopeType
     * @return mixed|string
     */
    public function getMappedDeckShippingMethod($magentoShippingMethod, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getShippingMethodsMap()[$magentoShippingMethod] ?? $this->getDefaultShippingMethod($scopeType);
    }

    /**
     * Get Deck Commerce API token
     *
     * @param string $apiName
     * @param string $currentTimestamp
     * @param string $scopeType
     * @return string
     */
    public function getApiToken($apiName, $currentTimestamp, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $key = strtolower($apiName) . $currentTimestamp . $this->getSiteApiKey($scopeType);
        $hash = hash('sha256', $key, true);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return strtoupper(implode(unpack("H*", $hash)));
    }

    /**
     * Get full web API URL
     *
     * @param string $apiName
     * @param string $scopeType
     * @return string
     */
    public function getFullWebApiUrl($apiName, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return implode('/', [$this->getWebApiUrl($scopeType), strtolower($apiName)]);
    }

    /**
     * Check whether current action is 'checkout_cart_add' on PDP
     *
     * @return bool
     */
    public function isPdpAddToCartAction()
    {
        if (!$this->_getRequest()) {
            return false;
        }
        if ($this->isPdpInventoryCheckEnabled() && $this->_getRequest()->getFullActionName() === 'checkout_cart_add') {
            return true;
        }

        return false;
    }

    /**
     * Is inventory check active on current page (PDP, Cart or Checkout)
     *
     * @return bool
     */
    public function isActiveOnCurrentPage()
    {
        $actionName = $this->_getRequest()->getFullActionName();
        if ($this->isPdpAddToCartAction()) {
            return true;
        }

        if (in_array($actionName, ['checkout_cart_index', 'checkout_sidebar_updateItemQty',
                                   'checkout_cart_updateItemQty', 'sales_order_reorder'])
            && $this->isCartInventoryCheckEnabled()
        ) {
            return true;
        }

        if ($actionName == '__') {
            $actionName = $this->_getRequest()->getPathInfo();
        }

        if (in_array($actionName, ['checkout_index_index', '/rest/default/V1/carts/mine/payment-information'])
            && $this->isCheckoutInventoryCheckEnabled()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get order locate
     *
     * @param OrderInterface $order
     * @return mixed
     */
    public function getOrderLocale(OrderInterface $order)
    {
        return $this->scopeConfig->getValue(
            DirectoryHelper::XML_PATH_DEFAULT_LOCALE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $order->getStoreId()
        );
    }

    /**
     * Format price
     *
     * @param float $totalAmount
     * @return string
     */
    public function formatPrice($totalAmount)
    {
        if (!$totalAmount) {
            $totalAmount = 0;
        }
        return sprintf("%s", number_format($totalAmount, 2, '.', ''));
    }

    /**
     * Cache $object->$method values
     * Used to cache Deck Commerce API responses
     *
     * @param $object
     * @param string $method
     * @param array $params
     * @param string $cacheLifetime
     * @return array|bool|float|int|mixed|string|null
     */
    public function getCachedData(
        $object,
        $method,
        $params,
        $cacheLifetime
    ) {
        try {
            $storeId = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            $storeId = 0;
        }
        $paramsHash = hash('sha256', $this->jsonEncode($params));
        $cacheId = sprintf('DECK_%s_%s_%s', $object::API_TYPE, $storeId, $paramsHash);
        if ($cacheLifetime && ($result = $this->cache->load($cacheId))) {
            return $this->serializer->unserialize($result);
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $result = call_user_func_array([$object, $method], $params);

        if ($cacheLifetime) {
            $this->cache->save(
                $this->serializer->serialize($result),
                $cacheId,
                $this->_getCacheTags($method, $params),
                $cacheLifetime
            );
        }

        return $result;
    }

    /**
     * Get cache tags
     *
     * @param string $method
     * @param array $params
     * @return string[]
     */
    protected function _getCacheTags($method, $params)
    {
        $tags = ['DECK_API'];
        if ($method === 'getApiOrdersHistory') {
            //1st parameter is customer ID
            $customerApiCacheTag = isset($params[0])
                ? ("DECK_API_{$method}_CUSTOMER_" . $params[0])
                : "DECK_API_{$method}_CUSTOMER_0";

            $tags[] = $customerApiCacheTag;
            $tags[] = 'DECK_API_' . $method . implode('_', $params);
        }

        return $tags;
    }

    /**
     * Clean Magento cache by tag
     *
     * @param $cacheTag
     */
    public function cleanCacheByTag($cacheTag)
    {
        $this->cache->clean([$cacheTag]);
    }

    /**
     * Clean order history cache
     *
     * @param $orderNumber
     */
    public function cleanOrderCache($orderNumber)
    {
        $cacheTag = "DECK_API_getApiOrdersHistory_{$orderNumber}_1_1";
        $this->cleanCacheByTag($cacheTag);
    }

    /**
     * Clean orders history cache for customer (used to refresh orders list after placement of new order)
     *
     * @param $customerId
     */
    public function cleanOrdersHistoryCache($customerId)
    {
        $cacheTag = 'DECK_API_getApiOrdersHistory_CUSTOMER_' . $customerId;
        $this->cleanCacheByTag($cacheTag);
    }

    /**
     * Get website code by Id or current website
     *
     * @param null $scopeId
     * @return string|null
     */
    public function getWebsiteCode($scopeId = null)
    {
        try {
            return null === $scopeId
                ? $this->storeManager->getWebsite()->getCode()
                : $this->storeManager->getWebsite($scopeId)->getCode();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get stock ID for website
     *
     * @param null $websiteCode
     * @param null $scopeId
     * @return int|null
     */
    public function getStockId($websiteCode = null, $scopeId = null)
    {
        if ($websiteCode === null) {
            $websiteCode = $this->getWebsiteCode($scopeId);
        }

        try {
            return $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)->getStockId();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check whether product can be backordered
     *
     * @param string $websiteCode
     * @param string $sku
     * @return bool
     */
    public function canBackorder($websiteCode, $sku)
    {
        try {
            $stockId = $this->getStockId($websiteCode);
            if ($stockId === null) {
                return false;
            }
            $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
            return ($stockItemConfiguration->getBackorders() !== StockItemConfigurationInterface::BACKORDERS_NO);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get carrier title by carrier code like: "Federal Express" by code "fedex"
     *
     * @param string $carrierCode
     * @return mixed
     */
    public function getCarrierTitleByCode($carrierCode)
    {
        return $this->scopeConfig->getValue(
            'carriers/' . $carrierCode . '/title',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Magento carrier by code
     *
     * @param string $carrierCode
     * @return bool|\Magento\Shipping\Model\Carrier\AbstractCarrier
     */
    public function getCarrierByCode($carrierCode)
    {
        return $this->carrierFactory->create($carrierCode);
    }

    /**
     * Get Shipping method Carrier Title and Method Title by it's codes
     * like: "FedEx - FedEx Two Day" by codes "fedex" and "FEDEX_2_DAY"
     *
     * @param string $carrierCode
     * @param string $methodCode
     * @return mixed|string
     */
    public function getMethodCarrierAndTitleByCodes($carrierCode, $methodCode)
    {
        $carrier = $this->getCarrierByCode($carrierCode);
        $carrierTitle = $carrierCode;
        $methodTitle  = $methodCode;
        if ($carrier) {
            $methods      = $carrier->getAllowedMethods();
            $carrierTitle = $carrier->getConfigData('title') ?: $carrierCode;
            $methodTitle  = $methods[$methodCode] ?? $methodCode;
        }

        return sprintf('%s - %s', $carrierTitle, $methodTitle);
    }

    /**
     * Get Order shipment by id built from Deck Commerce data
     *
     * @param OrderInterface $order
     * @param int $shipmentId
     * @return false|mixed
     */
    public function getOrderShipmentById($order, $shipmentId)
    {
        if ($order && $shipmentId) {
            foreach ($order->getShipmentsCollection() as $orderShipment) {
                if ($orderShipment->getId() == $shipmentId) {
                    return $orderShipment;
                }
            }
        }

        return false;
    }

    /**
     * Get inventory stock source code to be used for inventory import and checking inventory
     * Used "default" source
     *
     * @return string
     */
    public function getInventorySourceCode()
    {
        return $this->defaultSourceProvider->getCode();
    }

    /**
     * Get Inventory type: "full" or "delta" by type value in the inventory log table
     *
     * @param int $inventoryLogType
     * @return string
     */
    public function getInventoryTypeByLogType($inventoryLogType)
    {
        return $inventoryLogType ? self::INVENTORY_TYPE_DELTA : DeckHelper::INVENTORY_TYPE_FULL;
    }

    /**
     * Json serialize message if it's not a string
     *
     * @param mixed $message
     * @return bool|string
     */
    protected function getSerializeLogMessage($message)
    {
        try {
            if (!is_string($message)) {
                return $this->jsonEncode($message);
            }
            return $message;
        } catch (\InvalidArgumentException $e) {
            return 'Unable to serialize log.';
        }
    }

    /**
     * Add inventory check log
     *
     * @param string $title
     * @param mixed $message
     * @return void
     */
    public function addInventoryLog($title, $message)
    {
        if (!$this->useInventoryCheckDebug()) {
            return;
        }
        $message = "==> INVENTORY CHECK: {$title}\n" . $this->getSerializeLogMessage($message);

        $this->deckLogger->info($message);
    }

    /**
     * Add order export log
     *
     * @param string $title
     * @param mixed $message
     * @return void
     */
    public function addOrderExportLog($title, $message)
    {
        if (!$this->useOrderExportDebug()) {
            return;
        }
        $message = "==> ORDER EXPORT: {$title}\n" . $this->getSerializeLogMessage($message);

        $this->deckLogger->info($message);
    }

    /**
     * Add RMA export log
     *
     * @param string $title
     * @param mixed $message
     * @param false $isCancel
     * @return void
     */
    public function addRmaExportLog($title, $message, $isCancel = false)
    {
        if (!$this->useRmaExportDebug()) {
            return;
        }

        $action = $isCancel ? 'CANCEL' : 'EXPORT';

        $message = "==> RMA {$action}: {$title}\n" . $this->getSerializeLogMessage($message);

        $this->deckLogger->info($message);
    }

    /**
     * Add order history log
     *
     * @param string $title
     * @param mixed $message
     * @return void
     */
    public function addOrderHistoryLog($title, $message)
    {
        if (!$this->useOrderHistoryDebug()) {
            return;
        }
        $message = "==> ORDER HISTORY: {$title}\n" . $this->getSerializeLogMessage($message);

        $this->deckLogger->info($message);
    }

    /**
     * Json encode data
     * Return empty string on exception
     *
     * @param mixed $data
     * @return bool|string
     */
    public function jsonEncode($data)
    {
        try {
            return $this->jsonSerializer->serialize($data);
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * Decode json data
     * Return false on exception
     *
     * @param $jsonData
     * @return mixed|array|bool|int|float|string|null
     */
    public function jsonDecode($jsonData)
    {
        try {
            return $this->jsonSerializer->unserialize($jsonData);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Get Rma model by increment id
     *
     * @param $order
     * @param $rmaIncrementId
     * @return \Magento\Framework\DataObject|null
     */
    public function getRmaByIncrementId($order, $rmaIncrementId)
    {
        /** @var Collection $rmaCollection */
        $rmaCollection = $order->getData('deck_rma_collection');
        return $rmaCollection->getItemById($rmaIncrementId);
    }

    /**
     * Check if RMA module installed/enabled
     *
     * @return bool
     */
    public function isRmaModuleEnabled()
    {
        return $this->_moduleManager->isEnabled('Magento_Rma');
    }

    /**
     * Get json unserialized order payment methods mapping
     *
     * @param string $scopeType
     * @return mixed|array|bool|int|float|string|null
     */
    public function getPaymentMethodsMapping($scopeType = ScopeInterface::SCOPE_STORE)
    {
        $mappingJson = $this->getPaymentMethodsMappingJson($scopeType);
        if ($mappingJson) {
            $mapping = $this->jsonDecode($mappingJson);
            if (is_array($mapping)) {
                return $mapping;
            }
        }

        return [];
    }

    /**
     * Get DeckCommerce application order URL by the Magento order increment ID
     *
     * @param string $orderIncrementId
     * @return string
     */
    public function getDeckCommerceOrderUrl($orderIncrementId)
    {
        $apiUrl = $this->getWebApiUrl();
        return str_replace('api', 'app', $apiUrl) . '/OMS/' . $orderIncrementId;
    }
}
