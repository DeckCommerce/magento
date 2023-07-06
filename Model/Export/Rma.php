<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Export;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Service\HttpClient;
use DeckCommerce\Integration\Model\Service\Request\RmaBuilderInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\ScopeInterface;
use DeckCommerce\Integration\Model\Import\Rma\NumberGenerator;
use DeckCommerce\Integration\Model\Service\Response\Handler as ResponseHandler;

/**
 * Rma model to create RMA on Deck Commerce side
 */
class Rma
{

    const API_NAME_CANCEL = 'api/OmsRmaCancel';

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var RmaBuilderInterface
     */
    protected $rmaBuilder;

    /**
     * @var NumberGenerator
     */
    protected $numberGenerator;

    /**
     * Rma constructor.
     *
     * @param DeckHelper $helper
     * @param HttpClient $httpClient
     * @param RmaBuilderInterface $rmaBuilder
     * @param NumberGenerator $numberGenerator
     */
    public function __construct(
        DeckHelper $helper,
        HttpClient $httpClient,
        RmaBuilderInterface $rmaBuilder,
        NumberGenerator $numberGenerator
    ) {
        $this->helper     = $helper;
        $this->httpClient = $httpClient;
        $this->rmaBuilder = $rmaBuilder;
        $this->numberGenerator = $numberGenerator;
    }

    /**
     * Send Magento RMA data to DeckCommerce
     *
     * @param $orderId
     * @param $items
     * @param string $scope
     * @return array|void|null
     */
    public function send($orderId, $items, $scope = ScopeInterface::SCOPE_STORE)
    {
        $result = null;

        try {

            $rmaNumber = $this->numberGenerator->getRmaNumber($scope);
            if (!$rmaNumber) {
                return $result;
            }

            $params = $this->rmaBuilder->build($orderId, $rmaNumber, $items);
            $this->helper->addRmaExportLog('Request params:', $params);

            $result = $this->httpClient->execute(
                $this->helper->getRmaExportApiName(),
                'POST',
                $params,
                $scope
            );

            $this->helper->addRmaExportLog('Response result:', $result);

            if ($result && isset($result[ResponseHandler::RESPONSE_CODE_FIELD])
                && $result[ResponseHandler::RESPONSE_CODE_FIELD] == ResponseHandler::SUCCESS_RESPONSE_CODE
            ) {
                return $rmaNumber;
            }
        } catch (ValidatorException $e) {
            $this->helper->addRmaExportLog('NOTICE:', $e->getMessage());
        } catch (\Exception $e) {
            $this->helper->addRmaExportLog('!!! ERROR:', $e->getMessage());
        }

        return $result;
    }

    /**
     * Send Cancel RMA request to DeckCommerce
     *
     * @param $rmaNumber
     * @param string $scope
     * @return mixed
     * @throws \DeckCommerce\Integration\Model\Service\Exception\WebapiException
     */
    public function cancel($rmaNumber, $scope = ScopeInterface::SCOPE_STORE)
    {
        try {
            $result = null;

            $params = ['RmaNumber' => $rmaNumber];

            $this->helper->addRmaExportLog('Request params:', $params, true);

            $result = $this->httpClient->execute(
                self::API_NAME_CANCEL,
                'POST',
                $params,
                $scope
            );

            $this->helper->addRmaExportLog('Response result:', $result, true);

        } catch (ValidatorException $e) {
            $this->helper->addRmaExportLog('NOTICE:', $e->getMessage());
        } catch (\Exception $e) {
            $this->helper->addRmaExportLog('!!! ERROR:', $e->getMessage());
        }

        return $result;
    }
}
