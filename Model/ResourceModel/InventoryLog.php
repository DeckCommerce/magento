<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use DeckCommerce\Integration\Model\InventoryLog as InventoryLogModel;

/**
 * InventoryLog resource model
 */
class InventoryLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $filterManager;

    /**
     * InventoryLog constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param string|null $connectionName
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Filter\FilterManager $filterManager,
        $connectionName = null
    ) {
        $this->filterManager = $filterManager;
        parent::__construct($context, $connectionName);
    }

    /**
     * Model initialization
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init('deck_inventory_log', 'id');
    }

    /**
     * Load file names of inventory logs that are expired and will be deleted
     *
     * @param string $currentDate
     * @param string $logsLifetime
     * @param int $type
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadFileNamesForOldLogs($currentDate, $logsLifetime, $type = InventoryLogModel::TYPE_FULL)
    {
        $connection = $this->getConnection();
        $select = $connection
            ->select()
            ->from($this->getMainTable(), 'file')
            ->where('type = ?', $type);

        $archivePeriodExpr = $connection->getDateSubSql(
            $connection->quote($currentDate),
            (int)$logsLifetime,
            AdapterInterface::INTERVAL_DAY
        );
        $select->where($archivePeriodExpr . ' >= started_at');

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Delete logs with expired lifetime
     *
     * @param string $currentDate
     * @param int $LogsLifetime - in days
     * @param string $type
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteOldLogs($currentDate, $LogsLifetime, $type = InventoryLogModel::TYPE_FULL)
    {
        $connection = $this->getConnection();
        $archivePeriodExpr = $connection->getDateSubSql(
            $connection->quote($currentDate),
            (int)$LogsLifetime,
            AdapterInterface::INTERVAL_DAY
        );
        $connection->delete($this->getMainTable(), $archivePeriodExpr . ' >= started_at AND type=' . $type);
    }
}
