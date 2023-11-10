<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;
use Magento\Inventory\Model\ResourceModel\Source\Collection as SourceCollection;
use Magento\Inventory\Model\ResourceModel\Source\CollectionFactory as SourceCollectionFactory;

/**
* Class SourceColumn
 */
class SourceColumn extends Select
{

    /**
     * @var SourceCollectionFactory
     */
    private $sourceCollectionFactory;

    /**
     * Construct method
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param SourceCollectionFactory $sourceCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        SourceCollectionFactory $sourceCollectionFactory,
        array $data = []
    ) {
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Get options for select element
     *
     * @return array
     */
    public function getOptions()
    {
        if (empty($this->_options)) {
            $this->_options = $this->getSourceOptions();
        }
        return parent::getOptions();
    }

    /**
     * Get inventory sources array
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        /** @var SourceCollection $sourceCollection */
        $sourceCollection = $this->sourceCollectionFactory->create();
        $sourceCollection->addFieldToSelect(['source_code', 'name']);

        $options = [];
        foreach ($sourceCollection as $source) {
            $options[] = [
                'value' => $source->getSourceCode(),
                'label' => sprintf("%s (%s)", $source->getName(), $source->getSourceCode()),
            ];
        }

        return $options;
    }
}
