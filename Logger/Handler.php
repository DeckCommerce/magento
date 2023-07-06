<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Logger Handler
 */
class Handler extends Base
{

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/deck_commerce.log';

    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::DEBUG;
}
