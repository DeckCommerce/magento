<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Plugin\Block\Sales\Adminhtml\Order;

use DeckCommerce\Integration\Helper\Data as HelperData;
use DeckCommerce\Integration\Model\Export\Order as DeckOrder;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

/**
 * Order view plugin class
 */
class ViewPlugin
{

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var DeckOrder
     */
    protected $deckOrder;

    /**
     * @var string[]
     */
    protected $restrictedButtonsList = [
        'order_cancel',
        'order_creditmemo',
        'void_payment',
        'order_hold',
        'order_unhold',
        'order_invoice',
        'order_ship'
    ];

    /**
     * ViewPlugin constructor.
     *
     * @param HelperData $helper
     * @param DeckOrder $deckOrder
     */
    public function __construct(HelperData $helper, DeckOrder $deckOrder)
    {
        $this->helper = $helper;
        $this->deckOrder  = $deckOrder;
    }

    /**
     * Validate products qty by data received from Deck Commerce
     *
     * @param OrderView $subject
     * @param string $buttonId
     * @param array $data
     * @param integer $level
     * @param integer $sortOrder
     * @param string|null $region That button should be displayed in ('toolbar', 'header', 'footer', null)
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAddButton(
        OrderView $subject,
        $buttonId,
        $data,
        $level = 0,
        $sortOrder = 0,
        $region = 'toolbar'
    ) {
        $order = $subject->getOrder();
        if (!$order || !$this->helper->isOrderExportEnabled() || !$this->deckOrder->isSuccess($order)) {
            return [$buttonId, $data, $level, $sortOrder, $region];
        }

        if (in_array($buttonId, $this->restrictedButtonsList)) {
            $deckCommerceOrderUrl = $this->helper->getDeckCommerceOrderUrl($order->getIncrementId());
            $message = __('Please perform this action from the Deck Commerce application. Go to Deck Commerce order?');
            $data['onclick'] = "confirmSetLocation('{$message}', '{$deckCommerceOrderUrl}')";
            if (isset($data['id'])) {
                unset($data['id']);
            }
            if (isset($data['data_attribute'])) {
                unset($data['data_attribute']);
            }
        }

        return [$buttonId, $data, $level, $sortOrder, $region];
    }
}
