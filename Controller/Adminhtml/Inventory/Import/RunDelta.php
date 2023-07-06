<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Inventory\Import;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\DeltaInventory as ImportInventory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * RunDelta Controller
 */
class RunDelta extends Action implements HttpGetActionInterface
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DeckCommerce_Integration::deck_inventory';

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var ImportInventory
     */
    protected $importInventory;

    /**
     * RunDelta constructor.
     * @param Context $context
     * @param DeckHelper $helper
     * @param ImportInventory $importInventory
     */
    public function __construct(
        Context $context,
        DeckHelper $helper,
        ImportInventory $importInventory
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->importInventory = $importInventory;
    }

    /**
     * Execute Run Delta inventory action
     */
    public function execute(): ResultInterface
    {
        if ($this->helper->isInventoryImportEnabled($this->helper::INVENTORY_TYPE_DELTA)) {
            $result = $this->importInventory->execute();
            if ($result) {
                $this->messageManager->addSuccessMessage(
                    __('Delta inventory import has been finished.')
                );
            } else {
                $this->messageManager->addErrorMessage(
                    __('Unable to import delta inventory.')
                );
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('deck/inventory_log/index');
    }
}
