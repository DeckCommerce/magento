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
 * ReturnReasons Model
 */
class ReturnReasons
{
    const API_TYPE = 'rma_reasons';

    const API_NAME = 'api/OmsGetReturnReasons';

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
     * Generate RMA number using DeckCommerce API
     *
     * @param string $scope
     * @return array
     */
    public function getApiReturnReasons($scope = ScopeInterface::SCOPE_STORE)
    {
        try {
            $result = $this->httpClient->execute(
                self::API_NAME,
                'POST',
                [],
                $scope
            );

            $this->helper->addRmaExportLog('ReturnReasons Response result:', $result);

            if ($result && isset($result['DataPairs'])) {
                return $result['DataPairs'];
            }

        } catch (\Exception $e) {
            $this->helper->addRmaExportLog('!!! ReturnReasons ERROR:', $e->getMessage());
        }

        return [];
    }

    /**
     * Get Return reasons
     *
     * @return mixed|string
     */
    public function getReturnReasonsList()
    {
        $reasons = $this->getReturnReasonsCached();

        $result = [];
        foreach ($reasons as $reason) {
            $result[$reason['Value']] = $reason['Text'];
        }

        return $result;
    }

    /**
     * Cache return reasons
     *
     * @return array|bool|float|int|mixed|string|null
     */
    public function getReturnReasonsCached()
    {
        return $this->helper->getCachedData(
            $this,
            'getApiReturnReasons',
            [],
            self::CACHE_LIFETIME
        );
    }

}
