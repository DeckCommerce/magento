<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Rma;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\Rma\ReturnReasons;

/**
 * Create Rma model
 */
class Create extends \Magento\Rma\Block\Returns\Create
{
    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var ReturnReasons
     */
    protected $returnReasons;

    /**
     * Create constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Data\Collection\ModelFactory $modelFactory
     * @param \Magento\Eav\Model\Form\Factory $formFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Rma\Model\ItemFactory $itemFactory
     * @param \Magento\Rma\Model\Item\FormFactory $itemFormFactory
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param DeckHelper $helper
     * @param ReturnReasons $returnReasons
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Collection\ModelFactory $modelFactory,
        \Magento\Eav\Model\Form\Factory $formFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Rma\Model\ItemFactory $itemFactory,
        \Magento\Rma\Model\Item\FormFactory $itemFormFactory,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        DeckHelper $helper,
        ReturnReasons $returnReasons,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_rmaData = $rmaData;
        $this->_itemFactory = $itemFactory;
        $this->_itemFormFactory = $itemFormFactory;
        $this->addressRenderer = $addressRenderer;
        $this->helper = $helper;
        $this->returnReasons = $returnReasons;

        parent::__construct(
            $context,
            $modelFactory,
            $formFactory,
            $eavConfig,
            $itemFactory,
            $itemFormFactory,
            $rmaData,
            $registry,
            $addressRenderer,
            $data
        );
    }

    /**
     * Initialize returns content
     */
    public function _construct()
    {
        parent::_construct();

        if (!$this->helper->isRmaExportEnabled()) {
            $this->setTemplate('Magento_Rma::return/create.phtml');
            return;
        }

        $this->setTemplate('DeckCommerce_Integration::rma/create.phtml');
    }

    /**
     * @param $price
     * @return string
     */
    public function formatPrice($price)
    {
        return $this->helper->formatPrice($price);
    }

    /**
     * Get RMA reasons list
     */
    public function getReasonsList()
    {
        return $this->returnReasons->getReturnReasonsList();
    }
}
