<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2020 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */


namespace DeckCommerce\Integration\Model\Service\Response;

use Zend_Http_Response as Response;

/**
 * Service Response ValidatorInterface
 */
interface ValidatorInterface
{

    /**
     * Validate data
     *
     * @param Response $response
     * @return bool
     */
    public function validate(Response $response);
}
