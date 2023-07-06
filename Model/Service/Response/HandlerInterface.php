<?php

namespace DeckCommerce\Integration\Model\Service\Response;

use Zend_Http_Response as Response;
use DeckCommerce\Integration\Model\Service\Exception\WebapiException;

/**
 * Service Response HandlerInterface
 */
interface HandlerInterface
{

    /**
     * Handle API response (decode)
     *
     * @param Response $response
     * @return array|string
     * @throws WebapiException
     */
    public function handle(Response $response);
}
