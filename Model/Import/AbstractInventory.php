<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Import;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\InventoryLog;
use DeckCommerce\Integration\Model\InventoryLogFactory;
use DeckCommerce\Integration\Model\ResourceModel\InventoryLog as InventoryLogResource;
use DeckCommerce\Integration\Model\ResourceModel\InventoryLog\CollectionFactory as InventoryLogCollectionFactory;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventoryImportExport\Model\Import\Command\Append as ImportAppend;

/**
 * AbstractInventory Import Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractInventory
{
    const IMPORT_CHUNK_SIZE = 1000;

    const MAX_PROGRESS_TIME_HRS = 10;

    /**
     * @var string
     */
    protected $inventoryType;

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var InventoryLogFactory
     */
    protected $inventoryLogFactory;

    /**
     * @var InventoryLogCollectionFactory
     */
    protected $inventoryLogCollectionFactory;

    /**
     * @var InventoryLog
     */
    protected $inventoryLog;

    /**
     * @var InventoryLogResource
     */
    protected $inventoryLogResource;

    /**
     * @var ImportAppend
     */
    protected $importAppend;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Sftp
     */
    protected $sftpAdapter;

    /**
     * @var string[]
     */
    protected $fieldsMapping = [
        'sku'       => 'SKU',
        'quantity'  => 'Quantity'
    ];

    protected $logData = [];

    /**
     * AbstractInventory constructor.
     * @param DeckHelper $helper
     * @param InventoryLogFactory $inventoryLogFactory
     * @param InventoryLogResource $inventoryLogResource
     * @param InventoryLogCollectionFactory $inventoryLogCollectionFactory
     * @param ImportAppend $importAppend
     * @param DateTime $dateTime
     * @param Filesystem $filesystem
     * @param Sftp $sftpAdapter
     */
    public function __construct(
        DeckHelper $helper,
        InventoryLogFactory $inventoryLogFactory,
        InventoryLogResource $inventoryLogResource,
        InventoryLogCollectionFactory $inventoryLogCollectionFactory,
        ImportAppend $importAppend,
        DateTime $dateTime,
        Filesystem $filesystem,
        Sftp $sftpAdapter
    ) {
        $this->helper                        = $helper;
        $this->inventoryLogFactory           = $inventoryLogFactory;
        $this->inventoryLogResource          = $inventoryLogResource;
        $this->inventoryLogCollectionFactory = $inventoryLogCollectionFactory;
        $this->importAppend                  = $importAppend;
        $this->dateTime                      = $dateTime;
        $this->filesystem                    = $filesystem;
        $this->sftpAdapter                   = $sftpAdapter;
    }

    /**
     * Scan source directory by file mask and download source files to Magento
     *
     * @return array
     * @throws \Exception
     */
    public function scanFiles()
    {
        $files = $this->downloadSftpFiles();

        return $files;
    }

    /**
     * Read source file data
     *
     * @param string $file
     * @return array
     */
    public function getFileData($file)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stockItems = [];

        foreach ($rows as $i => $row) {
            $row = explode("\t", $row);
            if (!$i) {
                $header = $row;
                continue;
            }

            $stockItem = $this->prepareStockItem($row, $header, $stockItems);

            $stockItems[$stockItem['sku']] = $stockItem;
        }

        return $stockItems;
    }

    /**
     * Prepare stock item record for import
     *
     * @param array $row
     * @param array $header
     * @param array $stockItems
     * @return array
     */
    protected function prepareStockItem($row, $header, $stockItems)
    {
        $itemData = array_combine($header, $row);

        $item['source_code'] = $this->helper->getInventorySourceCode();
        $item['sku']         = $itemData[$this->fieldsMapping['sku']];
        $item['quantity']    = $itemData[$this->fieldsMapping['quantity']];

        $sku = $item['sku'];
        if (isset($stockItems[$sku]) && $stockItems[$sku]['quantity'] > 0) {
            $item['quantity'] += $stockItems[$sku]['quantity'];
        }

        $item['status'] = $item['quantity'] > 0 ? StockStatus::STATUS_IN_STOCK : StockStatus::STATUS_OUT_OF_STOCK;

        return $item;
    }

    /**
     * Run inventory import
     *
     * @param $stockItems
     */
    protected function runImport($stockItems)
    {
        foreach (array_chunk($stockItems, self::IMPORT_CHUNK_SIZE) as $chunk) {
            $this->importAppend->execute($chunk);
        }
    }

    /**
     * Get difference between dates
     *
     * @param string $dateTime
     * @return false|float
     */
    public function getDateDifferenceInHours($dateTime)
    {
        $now = $this->dateTime->gmtDate();
        $dateDiff = strtotime($now) - strtotime($dateTime);
        return floor($dateDiff/(60*60));
    }

    /**
     * Check whether other imports are running now and block new import
     * Added possibility to switch "progress" status to "failed" automatically for possible stuck imports
     * if they're older than MAX_PROGRESS_TIME
     *
     * @return bool
     */
    public function canRun()
    {
        $inventoryLogCollection = $this->inventoryLogCollectionFactory->create();
        $inventoryLogCollection->addFieldToFilter('status', InventoryLog::STATUS_PROGRESS);
        foreach ($inventoryLogCollection as $inventoryLog) {
            if ($this->getDateDifferenceInHours($inventoryLog->getStartedAt()) > self::MAX_PROGRESS_TIME_HRS) {
                $inventoryLog->setStatus(InventoryLog::STATUS_FAILED);
                $inventoryLog->setMessage('Stuck import');
                $inventoryLog->save();
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Add record to inventory log table
     *
     * @param string $message
     * @param false $initLog
     * @param false $saveLog
     * @param string $file
     * @param int $status
     */
    protected function addToLog(
        $message,
        $initLog = false,
        $saveLog = false,
        $file = '',
        $status = InventoryLog::STATUS_PROGRESS
    ) {
        try {
            if (($this->inventoryLog === null) || $initLog) {
                $this->inventoryLog = $this->inventoryLogFactory->create();
                $this->inventoryLog->setStartedAt($this->dateTime->date());
            }
            if ($file) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $this->inventoryLog->setFile(basename($file));
            }
            $this->inventoryLog->setType((int) ($this->inventoryType === DeckHelper::INVENTORY_TYPE_DELTA));
            $this->inventoryLog->setStatus($status);
            $this->inventoryLog->addMessage($message);
            if ($saveLog) {
                $this->inventoryLogResource->save($this->inventoryLog);
            }
            if ($this->inventoryLog === null) {
                $this->inventoryLog = null;
            }
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Filter inventory file by file prefix from config
     *
     * @param array $files
     * @return array
     */
    protected function filterInventoryFiles($files)
    {
        $filePrefix = $this->helper->getInventorySourceFilePrefix($this->inventoryType);
        $files = array_keys($files);
        sort($files);
        $filteredFiles = [];
        foreach ($files as $file) {
            if (in_array($file, ['.', '..']) || strpos($file, $filePrefix) !== 0) {
                continue;
            }
            $filteredFiles[] = $file;
        }

        return $filteredFiles;
    }

    /**
     * Check if current file is last in array.
     * It's used to get the newest file by name
     *
     * @param array $files
     * @param string $file
     * @return bool
     */
    protected function isOutOfDateInventoryFile($files, $file)
    {
        if ($this->inventoryType === DeckHelper::INVENTORY_TYPE_FULL) {
            $lastKey = array_key_last($files);

            return $files[$lastKey] !== $file;
        }

        return false;
    }

    /**
     * Download source files from SFTP and remove there
     *
     * If inventory type is Full, the only last file is necessary for import,
     * All another files will be just removed as useless
     *
     * If inventory type is Delta, the all files must be processed and the sort order must be ASC
     * (creation date is in file name)
     *
     * @return array
     * @throws \Exception
     */
    public function downloadSftpFiles()
    {
        $sftpDir    = $this->helper->getInventorySftpDirectory($this->inventoryType);
        $historyDir = $this->helper->getInventoryHistoryDirectory($this->inventoryType);
        try {
            $historyVarDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            if (!$historyVarDir->isExist($historyDir)) {
                $historyVarDir->create($historyDir);
            }
            $this->sftpAdapter->open($this->getSftpCredentials());
            $this->sftpAdapter->cd($sftpDir);
            $files = $this->sftpAdapter->rawls();

            $files = $this->filterInventoryFiles($files);

            $downloadedFiles = [];
            foreach ($files as $file) {
                if ($this->isOutOfDateInventoryFile($files, $file)) {
                    $this->sftpAdapter->rm($file);
                    continue;
                }
                $historyFilePath = $historyVarDir->getAbsolutePath($historyDir) . '/' . $file;
                $result = $this->sftpAdapter->read($file, $historyFilePath);
                if (!$result) {
                    throw new LocalizedException(__('SFTP source file is empty or can\'t be read.'));
                }

                $this->sftpAdapter->rm($file);
                $downloadedFiles[] = $historyVarDir->getAbsolutePath($historyFilePath);
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__("We can't read the import file. {$e->getMessage()}"));
        }

        return $downloadedFiles;
    }

    /**
     * Prepare array of sftp credentials and check if not empty
     *
     * @return array
     * @throws LocalizedException
     */
    protected function getSftpCredentials()
    {
        $host     = $this->helper->getInventorySftpHost($this->inventoryType);
        $username = $this->helper->getInventorySftpUsername($this->inventoryType);
        $password = $this->helper->getInventorySftpPassword($this->inventoryType);

        if (!$host || !$username || !$password) {
            throw new LocalizedException(__('Empty SFTP credentials'));
        }

        return [
            'host'     => $host,
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * Execute inventory import from Deck
     *
     * @return bool
     */
    public function execute()
    {
        try {
            if (!$this->canRun()) {
                $logMsg = "Unable to start import because other import is running";
                $this->addToLog($logMsg, false, true, '', InventoryLog::STATUS_FAILED);
                return false;
            }

            foreach ($this->scanFiles() as $file) {
                $this->processInventoryFile($file);
            }
            return true;
        } catch (\Exception $e) {
            $this->addToLog("Unable to import. {$e->getMessage()}", false, true, '', InventoryLog::STATUS_FAILED);
            return false;
        }
    }

    /**
     * Process import for inventory file
     *
     * @param string $file
     */
    public function processInventoryFile($file)
    {
        $time = $time = microtime(true);

        $this->addToLog("Start of importing.", true, false, $file);

        $stockItems = $this->getFileData($file);
        if (!$stockItems) {
            $this->addToLog(
                "Unable to read source file.",
                false,
                true,
                $file,
                InventoryLog::STATUS_FAILED
            );
            return;
        }
        try {
            $itemsCount = count($stockItems);
            $this->runImport($stockItems);

            $executionTimeMsg = "Execution time: " . round((microtime(true) - $time), 2);

            $this->addToLog(
                "Import has been finished. Processed {$itemsCount} items. {$executionTimeMsg} sec.",
                false,
                true,
                $file,
                InventoryLog::STATUS_SUCCESS
            );
        } catch (\Exception $e) {
            $this->addToLog(
                "Unable to import. {$e->getMessage()}",
                false,
                true,
                $file,
                InventoryLog::STATUS_FAILED
            );
        }
    }
}
