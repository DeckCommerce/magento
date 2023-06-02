<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model;

/**
 * DeckMethod model
 */
class DeckMethod extends \Magento\Framework\Model\AbstractModel
{

    const DECK_METHOD_ID   = 'deck_method_id';
    const DECK_METHOD_NAME = 'deck_method_name';
    const IS_ENABLED       = 'is_enabled';

    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init(\DeckCommerce\Integration\Model\ResourceModel\DeckMethod::class);
    }

    /**
     * Returns the Deck Commerce method ID.
     *
     * @return int Deck Commerce Method ID.
     */
    public function getDeckMethodId()
    {
        return $this->getData(self::DECK_METHOD_ID);
    }

    /**
     * Set Deck Commerce method ID
     *
     * @param $deckMethodId
     */
    public function setDeckMethodId($deckMethodId)
    {
        $this->setData(self::DECK_METHOD_ID, $deckMethodId);
    }

    /**
     * Returns the Deck Commerce method name.
     *
     * @return int Deck Commerce Method Name.
     */
    public function getDeckMethodName()
    {
        return $this->getData(self::DECK_METHOD_NAME);
    }

    /**
     * Set Deck Commerce method name.
     *
     * @param $deckMethodName
     */
    public function setDeckMethodName($deckMethodName)
    {
        $this->setData(self::DECK_METHOD_NAME, $deckMethodName);
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
