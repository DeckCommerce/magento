<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Import;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Service\Exception\WebapiException;
use DeckCommerce\Integration\Model\Service\HttpClient;
use DeckCommerce\Integration\Model\Service\Request\InventoryCheckBuilderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * InventoryCheck Model
 */
class InventoryCheck
{
    const API_TYPE = 'inventor_check';

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var InventoryCheckBuilderInterface
     */
    protected $inventoryCheckBuilder;

    /**
     * InventoryCheck constructor.
     *
     * @param DeckHelper $helper
     * @param HttpClient $httpClient
     * @param InventoryCheckBuilderInterface $inventoryCheckBuilder
     */
    public function __construct(
        DeckHelper $helper,
        HttpClient $httpClient,
        InventoryCheckBuilderInterface $inventoryCheckBuilder
    ) {
        $this->helper                = $helper;
        $this->httpClient            = $httpClient;
        $this->inventoryCheckBuilder = $inventoryCheckBuilder;
    }

    /**
     * Call API request to get products QTY from DeckCommerce
     *
     * @param array $skus
     * @param string $scope
     * @return array
     */
    protected function getApiProductsQty($skus, $scope = ScopeInterface::SCOPE_STORE)
    {
        if (empty($skus)) {
            return null;
        }
        $result = null;
        try {
            $inventoryFeedName = $this->helper->getInventoryFeedName();
            if (!is_array($skus)) {
                $skus = [$skus];
            }
            $params = $this->inventoryCheckBuilder->build($skus, $inventoryFeedName);

            $this->helper->addInventoryLog('Request params:', $params);

            $result = $this->httpClient->execute(
                $this->helper->getInventoryCheckApiName(),
                'POST',
                $params,
                $scope
            );

            $this->helper->addInventoryLog('Response result:', $result);
        } catch (\Exception $e) {
            $this->helper->addInventoryLog('!!! ERROR:', $e->getMessage());
        }

        return $result;
    }

    /**
     * Get Deck Commerce products qtys data
     *
     * @param array $skus
     * @param string $scope
     * @return array | null
     * @throws WebapiException
     */
    public function getDeckProductsQty($skus, $scope = ScopeInterface::SCOPE_STORE)
    {
        $qtyData = $this->getApiProductsQty($skus, $scope);
        if ($qtyData === null) {
            throw new WebapiException(__("Unable to get inventory from Deck Commerce"));
        }

        $qtys = null;
        if ($qtyData && isset($qtyData['Items']) && !empty($qtyData['Items'])) {
            $items = $qtyData['Items'];
            foreach ($items as $item) {
                if ($item && isset($item['TotalAvailableQuantity'])) {
                    $qtys[$item['DeckSKU']] = $item['TotalAvailableQuantity'];
                }
            }
        }

        return $qtys;
    }

    /**
     * Get cached products qty response data or call getDeckProductsQty method if cache is empty
     *
     * @param array $skus
     * @param string $scope
     * @return array|bool|float|int|mixed|string|null
     */
    public function getDeckProductsQtyCached($skus, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->helper->getCachedData(
            $this,
            'getDeckProductsQty',
            [$skus, $scope],
            $this->helper->getInventoryCacheLifetime()
        );
    }
}
