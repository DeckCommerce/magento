<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Cron;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Export\Order as DeckOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Send Orders to Deck Commerce by cron
 */
class OrderExportScheduled
{

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var DeckOrder
     */
    protected $deckOrder;

    /**
     * OrderExportScheduled constructor.
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param DeckHelper $helper
     * @param DeckOrder $deckOrder
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        DeckHelper $helper,
        DeckOrder $deckOrder
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper                 = $helper;
        $this->deckOrder              = $deckOrder;
    }

    /**
     * Execute order export cron
     *
     * @return array|false
     */
    public function execute()
    {
        if ($this->helper->isOrderExportEnabled()) {
            $collection = $this->orderCollectionFactory->create();
            $collection
                ->addFieldToFilter(DeckOrder::EXPORT_STATUS, ['eq' => DeckOrder::STATUS_PENDING])
                ->addFieldToFilter('state', ['nin' => [
                    Order::STATE_COMPLETE,
                    Order::STATE_CLOSED,
                    Order::STATE_CANCELED,
                    Order::STATE_HOLDED
                ]]);

            $processedIds = [];
            $failedIds = [];
            /** @var $order Order */
            foreach ($collection as $order) {
                try {
                    $this->deckOrder->send($order);
                } catch (\Exception $e) {
                    $failedIds[] = $order->getIncrementId();
                }

                $processedIds[] = $order->getIncrementId();
            }

            $successMsg = __('Orders Have Been Exported To Deck Commerce: %1. ', implode(', ', $processedIds));
            $failedMsg = '';
            if (!empty($failedIds)) {
                $failedMsg = __('Unable To Export Orders To Deck Commerce: %1', implode(', ', $failedIds));
            }
            return ['message' => $successMsg . $failedMsg];
        }

        return false;
    }
}
