<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Rma\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Data\Collection;

/**
 * "Returns" link
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Link extends \Magento\Rma\Block\Order\Link
{
    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * Link constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Rma\Model\ResourceModel\Rma\Grid\CollectionFactory $collectionFactory
     * @param \Magento\Rma\Helper\Data $rmaHelper
     * @param DeckHelper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\Framework\Registry $registry,
        \Magento\Rma\Model\ResourceModel\Rma\Grid\CollectionFactory $collectionFactory,
        \Magento\Rma\Helper\Data $rmaHelper,
        DeckHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $defaultPath, $registry, $collectionFactory, $rmaHelper, $data);
    }

    /**
     * Get is link aviable
     *
     * @return bool
     */
    protected function _isRmaAviable()
    {
        if (!$this->helper->isRmaExportEnabled()) {
            return parent::_isRmaAviable();
        }

        $order = $this->_registry->registry('current_order');

        /** @var Collection $rmaCollection */
        $rmaCollection = $order->getData('deck_rma_collection');
        if ($rmaCollection->count()) {
            return true;
        }

        return false;
    }
}
