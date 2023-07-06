<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Sales\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Helper\Sales\Reorder as DeckReorderHelper;
use DeckCommerce\Integration\Model\Data\Collection;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollectionFactory;

/**
 * Order Items Block
 */
class Items extends \Magento\Sales\Block\Order\Items
{

    /**
     * @var Collection
     */
    private $itemCollection;

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DeckReorderHelper
     */
    protected $deckReorderHelper;

    /**
     * Items constructor.
     * @param Context $context
     * @param Registry $registry
     * @param DeckHelper $helper
     * @param CollectionFactory $collectionFactory
     * @param DeckReorderHelper $deckReorderHelper
     * @param array $data
     * @param ItemCollectionFactory|null $itemCollectionFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DeckHelper $helper,
        CollectionFactory $collectionFactory,
        DeckReorderHelper $deckReorderHelper,
        array $data = [],
        ItemCollectionFactory $itemCollectionFactory = null
    ) {
        $this->helper            = $helper;
        $this->collectionFactory = $collectionFactory;
        $this->deckReorderHelper = $deckReorderHelper;

        parent::__construct($context, $registry, $data, $itemCollectionFactory);
    }

    /**
     * Init pager block and item collection with page size and current page number
     * @return $this
     */
    protected function _prepareLayout()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::_prepareLayout();
        }

        /** @var Collection itemCollection */
        $this->itemCollection = $this->getOrder()->getData('items_collection');
        $this->deckReorderHelper->setOrder($this->getOrder());

        return $this;
    }

    /**
     * Get visible items for current page.
     *
     * To be called from templates(after _prepareLayout()).
     *
     * @return \Magento\Framework\DataObject[] | Collection
     * @since 100.1.7
     */
    public function getItems()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::getItems();
        }

        return $this->itemCollection;
    }

    /**
     * Determine if the pager should be displayed for order items list.
     *
     * @return bool
     * @since 100.1.7
     */
    public function isPagerDisplayed()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::isPagerDisplayed();
        }

        return false;
    }
}
