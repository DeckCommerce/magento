<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Ui\Component\DataProvider;

use DeckCommerce\Integration\Model\DeckMethod;
use DeckCommerce\Integration\Model\ResourceModel\DeckMethod\CollectionFactory;
use Magento\Framework\App\RequestInterface;

/**
 * ShippingMethod Data Provider UI component
 */
class ShippingMethodDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * ShippingMethodDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $methodCollectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $methodCollectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $methodCollectionFactory->create();
        $this->_request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get shipping methods data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        $itemId = $this->_request->getParam(DeckMethod::DECK_METHOD_ID);

        if (!empty($itemId)) {
            /** @var $page \Magento\Cms\Model\Page */
            foreach ($items as $method) {
                $this->loadedData[$method->getId()]['method'] = $method->getData();
            }
        }

        return $this->loadedData;
    }
}
