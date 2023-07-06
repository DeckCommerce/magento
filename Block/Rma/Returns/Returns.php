<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Rma\Returns;

use DeckCommerce\Integration\Helper\Data as DeckHelper;

/**
 * Block Class Returns
 */
class Returns extends \Magento\Rma\Block\Returns\Returns
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * Returns constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Rma\Model\ResourceModel\Rma\Grid\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param DeckHelper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Rma\Model\ResourceModel\Rma\Grid\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Http\Context $httpContext,
        DeckHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $collectionFactory, $customerSession, $rmaData, $registry, $httpContext, $data);
    }

    /**
     * Initialize returns content
     */
    public function _construct()
    {
        if (!$this->helper->isRmaExportEnabled()) {
            parent::_construct();
            $this->setTemplate('Magento_Rma::return/returns.phtml');
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_coreRegistry->registry('current_order');
        $this->setTemplate('Magento_Rma::return/returns.phtml');
        $this->setReturns($order->getData('deck_rma_collection'));
    }
}
