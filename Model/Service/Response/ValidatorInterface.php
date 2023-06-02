<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */


namespace DeckCommerce\Integration\Model\Service\Response;

use Laminas\Http\Response;

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
