<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Shipping;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Import\OrderHistory;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Shipment;

/**
 * Shipping Info
 */
class Info extends \Magento\Shipping\Model\Info
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var OrderHistory
     */
    protected $orderHistory;

    /**
     * Info constructor.
     * @param \Magento\Shipping\Helper\Data $shippingData
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Shipping\Model\Order\TrackFactory $trackFactory
     * @param \Magento\Shipping\Model\ResourceModel\Order\Track\CollectionFactory $trackCollectionFactory
     * @param OrderHistory $orderHistory
     * @param DeckHelper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Shipping\Helper\Data $shippingData,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Shipping\Model\Order\TrackFactory $trackFactory,
        \Magento\Shipping\Model\ResourceModel\Order\Track\CollectionFactory $trackCollectionFactory,
        OrderHistory $orderHistory,
        DeckHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->orderHistory = $orderHistory;

        parent::__construct(
            $shippingData,
            $orderFactory,
            $shipmentRepository,
            $trackFactory,
            $trackCollectionFactory,
            $data
        );
    }

    /**
     * Generating tracking info
     *
     * @param array $hash
     * @return $this
     */
    public function loadByHash($hash)
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::loadByHash($hash);
        }

        /* @var $helper \Magento\Shipping\Helper\Data */
        $helper = $this->_shippingData;
        $data = $helper->decodeTrackingHash($hash);
        if (!empty($data)) {
            $this->setData($data['key'], $data['id']);
            $this->setOrderId($data['order_id']);
            $this->setProtectCode($data['hash']);

            if ($this->getTrackId() > 0) {
                $this->getTrackingInfoByTrackId();
            } elseif ($this->getShipId() > 0) {
                $this->getTrackingInfoByShip();
            } else {
                $this->getTrackingInfoByOrder();
            }
        }
        return $this;
    }

    /**
     * Instantiate order model
     *
     * @return \Magento\Sales\Model\Order|bool
     */
    protected function _initOrder()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::_initOrder();
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_loadOrder($this->getOrderId());
        if (!$order || !$order->getId() || $this->getProtectCode() !== $order->getProtectCode()) {
            return false;
        }

        return $order;
    }

    /**
     * Load DeckCommerce order
     *
     * @param int $deckOrderId
     * @return SalesOrder|null
     */
    protected function _loadOrder($deckOrderId)
    {
        return $this->orderHistory->getOrder($deckOrderId, true);
    }

    /**
     * Load Deck Commerce shipment from Deck Commerce Order data
     *
     * @return false|mixed
     */
    protected function _loadShipment()
    {
        $order = $this->_initOrder();
        foreach ($order->getShipmentsCollection() as $shipment) {
            if ($shipment->getId() == $this->getShipId()) {
                return $shipment;
            }
        }

        return false;
    }

    /**
     * Instantiate ship model
     *
     * @return Shipment|bool
     */
    protected function _initShipment()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::_initShipment();
        }

        /* @var $ship Shipment */
        $ship = $this->_loadShipment();
        if (!$ship || !$ship->getEntityId() || $this->getProtectCode() !== $ship->getProtectCode()) {
            return false;
        }

        return $ship;
    }

    /**
     * Retrieve tracking by tracking entity id
     *
     * @return array
     */
    public function getTrackingInfoByTrackId()
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::getTrackingInfoByTrackId();
        }

        $trackId = $this->getTrackId();
        $order = $this->_initOrder();
        foreach ($order->getShipmentsCollection() as $shipment) {
            $tracks = $this->_getTracksCollection($shipment);
            foreach ($tracks as $track) {
                if ($track->getId() == $trackId && $this->getProtectCode() === $track->getProtectCode()) {
                    $this->_trackingInfo = [[$track->getNumberDetail()]];
                }
            }
        }
        return $this->_trackingInfo;
    }

    /**
     * Get tracks collection prepared from Deck Commerce API response data
     *
     * @param Shipment $shipment
     * @return \Magento\Shipping\Model\ResourceModel\Order\Track\Collection
     */
    protected function _getTracksCollection(Shipment $shipment)
    {
        if (!$this->helper->isOrderHistoryEnabled()) {
            return parent::_getTracksCollection($shipment);
        }

        $tracks = $shipment->getTracksCollection();
        if ($shipment->getId()) {
            foreach ($tracks as $track) {
                $track->setShipment($shipment);
            }
        }
        return $tracks;
    }
}
