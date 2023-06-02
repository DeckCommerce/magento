<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Import\Rma;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Service\Exception\WebapiException;
use DeckCommerce\Integration\Model\Service\HttpClient;
use DeckCommerce\Integration\Model\Service\Request\InventoryCheckBuilderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * ReturnTypes model
 */
class ReturnTypes
{
    const API_TYPE = 'rma_types';

    const API_NAME = 'api/OmsGetReturnTypes';

    const CACHE_LIFETIME = 864000; //10 days

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * InventoryCheck constructor.
     *
     * @param DeckHelper $helper
     * @param HttpClient $httpClient
     */
    public function __construct(
        DeckHelper $helper,
        HttpClient $httpClient
    ) {
        $this->helper                = $helper;
        $this->httpClient            = $httpClient;
    }

    /**
     * Generate Return Types using DeckCommerce API
     *
     * @param string $scope
     * @return mixed|string
     */
    public function getApiReturnTypes($scope = ScopeInterface::SCOPE_STORE)
    {
        try {
            $result = $this->httpClient->execute(
                self::API_NAME,
                'POST',
                [],
                $scope
            );

            $this->helper->addInventoryLog('ReturnTypes Response result:', $result);

            if ($result && isset($result['DataPairs'])) {
                return $result['DataPairs'];
            }

        } catch (\Exception $e) {
            $this->helper->addInventoryLog('!!! ReturnTypes ERROR:', $e->getMessage());
        }

        return '';
    }

    /**
     * Get Return Types
     *
     * @return mixed|string
     */
    public function getReturnTypesList()
    {
        $defaultTypes = [
            '1' => 'Return as Defective (to Vendor)',
            '2' => 'Return to Inventory',
        ];

        if (!$this->helper->isEnabled()) {
            return $defaultTypes;
        }

        $types = $this->getReturnTypesCached();
        if (!$types) {
            return $defaultTypes;
        }

        $result = [];
        foreach ($types as $type) {
            $result[$type['Value']] = $type['Text'];
        }

        return $result;
    }

    /**
     * Cache return reasons
     *
     * @return array|bool|float|int|mixed|string|null
     */
    public function getReturnTypesCached()
    {
        return $this->helper->getCachedData(
            $this,
            'getApiReturnTypes',
            [],
            self::CACHE_LIFETIME
        );
    }
}
