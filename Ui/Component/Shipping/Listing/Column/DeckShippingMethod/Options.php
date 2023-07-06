<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Ui\Component\Shipping\Listing\Column\DeckShippingMethod;

use DeckCommerce\Integration\Model\ResourceModel\DeckMethod\Collection;
use DeckCommerce\Integration\Model\ResourceModel\DeckMethod\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * DeckShippingMethod Options
 */
class Options implements OptionSourceInterface
{

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Options constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Prepare Manage Shipping Methods source options
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_enabled', 1);

        $options[] = ['value' => '', 'label' => ''];
        foreach ($collection as $item) {
            $options[] = [
                'value' => $item->getDeckMethodId(),
                'label' => $item->getDeckMethodName()
            ];
        }

        return $options;
    }
}
