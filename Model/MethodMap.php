<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model;

/**
 * MethodMap model
 */
class MethodMap extends \Magento\Framework\Model\AbstractModel
{
    const MAP_ID         = 'map_id';
    const METHOD         = 'method';
    const DECK_METHOD_ID = 'deck_method_id';
    const IS_ENABLED     = 'is_enabled';

    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init(\DeckCommerce\Integration\Model\ResourceModel\MethodMap::class);
    }

    /**
     * Get shipping methods Map Id
     *
     * @return array|mixed|null
     */
    public function getMapId()
    {
        return $this->getData(self::MAP_ID);
    }

    /**
     * Set shipping methods Map Id
     *
     * @param $mapId
     */
    public function setMapId($mapId)
    {
        $this->setData(self::MAP_ID, $mapId);
    }

    /**
     * Get shipping method
     *
     * @return array|mixed|null
     */
    public function getMethod()
    {
        return $this->getData(self::METHOD);
    }

    /**
     * Set shipping method
     *
     * @param $method
     */
    public function setMethod($method)
    {
        $this->setData(self::METHOD, $method);
    }

    /**
     * Get Deck method id
     *
     * @return array|mixed|null
     */
    public function getDeckMethodId()
    {
        return $this->getData(self::DECK_METHOD_ID);
    }

    /**
     * Set Deck method id
     *
     * @param $deckMethodId
     */
    public function setDeckMethodId($deckMethodId)
    {
        $this->setData(self::DECK_METHOD_ID, $deckMethodId);
    }

    /**
     * Get is enabled flag.
     *
     * @return array|mixed|null
     */
    public function getIsEnabled()
    {
        return $this->getData(self::IS_ENABLED);
    }

    /**
     * Set is enabled flag.
     *
     * @param $isEnabled
     */
    public function setIsEnabled($isEnabled)
    {
        $this->setData(self::IS_ENABLED, $isEnabled);
    }
}
