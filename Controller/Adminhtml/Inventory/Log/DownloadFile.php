<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Controller\Adminhtml\Inventory\Log;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use DeckCommerce\Integration\Model\InventoryLogFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Backend\Model\View\Result\Redirect;

/**
 * DownloadFile Controller
 */
class DownloadFile extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DeckCommerce_Integration::deck_inventory';

    /**
     * @var InventoryLogFactory
     */
    protected $inventoryLogFactory;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var File
     */
    protected $file;

    /**
     * DownloadFile constructor.
     * @param Context $context
     * @param InventoryLogFactory $inventoryLogFactory
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param DeckHelper $helper
     * @param File $file
     */
    public function __construct(
        Context $context,
        InventoryLogFactory $inventoryLogFactory,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        DeckHelper $helper,
        File $file
    ) {
        $this->inventoryLogFactory = $inventoryLogFactory;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->helper = $helper;
        $this->file = $file;
        parent::__construct($context);
    }

    /**
     * Execute Download inventory history file action
     *
     * @return Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $id            = (int)$this->getRequest()->getParam('id');
            $inventoryLog  = $this->inventoryLogFactory->create()->load($id);
            $inventoryType = $this->helper->getInventoryTypeByLogType($inventoryLog->getType());
            $fileName      = $inventoryLog->getFile();
            if (!$fileName) {
                throw new LocalizedException(__('Empty file name.'));
            }

            $historyVarDir   = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $historyDir      = $this->helper->getInventoryHistoryDirectory($inventoryType);
            $historyFilePath = $historyVarDir->getAbsolutePath($historyDir) . '/' . $fileName;

            $content = $this->file->fileGetContents($historyFilePath);

            return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('File is not available. ' . $e->getMessage());

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('deck/inventory_log/index');
        }
    }
}
