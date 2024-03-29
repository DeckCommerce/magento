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
use Magento\Store\Model\ScopeInterface;

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
        $exportEnabledStoreIds = $this->helper->getOrderExportEnabledStoreIds();
        if (!empty($exportEnabledStoreIds)) {
            foreach ($exportEnabledStoreIds as $storeId) {

                $excludingStatuses =
                    $this->helper->getExcludedOrderStates(ScopeInterface::SCOPE_STORE, $storeId);

                $collection = $this->orderCollectionFactory->create();
                $collection->addFieldToFilter(DeckOrder::EXPORT_STATUS, ['eq' => DeckOrder::STATUS_PENDING]);
                if (!empty($excludingStatuses)) {
                    $collection->addFieldToFilter('state', ['nin' => $excludingStatuses]);
                }

                $collection->addFieldToFilter('store_id', $storeId);
                $collection->setOrder('entity_id', $collection::SORT_ORDER_ASC);
                $collection->setPageSize(10);
                $collection->setCurPage(1);

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
