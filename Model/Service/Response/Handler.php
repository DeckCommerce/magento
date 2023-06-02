<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Response;

use DeckCommerce\Integration\Model\Service\Exception\WebapiException;
use Magento\Framework\Serialize\Serializer\Json;
use Laminas\Http\Response;

/**
 * Response Handler
 */
class Handler implements HandlerInterface
{

    const RESPONSE_CODE_FIELD               = 'ResponseCode';
    const SUCCESS_RESPONSE_CODE             = '0';
    const SUCCESSFULLY_QUEUED_RESPONSE_CODE = '15';

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * Handler constructor.
     * @param Json $jsonSerializer
     */
    public function __construct(
        Json $jsonSerializer
    ) {
        $this->jsonSerializer    = $jsonSerializer;
    }

    /**
     * Handle API response (decode)
     *
     * @param Response $response
     * @return array|string
     * @throws WebapiException
     */
    public function handle(Response $response)
    {
        $responseBody = $response->getBody();

        try {
            $decodedResponseBody = $this->jsonSerializer->unserialize($responseBody);
            if (!isset($decodedResponseBody) || !in_array($decodedResponseBody[self::RESPONSE_CODE_FIELD], [
                self::SUCCESS_RESPONSE_CODE, self::SUCCESSFULLY_QUEUED_RESPONSE_CODE])
            ) {
                throw new WebapiException(
                    __($decodedResponseBody['Message']),
                    $decodedResponseBody[self::RESPONSE_CODE_FIELD]
                );
            }
            return $decodedResponseBody;
        } catch (\Exception $e) {
            throw new WebapiException(
                __('Deck Commerce API Response is invalid: ' . $e->getMessage()),
                $e->getCode()
            );
        }
    }
}
