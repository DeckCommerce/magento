<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Plugin\Model\ConfigurableProduct\Product\Type;

use DeckCommerce\Integration\Helper\Data as HelperData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * Class ConfigurablePlugin
 */
class ConfigurablePlugin
{

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * OrderPlugin constructor.
     *
     * @param HelperData $helper
     */
    public function __construct(HelperData $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Retrieve related products collection. Extension point for listing
     *
     * @param Configurable $subject
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection $result
     * @param  \Magento\Catalog\Model\Product $product
     * @return \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection
     */
    public function afterGetUsedProductCollection(Configurable $subject, $result, $product)
    {
        if (!$this->helper->isPdpAddToCartAction()) {
            return $result;
        }

        $result->setFlag('has_stock_status_filter', true);

        return $result;
    }
}
