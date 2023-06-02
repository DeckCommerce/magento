<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Plugin\CatalogInventory\Observer;

use DeckCommerce\Integration\Helper\Data as HelperData;
use DeckCommerce\Integration\Model\Import\InventoryCheck;
use Magento\CatalogInventory\Observer\QuantityValidatorObserver;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * QuantityValidatorObserverPlugin observer
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuantityValidatorObserverPlugin
{

    /**
     * @var InventoryCheck
     */
    protected $inventoryCheck;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var SourceItemsSaveInterface
     */
    protected $sourceItemsSaveInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    protected $sourceItemRepository;

    /**
     * @var StockResolverInterface
     */
    protected $stockResolver;

    /**
     * @var array
     */
    protected $validatedItems = [];

    /**
     * QuantityValidatorObserverPlugin constructor.
     *
     * @param InventoryCheck $inventoryCheck
     * @param HelperData $helper
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     */
    public function __construct(
        InventoryCheck $inventoryCheck,
        HelperData $helper,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository
    ) {
        $this->inventoryCheck = $inventoryCheck;
        $this->helper         = $helper;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
    }

    /**
     * Validate if product available for add to cart action
     *
     * @param $quoteItem
     * @param $deckQtys
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateOnPdp($quoteItem, $deckQtys)
    {
        if ($this->helper->isPdpAddToCartAction()) {
            $websiteCode = $quoteItem->getStore()->getWebsite()->getCode();
            $canBackorder = $this->helper->canBackorder($websiteCode, $quoteItem->getSku());
            $deckQty = array_shift($deckQtys);
            if ($deckQty < 1 && !$canBackorder) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Product that you are trying to add is not available.')
                );
            }
        }
    }

    /**
     * Validate products qty by data received from Deck Commerce
     *
     * @param QuantityValidatorObserver $subject
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(QuantityValidatorObserver $subject, Observer $observer)
    {
        if (!$this->helper->isEnabled() || !$this->helper->isActiveOnCurrentPage()) {
            return;
        }

        /* @var $quoteItem Item */
        $quoteItem = $observer->getEvent()->getItem();
        if (!$quoteItem ||
            !$quoteItem->getProductId() ||
            !$quoteItem->getQuote()
        ) {
            return;
        }

        if (!in_array($quoteItem->getSku(), $this->validatedItems)) {
            $skus = [];
            foreach ($quoteItem->getQuote()->getAllVisibleItems() as $item) {
                $skus[] = $item->getSku();
            }

            try {
                $deckQtys = $this->inventoryCheck->getDeckProductsQtyCached($skus);
                $this->updateInventory($deckQtys);
                $this->validatedItems = $skus;
            } catch (\Exception $e) {
                return;
            }

            $this->validateOnPdp($quoteItem, $deckQtys);
        }
    }

    /**
     * Update Magento inventory
     *
     * @param array $deckQtys
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    protected function updateInventory($deckQtys)
    {
        if ($deckQtys === null) {
            return;
        }

        $skus = array_keys($deckQtys);
        $sourceCode = $this->helper->getInventorySourceCode();

        $sourceItems = $this->getSourceItemsBySkus($skus, $sourceCode);
        $updatedSourceItems = [];
        foreach ($sourceItems as $sourceItemId => $sourceItem) {
            if (!isset($deckQtys[$sourceItem->getSku()])) {
                continue;
            }
            $deckProductQty = $deckQtys[$sourceItem->getSku()];
            if ($sourceItem->getQuantity() != $deckProductQty) {
                $sourceItem->setQuantity($deckProductQty);
                $sourceItem->setStatus(1);
                $updatedSourceItems[$sourceItemId] = $sourceItem;
            }
        }

        if (!empty($updatedSourceItems)) {
            $this->sourceItemsSaveInterface->execute($updatedSourceItems);
        }
    }

    /**
     * Load source items by skus
     *
     * @param array $productSkus
     * @param null $sourceCode
     * @return SourceItemInterface|SourceItemInterface[]|null
     */
    public function getSourceItemsBySkus($productSkus, $sourceCode = null)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, [$productSkus], 'in')
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();

        return $this->sourceItemRepository->getList($searchCriteria)->getItems();
    }
}
