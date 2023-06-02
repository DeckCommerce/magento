<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Response;

use Laminas\Http\Response;

/**
 * Response Validator
 */
class Validator implements ValidatorInterface
{

    /**
     * Success response codes mapping
     *
     * @var array
     */
    protected $successResponseCodes = [
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK' ,
        '201' => 'Created',
        '202' => 'Accepted',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content'
    ];

    /**
     * Failed response codes mapping
     *
     * @var array
     */
    protected $failedResponseCodes = [
        '400' => 'Bad Request - the request could not be parsed. Response: %s',
        '401' => 'Unauthorized - user is not logged in, could not be authenticated. Response: %s',
        '402' => 'Payment Required. Response: %s',
        '403' => 'Forbidden - cannot access resource. Response: %s',
        '404' => 'Not Found - resource does not exist. Response: %s',
        '405' => 'Method Not Allowed. Response: %s',
        '406' => 'Not Acceptable. Response: %s',
        '407' => 'Proxy Authentication Required. Response: %s',
        '408' => 'Request Timeout. Response: %s',
        '409' => 'Conflict - with state of the resource on server. Can occur with (too rapid) requests. Response: %s',
        '410' => 'Gone. Response: %s',
        '411' => 'Length Required. Response: %s',
        '412' => 'Precondition Failed. Response: %s',
        '413' => 'Request Entity Too Large. Response: %s',
        '414' => 'Request-URI Too Long. Response: %s',
        '415' => 'Unsupported Media Type. Response: %s',
        '416' => 'Requested Range Not Satisfiable. Response: %s',
        '417' => 'Expectation Failed. Response: %s',
        '500' => 'Internal Server Error. Response: %s',
        '501' => 'Not Implemented. Response: %s',
        '502' => 'Bad Gateway. Response: %s',
        '503' => 'Service Unavailable. Response: %s',
        '504' => 'Gateway Timeout. Response: %s',
        '505' => 'HTTP Version Not Supported. Response: %s',
        '509' => 'Bandwidth Limit Exceeded. Response: %s',
    ];

    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * Add error message
     *
     * @param $message
     */
    protected function addErrorMessage($message)
    {
        if (!in_array($message, $this->errorMessages)) {
            $this->errorMessages[] = $message;
        }
    }

    /**
     * Get error messages
     *
     * @return array
     */
    protected function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * Get error messages as comma separated string
     *
     * @return string
     */
    public function getErrorMessagesAsString()
    {
        return implode(', ', $this->errorMessages);
    }

    /**
     * Validate data
     *
     * @param Response $response
     * @return bool
     */
    public function validate(Response $response)
    {
        if (!isset($this->successResponseCodes[$response->getStatusCode()])) {
            $this->addErrorMessage($this->prepareErrorMessage($response));

            return false;
        }

        return true;
    }

    /**
     * Prepare error message text
     *
     * @param Response $response
     * @return string
     */
    protected function prepareErrorMessage($response)
    {
        $responseCode = $response->getStatusCode();
        $responseBody = $response->getBody();
        if (isset($this->failedResponseCodes[$responseCode])) {
            return sprintf($this->failedResponseCodes[$responseCode], $responseBody);
        }

        return sprintf('Unknown Deck Commerce API error. "%s" - "%s".', $responseCode, $responseBody);
    }
}
