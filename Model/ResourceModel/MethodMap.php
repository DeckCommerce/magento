<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\ResourceModel;

/**
 * MethodMap resource model
 */
class MethodMap extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $filterManager;

    /**
     * MethodMap constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param string|null $connectionName
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Filter\FilterManager $filterManager,
        $connectionName = null
    ) {
        $this->filterManager = $filterManager;
        parent::__construct($context, $connectionName);
    }

    /**
     * Model initialization
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init('deck_shipping_method_map', 'map_id');
    }
}
