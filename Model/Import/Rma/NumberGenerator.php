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
 * NumberGenerator Model
 */
class NumberGenerator
{
    const API_TYPE = 'generate_rma_number';

    const API_NAME = 'api/OmsRmaGenerateNumber';

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
     * @return mixed|string
     */
    protected function getApiGeneratedRmaNumber($scope = ScopeInterface::SCOPE_STORE)
    {
        try {
            $result = $this->httpClient->execute(
                self::API_NAME,
                'POST',
                [],
                $scope
            );

            $this->helper->addRmaExportLog('GeneratedRmaNumber Response result:', $result);

            if ($result && isset($result['RmaNumber'])) {
                return $result['RmaNumber'];
            }

        } catch (\Exception $e) {
            $this->helper->addRmaExportLog('!!! GeneratedRmaNumber ERROR:', $e->getMessage());
        }

        return '';
    }

    /**
     * Get RMA number
     *
     * @param string $scope
     * @return mixed|string
     */
    public function getRmaNumber($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getApiGeneratedRmaNumber($scope);
    }
}
