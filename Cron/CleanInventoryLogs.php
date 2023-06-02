<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Cron;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\InventoryLog;
use DeckCommerce\Integration\Model\ResourceModel\InventoryLog as InventoryLogResource;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime;

/**
 * CleanInventoryLogs Cron model
 */
class CleanInventoryLogs
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var InventoryLogResource
     */
    protected $inventoryLogResource;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * CleanInventoryLogs constructor.
     * @param DeckHelper $helper
     * @param InventoryLogResource $inventoryLogResource
     * @param Filesystem $filesystem
     * @param DateTime $dateTime
     */
    public function __construct(
        DeckHelper $helper,
        InventoryLogResource $inventoryLogResource,
        Filesystem $filesystem,
        DateTime $dateTime
    ) {
        $this->helper               = $helper;
        $this->inventoryLogResource = $inventoryLogResource;
        $this->dateTime             = $dateTime;
        $this->filesystem           = $filesystem;
    }

    /**
     * Execute clean inventory logs cron
     *
     * @return array|false
     */
    public function execute()
    {
        if ($this->helper->isInventoryImportEnabled($this->helper::INVENTORY_TYPE_FULL)) {
            try {
                $currDate = $this->dateTime->formatDate(true);

                $this->removeFullInventoryLogs($currDate);
                $this->removeDeltaInventoryLogs($currDate);

                return ['message' => __('Deck Commerce: Old logs have been removed')];
            } catch (\Exception $e) {
                return ['message' => __('Deck Commerce: Unable to delete old logs: ' . $e->getMessage())];
            }
        }

        return false;
    }

    /**
     * Remove inventory history files
     *
     * @param string $currDate
     * @param string $type
     */
    protected function removeInventoryHistoryFiles($currDate, $type = DeckHelper::INVENTORY_TYPE_FULL)
    {
        $lifetime = $this->helper->getInventoryLogsLifetime($type);

        try {
            $logType   = $type === DeckHelper::INVENTORY_TYPE_FULL ? InventoryLog::TYPE_FULL : InventoryLog::TYPE_DELTA;
            $fileNames = $this->inventoryLogResource
                ->loadFileNamesForOldLogs($currDate, $lifetime, $logType);
            if (empty($fileNames)) {
                return;
            }

            $historyDir    = $this->helper->getInventoryHistoryDirectory(DeckHelper::INVENTORY_TYPE_FULL);
            $historyVarDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            if (!$historyVarDir->isExist($historyDir)) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        foreach ($fileNames as $file) {
            if (!trim($file)) {
                continue;
            }
            try {
                $historyFilePath = $historyVarDir->getAbsolutePath($historyDir) . DS . $file;
                $historyVarDir->delete($historyFilePath);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * Remove full inventory logs older than $lifetime (in days)
     *
     * @param string $currDate
     * @throws LocalizedException
     */
    public function removeFullInventoryLogs($currDate)
    {
        $lifetime = $this->helper->getInventoryLogsLifetime(DeckHelper::INVENTORY_TYPE_FULL);

        $this->removeInventoryHistoryFiles($currDate, DeckHelper::INVENTORY_TYPE_FULL);

        $this->inventoryLogResource->deleteOldLogs($currDate, $lifetime, InventoryLog::TYPE_FULL);
    }

    /**
     * Remove delta inventory logs older than $lifetime (in days
     *
     * @param string $currDate
     * @throws LocalizedException
     */
    public function removeDeltaInventoryLogs($currDate)
    {
        $lifetime = $this->helper->getInventoryLogsLifetime(DeckHelper::INVENTORY_TYPE_DELTA);

        $this->removeInventoryHistoryFiles($currDate, DeckHelper::INVENTORY_TYPE_DELTA);

        $this->inventoryLogResource->deleteOldLogs($currDate, $lifetime, InventoryLog::TYPE_DELTA);
    }
}
