<?php

namespace DeckCommerce\Integration\Model\Service\Response;

use Laminas\Http\Response;
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
