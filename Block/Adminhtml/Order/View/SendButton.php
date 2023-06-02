<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
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
 * Block to display Send Button
 */
class SendButton extends View
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
     * SendButton constructor.
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
     * Add "Send To Deck Commerce" button on the admin order view page
     * Button is available only for orders with "Pending" deck_export_status value
     *
     * @return $this
     */
    public function addSendButton()
    {
        $parentBlock = $this->getParentBlock();

        if (!($parentBlock instanceof \Magento\Backend\Block\Template)
            || !$parentBlock->getOrderId()
            || !$this->helper->isOrderExportEnabled()
            || $this->deckOrder->isSuccess($this->getOrder())
            || $this->deckOrder->isSkipped($this->getOrder())
        ) {
            return $this;
        }

        $this->getToolbar()->addChild(
            'deck_send_order_button',
            Button::class,
            [
                'label' => __('Send To Deck Commerce'),
                'onclick' => "confirmSetLocation('Are you sure you want to send this order to Deck Commerce?',"
                    . " '{$this->getSendUrl()}')",
                'class' => 'primary',
                'sort_order' => 100
            ]
        );

        return $this;
    }

    /**
     * Get send to Deck Commerce Url
     *
     * @return string
     */
    public function getSendUrl()
    {
        return $this->urlBuilder->getUrl(
            'deck/order/send',
            ['order_id' => $this->getParentBlock()->getOrderId()]
        );
    }
}
