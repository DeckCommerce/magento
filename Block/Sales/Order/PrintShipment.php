<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Sales\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Helper\Sales\Reorder as DeckReorderHelper;

/**
 * PrintShipment Block
 */
class PrintShipment extends \Magento\Sales\Block\Order\PrintShipment
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var DeckReorderHelper
     */
    protected $deckReorderHelper;

    /**
     * PrintShipment constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param DeckHelper $helper
     * @param DeckReorderHelper $deckReorderHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        DeckHelper $helper,
        DeckReorderHelper $deckReorderHelper,
        array $data = []
    ) {
        $this->helper            = $helper;
        $this->deckReorderHelper = $deckReorderHelper;
        parent::__construct($context, $registry, $paymentHelper, $addressRenderer, $data);
    }

    /**
     * Get order items
     *
     * @return array|\Magento\Framework\DataObject[]
     */
    public function getItems()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::getItems();
        }

        if (!$this->getOrder()) {
            return [];
        }

        $this->deckReorderHelper->setOrder($this->getOrder());

        return $this->getOrder()->getData('items_collection');
    }
}
