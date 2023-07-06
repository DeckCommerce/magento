<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

/**
 * Config value backend model.
 */
class OrderExportCron extends Value
{

    const CRON_STRING_PATH = 'crontab/default/jobs/deck_order_export/schedule/cron_expr';

    const CRON_MODEL_PATH = 'crontab/default/jobs/deck_order_export/run/model';

    const XML_PATH_EXPORT_ENABLED   = 'groups/order/fields/export_frequency/value';
    const XML_PATH_EXPORT_FREQUENCY = 'groups/order/fields/export_frequency/value';
    const XML_PATH_IS_ENABLED       = 'groups/order/fields/enabled/value';
    const XML_PATH_SEND_IMMEDIATELY = 'groups/order/fields/send_immediately/value';

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @var string
     */
    protected $_runModelPath = '';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Cron settings after save
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave()
    {
        $frequency = $this->getData(self::XML_PATH_EXPORT_FREQUENCY);
        $isEnabledValue = $this->getData(self::XML_PATH_IS_ENABLED);
        $sendImmediately = $this->getData(self::XML_PATH_SEND_IMMEDIATELY);
        $isEnabled = $isEnabledValue && !$sendImmediately;

        $minute = $frequency === 60 ? '0' : "*/{$frequency}";

        if ($isEnabled) {
            $cronExprArray = [
                $minute,  # Minute
                '*',      # Hour
                '*',      # Day of the Month
                '*',      # Month of the Year
                '*',      # Day of the Week
            ];
            $cronExprString = join(' ', $cronExprArray);
        } else {
            $cronExprString = '';
        }

        try {
            $this->_configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();

            $this->_configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->_runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save the Cron expression.'));
        }
        return parent::afterSave();
    }
}
