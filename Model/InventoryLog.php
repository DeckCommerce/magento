<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model;

/**
 * InventoryLog model
 */
class InventoryLog extends \Magento\Framework\Model\AbstractModel
{
    const ID         = 'id';
    const TYPE       = 'type';
    const STARTED_AT = 'started_at';
    const FILE       = 'file';
    const STATUS     = 'status';
    const MESSAGE    = 'message';

    const TYPE_FULL  = 0;
    const TYPE_DELTA = 1;

    const STATUS_PROGRESS = 0;
    const STATUS_SUCCESS  = 1;
    const STATUS_FAILED   = 2;

    protected $messages = [];

    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init(\DeckCommerce\Integration\Model\ResourceModel\InventoryLog::class);
    }

    /**
     * Get inventory log Id
     *
     * @return array|mixed|null
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Set inventory log Id
     *
     * @param $id
     */
    public function setId($id)
    {
        $this->setData(self::ID, $id);
    }

    /**
     * Get inventory type: full or delta
     *
     * @return array|mixed|null
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * Set inventory type: full or delta
     *
     * @param $type
     */
    public function setType($type)
    {
        $this->setData(self::TYPE, $type);
    }

    /**
     * Get started at date
     *
     * @return array|mixed|null
     */
    public function getStartedAt()
    {
        return $this->getData(self::STARTED_AT);
    }

    /**
     * Set started at date
     *
     * @param $startedAt
     */
    public function setStartedAt($startedAt)
    {
        $this->setData(self::STARTED_AT, $startedAt);
    }

    /**
     * Get source file
     *
     * @return array|mixed|null
     */
    public function getFile()
    {
        return $this->getData(self::FILE);
    }

    /**
     * Set source file
     *
     * @param $file
     */
    public function setFile($file)
    {
        $this->setData(self::FILE, $file);
    }

    /**
     * Get import status
     *
     * @return array|mixed|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set import status
     *
     * @param $status
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
    }

    /**
     * Get import details message
     *
     * @return array|mixed|null
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set import details message
     *
     * @param $message
     */
    public function setMessage($message)
    {
        $this->setData(self::MESSAGE, $message);
    }

    /**
     * Add log message
     *
     * @param $message
     */
    public function addMessage($message)
    {
        $this->setMessage($this->getMessage() . ' ' . $message);
    }
}
