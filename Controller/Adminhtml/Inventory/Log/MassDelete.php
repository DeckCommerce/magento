<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Adminhtml\Inventory\Log;

use DeckCommerce\Integration\Model\ResourceModel\InventoryLog as InventoryLogResource;
use DeckCommerce\Integration\Model\ResourceModel\InventoryLog\Collection;
use DeckCommerce\Integration\Model\ResourceModel\InventoryLog\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * MassDelete Controller
 */
class MassDelete extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DeckCommerce_Integration::deck_inventory';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var InventoryLogResource
     */
    protected $inventoryLogResource;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param InventoryLogResource $inventoryLogResource
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        InventoryLogResource $inventoryLogResource
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->inventoryLogResource = $inventoryLogResource;
        parent::__construct($context);
    }

    /**
     * Execute mass delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $size = $collection->getSize();

        foreach ($collection as $log) {
            $this->inventoryLogResource->delete($log);
        }

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been removed.', $size)
        );

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('deck/inventory_log/index');
    }
}
