<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Import;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Builder\Order as OrderBuilder;
use DeckCommerce\Integration\Model\Data\Collection;
use DeckCommerce\Integration\Model\Data\CollectionFactory;
use DeckCommerce\Integration\Model\Service\HttpClient;
use DeckCommerce\Integration\Model\Service\Request\OrderHistoryInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Store\Model\ScopeInterface;

/**
 * OrderHistory Import Model
 */
class OrderHistory
{
    const API_TYPE = 'order_history';

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var OrderBuilder
     */
    protected $orderBuilder;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var OrderHistoryInterface
     */
    protected $orderHistoryBuilder;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * OrderHistory constructor.
     * @param DeckHelper $helper
     * @param OrderBuilder $orderBuilder
     * @param HttpClient $httpClient
     * @param OrderHistoryInterface $orderHistoryBuilder
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        DeckHelper $helper,
        OrderBuilder $orderBuilder,
        HttpClient $httpClient,
        OrderHistoryInterface $orderHistoryBuilder,
        CollectionFactory $collectionFactory
    ) {
        $this->helper              = $helper;
        $this->orderBuilder        = $orderBuilder;
        $this->httpClient          = $httpClient;
        $this->orderHistoryBuilder = $orderHistoryBuilder;
        $this->collectionFactory   = $collectionFactory;
    }

    /**
     * Send Order History request to Deck Commerce
     *
     * @param int $customerId
     * @param int $orderId
     * @param int $pageNumber
     * @param int $pageSize
     * @param string $scope
     * @return array|null|void
     */
    public function getApiOrdersHistory(
        $customerId,
        $orderId,
        $pageNumber,
        $pageSize,
        $scope = ScopeInterface::SCOPE_STORE
    ) {
        $result = null;
        try {
            $params = $this->orderHistoryBuilder->build($pageNumber, $pageSize, $customerId, $orderId);

            $this->helper->addOrderHistoryLog('Request params:', $params);

            $result = $this->httpClient->execute(
                $this->helper->getOrderHistoryApiName(),
                'POST',
                $params,
                $scope
            );

            $this->helper->addOrderHistoryLog('Response result:', $result);
        } catch (\Exception $e) {
            $this->helper->addOrderHistoryLog('!!! ERROR:', $e->getMessage());
        }

        return $result;
    }

    /**
     * Prepare Magento orders collection based on the Deck Commerce API data
     *
     * @param array $result
     * @return Collection
     */
    protected function prepareOrdersCollection(array $result)
    {
        $deckOrderData = $result["OrderDetails"] ?? [];

        /** @var $collection Collection */
        $collection = $this->collectionFactory->create();
        if (empty($deckOrderData)) {
            return $collection;
        }

        try {
            foreach ($deckOrderData as $deckOrder) {
                $order = $this->orderBuilder->prepareMagentoOrder($deckOrder);
                if ($order && !empty($order->getData())) {
                    $collection->addItem($order);
                }
            }
            if (isset($result['TotalRecordsAvailable'])) {
                $collection->setTotalCount($result['TotalRecordsAvailable']);
            }
        } catch (\Exception $e) {
            return $collection;
        }

        return $collection;
    }

    /**
     * Check whether returned API result has necessary data
     *
     * @param array|null $result
     * @return bool
     */
    protected function isApiOrderResultValid($result)
    {
        return $result && isset($result["OrderDetails"]) && !empty($result['OrderDetails']);
    }

    /**
     * Get history orders collection
     *
     * @param int $customerId
     * @param int $orderId
     * @param int $pageNumber
     * @param int $pageSize
     * @return Collection
     */
    public function getOrdersHistory($customerId, $orderId, $pageNumber, $pageSize)
    {
        if (!$customerId) {
            return $this->prepareOrdersCollection([]);
        }
        if ($this->helper->isOrderHistoryEnabled()) {
            $result = $this->getApiOrdersHistoryCached($customerId, $orderId, $pageNumber, $pageSize);
            if ($this->isApiOrderResultValid($result)) {
                return $this->prepareOrdersCollection($result);
            }
        }

        return $this->prepareOrdersCollection([]);
    }

    /**
     * Get cached orders history
     *
     * @param int $customerId
     * @param int $orderId
     * @param int $pageNumber
     * @param int $pageSize
     * @return array|bool|float|int|mixed|string|null
     */
    public function getApiOrdersHistoryCached($customerId, $orderId, $pageNumber, $pageSize)
    {
        return $this->helper->getCachedData(
            $this,
            'getApiOrdersHistory',
            [$customerId, $orderId, $pageNumber, $pageSize],
            $this->helper->getOrderHistoryCacheLifetime()
        );
    }

    /**
     * Get magento order build from Deck Commerce API response data
     *
     * @param int $orderId
     * @param bool $canUseForGuest
     * @return null|SalesOrder
     */
    public function getOrder($orderId, $canUseForGuest = false)
    {
        if (!$orderId) {
            return null;
        }
        $order = null;
        if ($this->helper->isOrderHistoryEnabled()) {
            try {
                $result = $this->getApiOrdersHistoryCached(null, $orderId, 1, 1);
                if ($this->isApiOrderResultValid($result)) {
                    $order = $this->orderBuilder
                        ->prepareMagentoOrder(reset($result["OrderDetails"]), $canUseForGuest, true);
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return $order;
    }
}
