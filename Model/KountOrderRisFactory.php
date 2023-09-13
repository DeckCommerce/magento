<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class KountOrderRisFactory
 * Factory class that handles the case when Kount module may exist or not
 */
class KountOrderRisFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var mixed|string
     */
    private $instanceName;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param $instanceName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
                               $instanceName = \Kount\Kount\Model\Order\Ris::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName  = $instanceName;
    }

    /**
     * @param array $data
     * @return \#P#C\DeckCommerce\Integration\Model\KountOrderRisFactory.instanceName
     * |\#P#S\DeckCommerce\Integration\Model\KountOrderRisFactory.instanceName|mixed|null
     */
    public function create(array $data = [])
    {
        if (class_exists($this->instanceName)) {
            return $this->objectManager->create($this->instanceName, $data);
        }

        return null;
    }
}
