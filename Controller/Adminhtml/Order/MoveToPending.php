<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use DeckCommerce\Integration\Model\Export\Order as ExportOrder;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;

/**
 * MoveToPending  Controller
 */
class MoveToPending extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DeckCommerce_Integration::deck_order';

    /**
     * MoveToPending constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $filter);
    }

    /**
     * Move to pending status mass action
     *
     * @param AbstractCollection $collection
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    protected function massAction(AbstractCollection $collection)
    {
        $orderIds = $collection->getAllIds();

        $bind = [ExportOrder::EXPORT_STATUS => ExportOrder::STATUS_PENDING];
        $where = ['entity_id IN (?)' => $orderIds, ExportOrder::EXPORT_STATUS => ExportOrder::STATUS_FAILED];
        $collection->getConnection()->update($collection->getTable('sales_order'), $bind, $where);
        $updatedCnt = $collection->getConnection()->update($collection->getTable('sales_order_grid'), $bind, $where);

        if ($updatedCnt) {
            $this->messageManager->addSuccessMessage(
                __('You have moved %1 order(s) to Pending Deck Commerce Sync status.', $updatedCnt));
        } else {
            $this->messageManager->addWarningMessage(
                __('No order(s) were moved Pending Deck Commerce Sync status.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
    }
}
