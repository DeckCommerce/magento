<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Helper\Shipping;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Shipping Data helper
 */
class Data extends \Magento\Shipping\Helper\Data
{

    /**
     * @var UrlInterface|null
     */
    private $url;

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param StoreManagerInterface $storeManager
     * @param DeckHelper $helper
     * @param UrlInterface|null $url
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        StoreManagerInterface $storeManager,
        DeckHelper $helper,
        UrlInterface $url = null
    ) {
        $this->url    = $url ?: ObjectManager::getInstance()->get(UrlInterface::class);
        $this->helper = $helper;

        parent::__construct($context, $storeManager, $url);
    }

    /**
     * Decode url hash
     * Added order_id data for Deck Commerce order
     *
     * @param  string $hash
     * @return array
     */
    public function decodeTrackingHash($hash)
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::decodeTrackingHash($hash);
        }

        $hash = explode(':', $this->urlDecoder->decode($hash));
        if (count($hash) === 4 && in_array($hash[0], $this->_allowedHashKeys)) {
            return ['key' => $hash[0], 'id' => (int)$hash[1], 'hash' => $hash[2], 'order_id' => $hash[3]];
        }
        return [];
    }

    /**
     * Retrieve tracking url with params
     * Added order_id param for Deck Commerce order
     *
     * @param  string $key
     * @param  \Magento\Sales\Model\Order
     * |\Magento\Sales\Model\Order\Shipment|\Magento\Sales\Model\Order\Shipment\Track $model
     * @param  string $method Optional - method of a model to get id
     * @return string
     */
    protected function _getTrackingUrl($key, $model, $method = 'getId')
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::_getTrackingUrl($key, $model, $method);
        }

        $deckOrderId = $model->getOrderId() ?: $model->getId();
        $urlPart = "{$key}:{$model->{$method}()}:{$model->getProtectCode()}:{$deckOrderId}";
        $params = [
            '_scope' => $model->getStoreId(),
            '_nosid' => true,
            '_direct' => 'shipping/tracking/popup',
            '_query' => ['hash' => $this->urlEncoder->encode($urlPart)]
        ];

        return $this->url->getUrl('', $params);
    }
}
