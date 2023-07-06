<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Ui\Component\DataProvider;

use DeckCommerce\Integration\Model\MethodMap;
use DeckCommerce\Integration\Model\ResourceModel\MethodMap\CollectionFactory;
use Magento\Framework\App\RequestInterface;

/**
 * ShippingMap Data Provider UI component
 */
class ShippingMapDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
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
     * ShippingMapDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $mapCollectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $mapCollectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $mapCollectionFactory->create();
        $this->_request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get shipping methods mapping data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        $itemId = $this->_request->getParam(MethodMap::MAP_ID);

        if (!empty($itemId)) {
            /** @var $page \Magento\Cms\Model\Page */
            foreach ($items as $map) {
                $this->loadedData[$map->getId()]['map'] = $map->getData();
            }
        }

        return $this->loadedData;
    }
}
