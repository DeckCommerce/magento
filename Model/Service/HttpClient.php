<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2022 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service;

use DeckCommerce\Integration\Helper\Data as HelperData;
use DeckCommerce\Integration\Model\Service\Exception\WebapiException;
use DeckCommerce\Integration\Model\Service\Response\Handler as ResponseHandler;
use DeckCommerce\Integration\Model\Service\Response\Validator as ResponseValidator;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Store\Model\ScopeInterface;
use Zend_Http_Response as Response;

/**
 * API Http Client
 */
class HttpClient
{
    const DECK_AUTH_TYPE = 'Authorization';
    const DECK_API_VERSION = 'apiVersion';
    const DECK_API_VERSION_NUMBER = '5';

    const DECK_JSON_DATA_TYPE = 'application/json';

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var ResponseValidator
     */
    protected $responseValidator;

    /**
     * @var ResponseHandler
     */
    protected $responseHandler;

    /**
     * HttpClient constructor.
     * @param ZendClientFactory $httpClientFactory
     * @param HelperData $helper
     * @param Json $jsonSerializer
     * @param ResponseValidator $responseValidator
     * @param ResponseHandler $responseHandler
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        HelperData $helper,
        Json $jsonSerializer,
        ResponseValidator $responseValidator,
        ResponseHandler $responseHandler
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->helper            = $helper;
        $this->jsonSerializer    = $jsonSerializer;
        $this->responseValidator = $responseValidator;
        $this->responseHandler   = $responseHandler;
    }

    /**
     * Execute API request to DeckCommerce
     *
     * @param string $apiName
     * @param string $method
     * @param array $params
     * @param string $scopeType
     * @return array
     * @throws WebapiException
     */
    public function execute($apiName, $method, $params, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $apiEndpoint = $this->helper->getFullWebApiUrl($apiName);

        $currentUtcTimestamp = gmdate('n/j/Y H:i A');

        $apiToken = $this->helper->getApiToken($apiName, $currentUtcTimestamp, $scopeType);
        $params = $this->prepareParams($params, $apiToken, $currentUtcTimestamp, $scopeType);

        $response = $this->doRequest($apiEndpoint, $apiToken, $method, $params);

        if (!$this->responseValidator->validate($response)) {
            throw new WebapiException(__($this->responseValidator->getErrorMessagesAsString()), $response->getStatus());
        }

        return $this->responseHandler->handle($response);
    }

    /**
     * Prepare request params
     *
     * @param array $params
     * @param string $apiToken
     * @param string $currentUtcTimestamp
     * @param string $scopeType
     * @return mixed
     */
    protected function prepareParams($params, $apiToken, $currentUtcTimestamp, $scopeType)
    {
        $params['VerificationKey'] = $apiToken;
        $params['TimestampUTC']    = $currentUtcTimestamp;
        $params['SiteCode']        = $this->helper->getSiteCode($scopeType);

        return $params;
    }

    /**
     * Do API request with provided params
     *
     * @param string $uriEndpoint
     * @param string $apiToken
     * @param string $requestMethod
     * @param array $params
     * @return Response
     */
    protected function doRequest(
        string $uriEndpoint,
        string $apiToken,
        string $requestMethod = Request::HTTP_METHOD_GET,
        array $params = []
    ): Response {

        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();

        try {
            $client->setHeaders(self::DECK_AUTH_TYPE, $apiToken);
            $client->setHeaders(self::DECK_API_VERSION, self::DECK_API_VERSION_NUMBER);
            $client->setUri($uriEndpoint);
            $client->setMethod($requestMethod);
            $client->setConfig(['timeout' => 60]);

            if (!empty($params)) {
                $encodedData = $this->jsonSerializer->serialize($params);
                $client->setRawData($encodedData, self::DECK_JSON_DATA_TYPE);
            }

            $response = $client->request();

        } catch (\Exception $e) {
            $data = [
                'ResponseCode' => $e->getCode() ?: -1,
                'Message' => $e->getMessage()
            ];

            $response = new \Zend_Http_Response($e->getCode(), [], $this->jsonSerializer->serialize($data));
        }

        return $response;
    }
}
