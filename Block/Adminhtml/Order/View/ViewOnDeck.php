<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2020 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Adminhtml\Order\View;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Export\Order as DeckOrder;
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Config;

/**
 * Block to display View on Deck Commerce button
 */
class ViewOnDeck extends View
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var DeckOrder
     */
    protected $deckOrder;

    /**
     * ViewOnDeck constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Config $salesConfig
     * @param Reorder $reorderHelper
     * @param DeckHelper $helper
     * @param DeckOrder $deckOrder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $salesConfig,
        Reorder $reorderHelper,
        DeckHelper $helper,
        DeckOrder $deckOrder,
        array $data = []
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->helper     = $helper;
        $this->deckOrder  = $deckOrder;
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
    }

    /**
     * Add "View on Deck Commerce" button on the admin order view page
     * Button is available only for orders with "Synced" deck_export_status value
     *
     * @return $this
     */
    public function viewOnDeck()
    {
        $parentBlock = $this->getParentBlock();

        if (!($parentBlock instanceof \Magento\Backend\Block\Template)
            || !$parentBlock->getOrderId()
            || !$this->helper->isOrderExportEnabled()
            || !$this->deckOrder->isSuccess($this->getOrder())
        ) {
            return $this;
        }

        $apiUrl = $this->helper->getWebApiUrl();
        $deckUrl = str_replace('api', 'app', $apiUrl) . '/OMS/' . $this->getOrder()->getIncrementId();
        $this->getToolbar()->addChild(
            'deck_order_view',
            Button::class,
            [
                'label' => __('View on Deck Commerce'),
                'onclick' => 'setLocation(\'' . $deckUrl . '\')',
                'class' => 'primary',
                'sort_order' => 100
            ]
        );

        return $this;
    }
}
