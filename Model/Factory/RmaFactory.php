<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2021 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Factory;

/**
 * Factory class for @see \Magento\Rma\Model\Rma
 * Used not to have conflict in Magento CE
 */
class RmaFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\Rma\\Model\\Rma')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Magento\Rma\Model\Rma
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
