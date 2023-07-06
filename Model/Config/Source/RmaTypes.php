<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Source;

use DeckCommerce\Integration\Model\Import\Rma\ReturnTypes;

/**
 * Rma Types model that uses API to get types list
 */
class RmaTypes implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var ReturnTypes
     */
    protected $returnTypes;

    /**
     * RmaTypes constructor.
     * @param ReturnTypes $returnTypes
     */
    public function __construct(
        ReturnTypes $returnTypes
    ) {
        $this->returnTypes = $returnTypes;
    }

    /**
     * @var array
     */
    protected static $_options;

    /**
     * Cron frequency options for delta inventory
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!self::$_options) {

            $rmaTypes = $this->returnTypes->getReturnTypesList();
            foreach ($rmaTypes as $id => $typeText) {
                self::$_options[] = [
                    'label' => __($typeText),
                    'value' => $id,
                ];
            }
        }
        return self::$_options;
    }
}
