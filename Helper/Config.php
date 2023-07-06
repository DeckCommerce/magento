<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Config Helper
 */
class Config extends AbstractHelper
{
    const CONFIG_IS_ENABLED   = 'deck_commerce_general/general/enabled';
    const CONFIG_WEB_API_URL  = 'deck_commerce_general/general/web_api_url';
    const CONFIG_SITE_CODE    = 'deck_commerce_general/general/site_code';
    const CONFIG_SITE_API_KEY = 'deck_commerce_general/general/site_api_key';

    const INVENTORY_ENABLED              = 'deck_commerce_inventory/inventory_check/enabled';
    const INVENTORY_API_NAME             = 'deck_commerce_inventory/inventory_check/api_name';
    const INVENTORY_FEED_NAME            = 'deck_commerce_inventory/inventory_check/feed_name';
    const INVENTORY_CHECK_PDP            = 'deck_commerce_inventory/inventory_check/check_inventory_on_pdp';
    const INVENTORY_CHECK_CART           = 'deck_commerce_inventory/inventory_check/check_inventory_on_cart';
    const INVENTORY_CHECK_CHECKOUT       = 'deck_commerce_inventory/inventory_check/check_inventory_on_checkout';
    const INVENTORY_CHECK_CACHE_LIFETIME = 'deck_commerce_inventory/inventory_check/cache_lifetime';
    const INVENTORY_CHECK_DEBUG          = 'deck_commerce_inventory/inventory_check/debug';

    const INVENTORY_TYPE_FULL                 = 'full';
    const INVENTORY_TYPE_DELTA                = 'delta';
    const INVENTORY_IMPORT_ENABLED            = 'deck_commerce_inventory/%s_inventory_import/enabled';
    const INVENTORY_IMPORT_SOURCE_FILE_PREFIX = 'deck_commerce_inventory/%s_inventory_import/source_file_prefix';
    const INVENTORY_IMPORT_SFTP_DIR           = 'deck_commerce_inventory/%s_inventory_import/sftp_directory';
    const INVENTORY_IMPORT_SFTP_HOST          = 'deck_commerce_inventory/%s_inventory_import/sftp_host';
    const INVENTORY_IMPORT_SFTP_USERNAME      = 'deck_commerce_inventory/%s_inventory_import/sftp_username';
    const INVENTORY_IMPORT_SFTP_PASSWORD      = 'deck_commerce_inventory/%s_inventory_import/sftp_password';
    const INVENTORY_IMPORT_HISTORY_DIRECTORY  = 'deck_commerce_inventory/%s_inventory_import/history_directory';
    const INVENTORY_IMPORT_LOGS_LIFETIME      = 'deck_commerce_inventory/%s_inventory_import/logs_lifetime';

    const ORDER_ENABLED                     = 'deck_commerce_sales/order/enabled';
    const ORDER_API_NAME                    = 'deck_commerce_sales/order/api_name';
    const ORDER_DEFAULT_METHOD              = 'deck_commerce_sales/order/default_method';
    const ORDER_UPC_ATTRIBUTE_CODE          = 'deck_commerce_sales/order/upc_attribute';
    const ORDER_SEND_IMMEDIATELY            = 'deck_commerce_sales/order/send_immediately';
    const ORDER_USE_PAYMENT_METHODS_MAPPING = 'deck_commerce_sales/order/use_payment_methods_mapping';
    const ORDER_PAYMENT_METHODS_MAPPING     = 'deck_commerce_sales/order/payment_methods_mapping';
    const ORDER_USE_RETAIL_DELIVERY_TAX     = 'deck_commerce_sales/order/use_retail_delivery_tax';
    const ORDER_RETAIL_DELIVERY_TAX_AMOUNT  = 'deck_commerce_sales/order/retail_delivery_tax_amount';
    const ORDER_DEBUG                       = 'deck_commerce_sales/order/debug';

    const ORDER_HISTORY_ENABLED        = 'deck_commerce_sales/order_history/enabled';
    const ORDER_HISTORY_API_NAME       = 'deck_commerce_sales/order_history/api_name';
    const ORDER_HISTORY_CACHE_LIFETIME = 'deck_commerce_sales/order_history/cache_lifetime';
    const ORDER_HISTORY_DEBUG          = 'deck_commerce_sales/order_history/debug';

    const RMA_ENABLED                  = 'deck_commerce_sales/rma/enabled';
    const RMA_API_NAME                 = 'deck_commerce_sales/rma/api_name';
    const RMA_DEFAULT_TYPE             = 'deck_commerce_sales/rma/default_type';
    const RMA_DEBUG                    = 'deck_commerce_sales/rma/debug';

    const DEFAULT_DECK_SHIPPING_METHOD = 'GROUND';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Config constructor.
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * Is DeckCommerce_Integration extension functionality enabled
     *
     * @param string $scopeType
     * @return bool
     */
    public function isEnabled($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return
            $this->scopeConfig->isSetFlag(self::CONFIG_IS_ENABLED, $scopeType)
            && !empty($this->getSiteApiKey($scopeType))
            && !empty($this->getSiteCode($scopeType))
            && !empty($this->getWebApiUrl($scopeType));
    }

    /**
     * Get config value
     *
     * @param string $path
     * @param string $scopeType
     */
    public function getConfigValue($path, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($path, $scopeType);
    }

    /**
     * Get Deck Commerce site code setting
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getSiteCode($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(self::CONFIG_SITE_CODE, $scopeType);
    }

    /**
     * Get Deck Commerce web api url setting
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getWebApiUrl($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return rtrim($this->getConfigValue(self::CONFIG_WEB_API_URL, $scopeType), '/');
    }

    /**
     * Get Deck Commerce site api url setting
     *
     * @param string $scopeType
     * @return string
     */
    public function getSiteApiKey($scopeType = ScopeInterface::SCOPE_STORE)
    {
        $value = $this->getConfigValue(self::CONFIG_SITE_API_KEY, $scopeType);
        return $this->encryptor->decrypt($value);
    }

    /**
     * Get inventory check api name setting
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getInventoryCheckApiName($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(self::INVENTORY_API_NAME, $scopeType);
    }

    /**
     * Is general check inventory action enabled
     *
     * @param string $scopeType
     * @return bool
     */
    public function isInventoryCheckEnabled($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::INVENTORY_ENABLED,
            $scopeType
        );
    }

    /**
     * Is check inventory on PDP AddToCart action enabled
     *
     * @param string $scopeType
     * @return bool
     */
    public function isPdpInventoryCheckEnabled($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isInventoryCheckEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::INVENTORY_CHECK_PDP,
            $scopeType
        );
    }

    /**
     * Is check inventory on cart page enabled
     *
     * @param string $scopeType
     * @return bool
     */
    public function isCartInventoryCheckEnabled($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isInventoryCheckEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::INVENTORY_CHECK_CART,
            $scopeType
        );
    }

    /**
     * Is check inventory on checkout page enabled
     *
     * @param string $scopeType
     * @return bool
     */
    public function isCheckoutInventoryCheckEnabled($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isInventoryCheckEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::INVENTORY_CHECK_CHECKOUT,
            $scopeType
        );
    }

    /**
     * Get inventory feed name setting
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getInventoryFeedName($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::INVENTORY_FEED_NAME,
            $scopeType
        );
    }

    /**
     * Get inventory cache lifetime setting
     * Used to decrease number of same inventory requests to Deck Commerce and to optimize performance
     * If value is 0 - cache is disabled
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getInventoryCacheLifetime($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::INVENTORY_CHECK_CACHE_LIFETIME,
            $scopeType
        );
    }

    /**
     * Use debug mode for inventory check - log all API requests and responses
     *
     * @param string $scopeType
     * @return bool
     */
    public function useInventoryCheckDebug($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isInventoryCheckEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::INVENTORY_CHECK_DEBUG,
            $scopeType
        );
    }

    /**
     * Is order export to Deck Commerce enabled setting
     *
     * @param string $scopeType
     * @return bool
     */
    public function isOrderExportEnabled($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isEnabled($scopeType) && $this->scopeConfig->isSetFlag(self::ORDER_ENABLED, $scopeType);
    }

    /**
     * Get order export API key setting
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getOrderExportApiName($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(self::ORDER_API_NAME, $scopeType);
    }

    /**
     * Get default Deck Commerce shipping method exported to order
     * It will be used if used Magento shipping method doesn't exist in the shipping methods mapping table
     * located in menu: Deck Commerce / Map Shipping Methods
     * If this config is also empty then GROUND method will be used by default
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getDefaultShippingMethod($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return
            $this->getConfigValue(self::ORDER_DEFAULT_METHOD, $scopeType)
            ?: self::DEFAULT_DECK_SHIPPING_METHOD;
    }

    /**
     * Get product attribute code for UPC(GTIN) value
     *
     * @param string $scopeType
     * @return string
     */
    public function getOrderItemUpcAttribute($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(self::ORDER_UPC_ATTRIBUTE_CODE, $scopeType);
    }

    /**
     * Get flag that determines whether need to send order to Deck Commerce on place order action
     *
     * @param string $scopeType
     * @return string
     */
    public function getOrderSendImmediately($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isOrderExportEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::ORDER_SEND_IMMEDIATELY,
            $scopeType
        );
    }

    /**
     * Setting that determines if need to use payment methods mapping
     *
     * @param string $scopeType
     * @return bool
     */
    public function usePaymentMethodsMappingJson($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag(self::ORDER_USE_PAYMENT_METHODS_MAPPING, $scopeType);
    }

    /**
     * Get order payment methods mapping json
     *
     * @param string $scopeType
     * @return bool
     */
    public function getPaymentMethodsMappingJson($scopeType = ScopeInterface::SCOPE_STORE)
    {
        if (!$this->usePaymentMethodsMappingJson($scopeType)) {
            return '';
        }

        return trim($this->getConfigValue(self::ORDER_PAYMENT_METHODS_MAPPING, $scopeType));
    }

    /**
     * Setting that determines if need to use Colorado Retail Delivery Tax calculation
     *
     * @param string $scopeType
     * @return bool
     */
    public function useDeliveryTax($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag(self::ORDER_USE_RETAIL_DELIVERY_TAX, $scopeType);
    }

    /**
     * Get Colorado Retail Delivery Tax calculation value (0.27 by default)
     *
     * @param string $scopeType
     * @return string
     */
    public function getDeliveryTaxAmount($scopeType = ScopeInterface::SCOPE_STORE)
    {
        if (!$this->useDeliveryTax($scopeType)) {
            return 0;
        }

        return $this->getConfigValue(self::ORDER_RETAIL_DELIVERY_TAX_AMOUNT, $scopeType);
    }

    /**
     * Use debug mode for order export - log all API requests and responses
     *
     * @param string $scopeType
     * @return bool
     */
    public function useOrderExportDebug($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isOrderExportEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::ORDER_DEBUG,
            $scopeType
        );
    }

    /**
     * Is order history enabled setting.
     * Allows to show Deck Commerce orders data in My Account instead of magento orders
     *
     * @param string $scopeType
     * @return bool
     */
    public function isOrderHistoryEnabled($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isEnabled($scopeType)
            && $this->isOrderExportEnabled($scopeType)
            && $this->scopeConfig->isSetFlag(self::ORDER_HISTORY_ENABLED, $scopeType);
    }

    /**
     * Get order history API name setting
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getOrderHistoryApiName($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(self::ORDER_HISTORY_API_NAME, $scopeType);
    }

    /**
     * Get order history cache lifetime setting
     * Used to decrease number of requests to Deck Commerce and optimize performance
     * 0 - disable cache
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getOrderHistoryCacheLifetime($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::ORDER_HISTORY_CACHE_LIFETIME,
            $scopeType
        );
    }

    /**
     * Use debug mode for order history - log all API requests and responses
     *
     * @param string $scopeType
     * @return bool
     */
    public function useOrderHistoryDebug($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isOrderHistoryEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::ORDER_HISTORY_DEBUG,
            $scopeType
        );
    }

    /**
     * Is full/delta inventory import enabled
     *
     * @param $inventoryType
     * @param string $scopeType
     * @return bool
     */
    public function isInventoryImportEnabled($inventoryType, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag(
            sprintf(self::INVENTORY_IMPORT_ENABLED, $inventoryType),
            $scopeType
        );
    }

    /**
     * Get file prefix for inventory files to filter required ones
     *
     * @param string $inventoryType
     * @param string $scopeType
     * @return mixed
     */
    public function getInventorySourceFilePrefix($inventoryType, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            sprintf(self::INVENTORY_IMPORT_SOURCE_FILE_PREFIX, $inventoryType),
            $scopeType
        );
    }

    /**
     * Get full/delta inventory import SFTP directory
     *
     * @param string $inventoryType
     * @param string $scopeType
     * @return mixed
     */
    public function getInventorySftpDirectory($inventoryType, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            sprintf(self::INVENTORY_IMPORT_SFTP_DIR, $inventoryType),
            $scopeType
        );
    }

    /**
     * Get full/delta inventory import SFTP host
     *
     * @param string $inventoryType
     * @param string $scopeType
     * @return mixed
     */
    public function getInventorySftpHost($inventoryType, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            sprintf(self::INVENTORY_IMPORT_SFTP_HOST, $inventoryType),
            $scopeType
        );
    }

    /**
     * Get full/delta inventory import SFTP username
     *
     * @param string $inventoryType
     * @param string $scopeType
     * @return mixed
     */
    public function getInventorySftpUsername($inventoryType, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            sprintf(self::INVENTORY_IMPORT_SFTP_USERNAME, $inventoryType),
            $scopeType
        );
    }

    /**
     * Get full/delta inventory import SFTP password
     *
     * @param string $inventoryType
     * @param string $scopeType
     * @return mixed
     */
    public function getInventorySftpPassword($inventoryType, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $value = $this->getConfigValue(
            sprintf(self::INVENTORY_IMPORT_SFTP_PASSWORD, $inventoryType),
            $scopeType
        );
        return $this->encryptor->decrypt($value);
    }

    /**
     * Get full/delta import inventory history directory (in "var" folder)
     * Used to store imported files
     *
     * @param string $inventoryType
     * @param string $scopeType
     * @return string
     */
    public function getInventoryHistoryDirectory($inventoryType, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return rtrim($this->getConfigValue(
            sprintf(self::INVENTORY_IMPORT_HISTORY_DIRECTORY, $inventoryType),
            $scopeType
        ), '/');
    }

    /**
     * Get full/delta inventory logs lifetime
     *
     * @param string $inventoryType
     * @param string $scopeType
     * @return string
     */
    public function getInventoryLogsLifetime($inventoryType, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return rtrim($this->getConfigValue(
            sprintf(self::INVENTORY_IMPORT_LOGS_LIFETIME, $inventoryType),
            $scopeType
        ), '/');
    }

    /**
     * Is RMA export to Deck Commerce enabled setting
     *
     * @param string $scopeType
     * @return bool
     */
    public function isRmaExportEnabled($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return
            $this->isRmaModuleEnabled()
            && $this->isEnabled($scopeType)
            && $this->scopeConfig->isSetFlag(self::RMA_ENABLED, $scopeType);
    }

    /**
     * Get RMA export API key setting
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getRmaExportApiName($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(self::RMA_API_NAME, $scopeType);
    }

    /**
     * Get Default Rma Type
     *
     * @param string $scopeType
     * @return mixed
     */
    public function getDefaultRmaType($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(self::RMA_DEFAULT_TYPE, $scopeType);
    }

    /**
     * Use debug mode for order export - log all API requests and responses
     *
     * @param string $scopeType
     * @return bool
     */
    public function useRmaExportDebug($scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isRmaExportEnabled($scopeType) && $this->scopeConfig->isSetFlag(
            self::RMA_DEBUG,
            $scopeType);
    }
}
