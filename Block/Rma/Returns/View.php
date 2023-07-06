<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Rma\Returns;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\Rma\ReturnReasons;
use Magento\Customer\Model\Context;

/**
 * Order Returns view block
 */
class View extends \Magento\Rma\Block\Returns\View
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
     * View constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Data\Collection\ModelFactory $modelFactory
     * @param \Magento\Eav\Model\Form\Factory $formFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemsFactory
     * @param \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory $historiesFactory
     * @param \Magento\Rma\Model\ItemFactory $itemFactory
     * @param \Magento\Rma\Model\Item\FormFactory $itemFormFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Customer\Helper\View $customerView
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param DeckHelper $helper
     * @param ReturnReasons $returnReasons
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Collection\ModelFactory $modelFactory,
        \Magento\Eav\Model\Form\Factory $formFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemsFactory,
        \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory $historiesFactory,
        \Magento\Rma\Model\ItemFactory $itemFactory,
        \Magento\Rma\Model\Item\FormFactory $itemFormFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Customer\Helper\View $customerView,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        DeckHelper $helper,
        ReturnReasons $returnReasons,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->returnReasons = $returnReasons;
        parent::__construct(
            $context,
            $modelFactory,
            $formFactory,
            $eavConfig,
            $itemsFactory,
            $historiesFactory,
            $itemFactory,
            $itemFormFactory,
            $currentCustomer,
            $customerView,
            $httpContext,
            $rmaData,
            $registry,
            $customerRepository,
            $data
        );
    }

    /**
     * Initialize rma return
     *
     * @return void
     */
    public function _construct()
    {
        if (!$this->helper->isRmaExportEnabled()) {
            parent::_construct();
            $this->setTemplate('Magento_Rma::return/view.phtml');
            return;
        }

        $this->setTemplate('DeckCommerce_Integration::rma/view.phtml');

        $rma = $this->_coreRegistry->registry('current_rma');
        if (!$rma) {
            return;
        }

        $this->setRma($rma);
        $this->setOrder($this->_coreRegistry->registry('current_order'));
        $this->setItems($rma->getData('deck_rma_items_collection'));
    }

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(
            __('Order # %1', $this->getOrder()->getRealOrderId())
        );
    }

    /**
     * Retrieve current order model instance
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Return back url for logged in and guest users
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $this->getUrl('*/*/history');
        }
        return $this->getUrl('*/*/form');
    }

    /**
     * Return back title for logged in and guest users
     *
     * @return \Magento\Framework\Phrase
     */
    public function getBackTitle()
    {
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return __('Back to My Orders');
        }
        return __('View Another Order');
    }

    /**
     * Get current RMA
     *
     * @return mixed|null
     */
    public function getRma()
    {
        return $this->_coreRegistry->registry('current_rma');
    }

    /**
     * Get Cancel RMA url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('*/*/view', ['entity_id' => $this->getRma()->getId(), 'cancel' => 1]);
    }

    /**
     * Get RMA reason by ID
     *
     * @param string $reasonId
     * @return mixed|string
     */
    public function getRmaReasonById($reasonId)
    {
        $reasons = $this->returnReasons->getReturnReasonsList();
        if (isset($reasons[$reasonId])) {
            return $reasons[$reasonId];
        }

        return '';
    }
}
