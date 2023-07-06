<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Builder;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Data\Collection;
use DeckCommerce\Integration\Model\Data\CollectionFactory;
use DeckCommerce\Integration\Model\Export\Order as DeckOrder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\Method\Substitution;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\AddressFactory;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\PaymentFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\OrderFactory;
use Magento\Shipping\Model\Order\Track;
use Magento\Shipping\Model\Order\TrackFactory;
use Magento\Rma\Model\Rma;
use DeckCommerce\Integration\Model\Factory\RmaFactory;
use Magento\Rma\Model\Item as RmaItem;
use DeckCommerce\Integration\Model\Factory\RmaItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Order Builder model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Order
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @var PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ConvertOrder
     */
    protected $convertOrder;

    /**
     * @var TrackFactory
     */
    protected $trackFactory;

    /**
     * @var RmaFactory
     */
    protected $rmaFactory;

    /**
     * @var RmaItemFactory
     */
    protected $rmaItemFactory;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var array
     */
    protected $orderFieldsMapping = [
        'increment_id'         => 'OrderNumber',
        'entity_id'            => 'OrderNumber',
        'created_at'           => 'OrderDateAsDate',
        'updated_at'           => 'OrderDateAsDate',
        'state'                => 'OrderStatus',
        'customer_id'          => 'CustomerID',
        'customer_email'       => 'EmailAddress',
        'shipping_description' => 'ShippingMethod'
    ];

    /**
     * @var array
     */
    protected $customerFieldsMapping = [
        'customer_firstname' => 'FirstName',
        'customer_lastname'  => 'LastName'
    ];

    /**
     * @var array
     */
    protected $addressFieldsMapping = [
        'firstname'  => 'FirstName',
        'lastname'   => 'LastName',
        'email'      => 'Email',
        'street'     => ['Address1', 'Address2'],
        'city'       => 'City',
        'region'     => 'Province',
        'postcode'   => 'PostalCode',
        'country_id' => 'Country',
        'telephone'  => 'Phone',
        'company'    => 'CompanyName'
    ];

    /**
     * @var array
     */
    protected $totalsFieldsMapping = [
        'subtotal'               => 'MerchandiseNetTotal',
        'base_subtotal'          => 'MerchandiseNetTotal',
        'subtotal_incl_tax'      => 'MerchandiseGrossTotal',
        'base_subtotal_incl_tax' => 'MerchandiseGrossTotal',
        'shipping_amount'        => 'ShippingNetTotal',
        'base_shipping_amount'   => 'ShippingNetTotal',
        'shipping_incl_tax'      => 'ShippingGrossTotal',
        'base_shipping_incl_tax' => 'ShippingGrossTotal',
        'grand_total'            => 'TotalGrossTotal',
        'base_grand_total'       => 'TotalGrossTotal',
        'total_paid'             => 'TotalGrossTotal',
        'base_total_paid'        => 'TotalGrossTotal'
    ];

    /**
     * @var array
     */
    protected $totalsTaxFieldsMapping = [
        'tax_amount'      => 'Tax',
        'base_tax_amount' => 'Tax'
    ];

    /**
     * Order constructor.
     * @param DeckHelper $helper
     * @param OrderFactory $orderFactory
     * @param AddressFactory $addressFactory
     * @param PaymentFactory $paymentFactory
     * @param ItemFactory $itemFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param CollectionFactory $collectionFactory
     * @param ConvertOrder $convertOrder
     * @param TrackFactory $trackFactory
     * @param JsonSerializer $jsonSerializer
     * @param RmaFactory $rmaFactory
     * @param RmaItemFactory $rmaItemFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        DeckHelper $helper,
        OrderFactory $orderFactory,
        AddressFactory $addressFactory,
        PaymentFactory $paymentFactory,
        ItemFactory $itemFactory,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        CollectionFactory $collectionFactory,
        ConvertOrder $convertOrder,
        TrackFactory $trackFactory,
        JsonSerializer $jsonSerializer,
        RmaFactory $rmaFactory,
        RmaItemFactory $rmaItemFactory
    ) {
        $this->helper            = $helper;
        $this->orderFactory      = $orderFactory;
        $this->addressFactory    = $addressFactory;
        $this->paymentFactory    = $paymentFactory;
        $this->itemFactory       = $itemFactory;
        $this->productRepository = $productRepository;
        $this->storeManager      = $storeManager;
        $this->customerSession   = $customerSession;
        $this->collectionFactory = $collectionFactory;
        $this->convertOrder      = $convertOrder;
        $this->trackFactory      = $trackFactory;
        $this->jsonSerializer    = $jsonSerializer;
        $this->rmaFactory        = $rmaFactory;
        $this->rmaItemFactory    = $rmaItemFactory;
    }

    /**
     * Set order fields with Deck data using mapping
     *
     * @param SalesOrder $order
     * @param array $data
     */
    protected function prepareOrder($order, $data)
    {
        foreach ($this->orderFieldsMapping as $key => $deckKey) {
            if (isset($data[$deckKey])) {
                $order->setData($key, $data[$deckKey]);
            }
        }
    }

    /**
     * Set order customer fields with Deck data using mapping
     *
     * @param SalesOrder $order
     * @param array $data
     */
    protected function prepareCustomerData($order, $data)
    {
        foreach ($this->customerFieldsMapping as $key => $deckKey) {
            if (isset($data['CustomerAddress'][$deckKey])) {
                $order->setData($key, $data['CustomerAddress'][$deckKey]);
            }
        }
    }

    /**
     * Set customer address fields with Deck data using mapping
     *
     * @param Address $address
     * @param array $data
     * @param string $addressField
     */
    protected function prepareAddressData($address, $data, $addressField = 'CustomerAddress')
    {
        foreach ($this->addressFieldsMapping as $key => $deckKey) {
            if (is_array($deckKey)) {
                $addressData = [];
                foreach ($deckKey as $deckKeyPart) {
                    if (isset($data[$addressField][$deckKeyPart])) {
                        array_push($addressData, $data[$addressField][$deckKeyPart]);
                    }
                }
                $address->setData($key, implode("\n", $addressData));
                continue;
            }

            if (isset($data[$addressField][$deckKey])) {
                $address->setData($key, $data[$addressField][$deckKey]);
            }
        }
    }

    /**
     * Build customer shipping and billing addresses collection for order
     *
     * @param SalesOrder $order
     * @param array $data
     * @throws \Exception
     */
    protected function prepareOrderAddresses($order, $data)
    {
        /** @var $addressCollection Collection */
        $addressCollection = $this->collectionFactory->create();

        /** @var $billingAddress Address */
        $billingAddress = $this->addressFactory->create();
        $billingAddress->setAddressType('billing');
        $this->prepareAddressData($billingAddress, $data, 'CustomerAddress');
        $addressCollection->addItem($billingAddress);

        /** @var $shippingAddress Address */
        $shippingAddress = $this->addressFactory->create();
        $shippingAddress->setAddressType('shipping');
        $this->prepareAddressData($shippingAddress, $data, 'ShipAddress');
        $addressCollection->addItem($shippingAddress);

        $order->setData('addresses', $addressCollection);
    }

    /**
     * Check whether order item is returned
     *
     * @param array $data
     * @return bool
     */
    protected function isReturnStatus($data)
    {
        return $data['ItemStatusName'] == 'Return' || $data['ItemStatusName'] == 'Pending Return';
    }

    /**
     * Check whether order item is shipped
     *
     * @param array $data
     * @return bool
     */
    protected function isShippedStatus($data)
    {
        return $data['ItemStatusName'] == 'Shipped';
    }

    /**
     * Check whether order item has return and it's cancelled
     *
     * @param array $data
     * @return bool
     */
    protected function isReturnCancelledStatus($data)
    {
        return $data['ItemStatusName'] == 'Return Cancelled';
    }

    /**
     * Check whether order item is canceled
     *
     * @param array $data
     * @return bool
     */
    protected function isCanceledStatus($data)
    {
        return $data['ItemStatusName'] == 'Cancelled';
    }

    /**
     * Build order items from Deck data
     *
     * @param SalesOrder $order
     * @param array $data
     * @param $storeId
     */
    protected function prepareOrderItems($order, $data, $storeId)
    {
        if (isset($data['Items'])) {
            $orderDiscount         = 0;
            $orderDiscountRefunded = 0;
            $orderSubtotalRefunded = 0;
            $orderTotalRefunded    = 0;

            /** @var Collection itemCollection */
            $itemsCollection = $this->collectionFactory->create();
            foreach ($data['Items'] as $deckItem) {
                $price    = $deckItem['DisplayPrice'] ?? null;
                $discount = $deckItem['DisplayDiscount'] ?? 0;
                $qty      = $deckItem['Quantity'] ?? 0;

                $orderItemData = $this->getOrderItemData($deckItem, $storeId, $price, $discount, $qty);
                if (empty($orderItemData)) {
                    continue;
                }

                /** @var $item Item */
                $item = $this->itemFactory->create();
                $item->setData($orderItemData);
                $item->setProductOptions(['info_buyRequest' => ['qty' => $item->getQtyOrdered()]]);
                $item->setId($deckItem['ID']);
                $item->setOrder($order);
                $itemsCollection->addItem($item);

                if ($this->isReturnStatus($deckItem)) {
                    $orderDiscountRefunded += $discount;
                    $orderSubtotalRefunded += $price;
                    $orderTotalRefunded    += ($price - $discount);
                }
                $orderDiscount += $discount;
            }

            $order->setDiscountAmount(-1 * $orderDiscount);
            $order->setDiscountDescription('');
            $order->setDiscountRefunded($orderDiscountRefunded);
            $order->setSubtotalRefunded($orderSubtotalRefunded);
            $order->setTotalRefunded($orderTotalRefunded);

            $order->setItems($itemsCollection->getItems());
            $order->setData('items_collection', $itemsCollection);
        }
    }

    /**
     * Set order totals from Deck data
     *
     * @param SalesOrder $order
     * @param array $data
     */
    protected function prepareOrderTotals($order, $data)
    {
        if (isset($data['Totals'])) {
            foreach ($this->totalsFieldsMapping as $key => $deckKey) {
                $order->setData($key, $data['Totals'][$deckKey] ?? 0);
            }

            if ($order->getCustomerBalanceAmount() > 0) {
                $grandTotal = $order->getGrandTotal() - $order->getCustomerBalanceAmount();
                $order->setGrandTotal($grandTotal);
                $order->setGrandTotal($grandTotal);
                $order->setBaseGrandTotal($grandTotal);
                $order->setTotalPaid($grandTotal);
                $order->setBaseTotalPaid($grandTotal);
            }
        }
    }

    /**
     * Set order tax totals from Deck data
     *
     * @param SalesOrder $order
     * @param array $data
     */
    protected function prepareOrderTaxTotals($order, $data)
    {
        if (isset($data['SimpleTotals'])) {
            foreach ($this->totalsTaxFieldsMapping as $key => $deckKey) {
                $order->setData($key, $data['SimpleTotals'][$deckKey] ?? 0);
            }
        }
    }

    /**
     * Build order payment from Deck data
     *
     * @param SalesOrder $order
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function preparePayment($order, $data)
    {
        if (!isset($data['Payments'])) {
            return;
        }

        /** @var $payment Payment */
        $payment = $this->paymentFactory->create();
        $paymentNames = [];
        foreach ($data['Payments'] as $paymentData) {
            $paymentName = $paymentData['PaymentName'];
            $paymentAmount = $paymentData['Amount'];
            if (!$paymentName && $paymentAmount > 0) {
                $order->setCustomerBalanceAmount($paymentAmount);
                $order->setBaseCustomerBalanceAmount($paymentAmount);
                if (!$payment->getMethod()) {
                    $paymentNames[] = sprintf('Store Credit or Reward Points (-$%s)', $paymentAmount);
                    continue;
                }
            }
            $paymentNames[] = $paymentName;
        }

        $paymentName = implode(', ', $paymentNames);

        $payment
            ->setMethod($paymentName)
            ->setAdditionalInformation(Substitution::INFO_KEY_TITLE, $paymentName);

        $order->setPayment($payment);
    }

    /**
     * Check if current customer is logged in
     *
     * @param array $data
     * @return bool
     */
    protected function isLoggedInCustomer($data)
    {
        return ($data && isset($data['CustomerID']) && $data['CustomerID']
            && ($this->customerSession->getCustomerId() == $data['CustomerID']
                || (!$this->customerSession->isLoggedIn() && $data['CustomerID'] == DeckOrder::GUEST_CUSTOMER_ID)));
    }

    /**
     * Build Magento order from Dech API data
     *
     * @param array $data
     * @param false $canUseForGuest
     * @param false $buildShipment
     * @return SalesOrder
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareMagentoOrder($data, $canUseForGuest = false, $buildShipment = false)
    {
        $storeId = $this->storeManager->getStore()->getId();

        /** @var $order SalesOrder */
        $order = $this->orderFactory->create();
        $order->reset();

        if ($this->isLoggedInCustomer($data) || $canUseForGuest) {
            $order->setStoreId($storeId);
            $order->setEmailSent(true);

            $this->prepareOrder($order, $data);
            $this->prepareStatus($order, $data);
            $this->prepareCustomerData($order, $data);

            $this->prepareOrderAddresses($order, $data);
            $this->preparePayment($order, $data);
            $this->prepareOrderTotals($order, $data);
            $this->prepareOrderTaxTotals($order, $data);
            $this->prepareOrderItems($order, $data, $storeId);
            if ($buildShipment) {
                $this->buildShipments($order, $data);
            }

            $this->buildRmas($order, $data);

            $order->setProtectCode(hash('sha256', $order->getCustomerEmail()));

            $order->setData('can_return', $this->isReturnable($order));
        }

        return $order;
    }

    /**
     * Is order returnable
     *
     * @param $order
     * @return bool
     */
    protected function isReturnable($order)
    {
        foreach ($order->getItems() as $item) {
            if ($item->getData('can_return')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get color/size custom attributes
     *
     * @param array $data
     * @return array
     */
    protected function getItemAttributes($data)
    {
        $itemAttributes = [];
        if (isset($data['Custom2']) && !empty($data['Custom2'])) {
            $itemAttributes['Color'] = $data['Custom2'];
        }
        if (isset($data['ProductSize']) && !empty($data['ProductSize'])) {
            $itemAttributes['Size'] = $data['ProductSize'];
        }
        return $itemAttributes;
    }

    /**
     * Check if item is returnable
     *
     * @param array $extendedData
     * @return bool
     */
    protected function isItemReturnable($extendedData)
    {
        return
            isset($extendedData['Key']) && $extendedData['Key'] == 'Returnable'
            && isset($extendedData['Value']) && $extendedData['Value'] == 'Yes';
    }

    /**
     * Get prepared order item data
     *
     * @param array $data
     * @param int $storeId
     * @param float $price
     * @param float $discount
     * @param int $qty
     * @return array
     */
    protected function getOrderItemData($data, $storeId, $price, $discount, $qty)
    {
        $sku = $data['DeckSKU'] ?: $data['StyleNumber'];
        try {
            $product = $this->productRepository->get($sku, false, $storeId);
        } catch (NoSuchEntityException $e) {
            return [];
        }

        $shippedQty   = 0;
        $availableQty = 0;

        $canReturn = false;
        $extendedData = reset($data['Extended']);
        if ($this->isShippedStatus($data) || $this->isReturnCancelledStatus($data)) {
            $shippedQty = $qty;
            if ($this->isItemReturnable($extendedData)) {
                $canReturn = true;
                $availableQty = $qty;
            }
        }

        return [
            'sku'                     => $sku,
            'name'                    => $product->getName(),
            'gtin'                    => $data['GTIN'] ?? null,
            'product_id'              => $product->getId(),
            'product_type'            => ($data['ItemType'] == 'RegularItem') ? 'simple' : $data['ItemType'],
            'item_attributes'         => $this->getItemAttributes($data),
            'shipping_method'         => $data['ItemShippingMethod'],
            'qty_ordered'             => $qty,
            'qty_shipped'             => $shippedQty,
            'qty_canceled'            => $this->isCanceledStatus($data) ? $qty : 0,
            'qty_returned'            => $this->isReturnStatus($data) ? $qty : 0,
            'available_qty'           => $availableQty,
            'can_return'              => $canReturn,
            'price'                   => $price,
            'base_price'              => $price,
            'price_incl_tax'          => $price,
            'row_total'               => $price,
            'row_total_incl_tax'      => $price,
            'base_row_total'          => $price,
            'base_row_total_incl_tax' => $price,
            'discount_amount'         => $discount,
            'base_discount_amount'    => $discount
        ];
    }

    /**
     * Map Deck -> Magento order statuses
     *
     * @param SalesOrder $order
     * @param array $data
     */
    protected function prepareStatus($order, $data)
    {
        switch ($data['OrderStatus']) {
            case 'New':
                $status = SalesOrder::STATE_PROCESSING;
                break;
            case 'Pending':
                $status = 'pending';
                break;
            case 'Review':
                $status = SalesOrder::STATE_PAYMENT_REVIEW;
                break;
            case 'Complete':
                $status = SalesOrder::STATE_COMPLETE;
                break;
            case 'Cancelled':
                $status = SalesOrder::STATE_CANCELED;
                break;
            case 'Exception':
            case 'Invalid':
                $status = SalesOrder::STATE_HOLDED;
                break;
            default:
                $status = 'payment_review';
        }
        $order->setStatus($status);
    }

    /**
     * Build order shipments collection
     *
     * @param SalesOrder $order
     * @param array $data
     * @return void
     */
    protected function buildShipments($order, $data)
    {
        $shipmentsCollection = $this->collectionFactory->create();
        $orderTracksCollection = $this->collectionFactory->create();
        if (empty($data) || !isset($data['Shipments'])) {
            return;
        }
        foreach ($data['Shipments'] as $shipmentData) {
            $shipTracksCollection = $this->collectionFactory->create();
            $shipment = $this->convertOrder->toShipment($order);
            $shipment->setId($shipmentData['OrderShipmentID']);
            $shipment->setIncrementId($shipment->getId());

            $this->buildShipmentItemsAndTracks(
                $order,
                $shipment,
                $shipmentData,
                $shipTracksCollection,
                $orderTracksCollection
            );

            $shipment->setData('deck_ship_tracks_collection', $shipTracksCollection);
            $shipmentsCollection->addItem($shipment);
        }

        $order->setData('deck_order_tracks_collection', $orderTracksCollection);
        $order->setData('deck_shipments_collection', $shipmentsCollection);
    }

    /**
     * Build shipment items and tracks collection
     *
     * @param SalesOrder $order
     * @param Shipment $shipment
     * @param array $shipmentData
     * @param Collection $shipTracksCollection
     * @param Collection $orderTracksCollection
     */
    protected function buildShipmentItemsAndTracks(
        $order,
        $shipment,
        $shipmentData,
        $shipTracksCollection,
        $orderTracksCollection
    ) {
        $trackNumbers = [];
        foreach ($shipmentData['Items'] as $shipItemData) {
            $trackNumber = $shipItemData['Tracking'];
            if ($trackNumber && !in_array($trackNumber, $trackNumbers)) {
                $shippingMethod = $this->getDeckShippingMethod($order, $shipmentData, $shipItemData);
                $track = $this
                    ->buildTrack($shipment, $trackNumber, $shippingMethod, $shipItemData['OrderShipmentItemID']);
                $shipTracksCollection->addItem($track);
                $orderTracksCollection->addItem($track);
            }
            $this->buildShipmentItemFromOrderItem($order, $shipment, $shipItemData['OrderItemID']);
        }
    }

    /**
     * Get Deck Commerce Shipping method
     * Shipping method on order or shipment level may differ from the shipping method on order item level
     * So it's used the shipping method on order item level as prioritized
     * and if it's not set then use shipping method of shipment level
     *
     * @param SalesOrder $order
     * @param array $shipmentData
     * @param array $shipItemData
     * @return mixed
     */
    protected function getDeckShippingMethod($order, $shipmentData, $shipItemData)
    {
        $shippingMethod = $shipmentData['ShippingMethod'];
        $orderItem = $order->getItemById($shipItemData['OrderItemID']);
        if ($orderItem && $orderItem->getData('shipping_method')) {
            $shippingMethod = $orderItem->getData('shipping_method');
        }

        return $shippingMethod;
    }

    /**
     * Prepare shipment item based on order item data and assign to shipment
     *
     * @param SalesOrder $order
     * @param Shipment $shipment
     * @param int $orderItemId
     */
    protected function buildShipmentItemFromOrderItem($order, $shipment, $orderItemId)
    {
        try {
            $orderItem = $order->getItemById($orderItemId);
            if ($orderItem) {
                $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty(1);
                $shipment->addItem($shipmentItem);
            }
        } catch (LocalizedException $e) {
            return;
        }
    }

    /**
     * Prepare Shipping Track model
     * First trying to find method code in the Deck shipping mapping table
     * If it's not found then use defult "custom" carrier
     * Also if tracking is not supported by carrier, also using the defult "custom" carrier
     *
     * @param Shipment $shipment
     * @param string $trackNumber
     * @param string $deckShippingMethod
     * @param int $trackId
     * @return Track|void
     */
    protected function buildTrack($shipment, $trackNumber, $deckShippingMethod, $trackId)
    {
        $methodCode = $this->helper->getMappedShippingMethod($deckShippingMethod);
        if (!$methodCode) {
            $methodCode = Track::CUSTOM_CARRIER_CODE;
        }

        $carrierCode = strtok($methodCode, '_');
        $carrier = $this->helper->getCarrierByCode($carrierCode);
        if ($carrier && !$carrier->isTrackingAvailable()) {
            $carrierCode  = Track::CUSTOM_CARRIER_CODE;
            $carrierTitle = $deckShippingMethod;
        } else {
            $carrierTitle = $this->helper->getCarrierTitleByCode($carrierCode);
        }

        /** @var Track $track */
        $track = $this->trackFactory->create();
        $track
            ->setNumber($trackNumber)
            ->setId($trackId)
            ->setTitle($carrierTitle)
            ->setCarrierCode($carrierCode)
            ->setShipment($shipment)
            ->setParentId($shipment->getId())
            ->setOrderId($shipment->getOrderId())
            ->setStoreId($shipment->getStoreId());

        return $track;
    }

    /**
     * @param SalesOrder $order
     * @param $data
     * @return void
     */
    protected function buildRmas($order, $data)
    {
        if (!$this->helper->isRmaExportEnabled()) {
            return;
        }

        /** @var Collection $rmasCollection */
        $rmasCollection = $this->collectionFactory->create();
        if (empty($data) || !isset($data['RMAS'])) {
            return;
        }
        foreach ($data['RMAS'] as $rmaData) {

            $rmaNumber = $rmaData['RmaNumber'];
            $rmaId = $order->getIncrementId() . '_rma_' . $rmaNumber;

            /** @var Rma $rma */
            $rma = $this->rmaFactory->create();
            $rma
                ->setId($rmaId)
                ->setStatus($rmaData['Status'])
                ->setIncrementId($rmaData['RmaNumber'])
                ->setDateRequested($rmaData['DateCreated'])

                ->setOrderId($order->getId())
                ->setOrderIncrementId($order->getIncrementId())
                ->setOrderDate($order->getIncrementId())
                ->setStoreId($order->getStoreId())
                ->setCustomerId($order->getCustomerId())
                ->setCustomerName($order->getCustomerName());

            $this->buildRmaItems($order, $rma, $rmaData, $rmaId);

            $rmasCollection->addItem($rma);
        }

        $order->setData('deck_rma_collection', $rmasCollection);
        $order->setData('has_rma', $rmasCollection->getSize() > 0);
    }

    /**
     * Prepare Rma item data
     *
     * @param $data
     * @return array
     */
    protected function getRmaItemData($data)
    {
        return [
            'entity_id'               => $data['RmaOrderItemID'],
            'order_item_id'           => $data['OrderItemID'],
            'qty_requested'           => 1,
            'qty_authorized'          => 1,
            'qty_returned'            => 1,
            'qty_approved'            => 1,
            'reason'                  => $data['ReturnReasonID'],
            'reason_text'             => $data['ReturnReasonText'],
            'type_id'                 => $data['ReturnTypeID'],
            'type_text'               => $data['ReturnType'],
            'is_canceled'             => $data['IsRmaCancelled'],
            'tracking_number'         => $data['TrackingNumber'],
            'carrier_code'            => $data['CarrierCode']
        ];
    }

    /**
     * Prepare Rma items collection
     *
     * @param SalesOrder $order
     * @param Rma $rma
     * @param array $rmaData
     * @param string $rmaId
     */
    protected function buildRmaItems($order, $rma, $rmaData, $rmaId) {

        $rmaItemsCollection = $this->collectionFactory->create();

        foreach ($rmaData['Orders'][0]['Items'] as $rmaItem) {
            $rmaItemData = $this->getRmaItemData($rmaItem);
            if (empty($rmaItemData)) {
                continue;
            }

            $orderItem = $order->getItemById($rmaItemData['order_item_id']);

            /** @var $item RmaItem */
            $item = $this->rmaItemFactory->create();
            $item->setData($rmaItemData);

            $item->setProductName($orderItem->getName());
            $item->setProductSku($orderItem->getSku());
            $item->setProductAdminName($orderItem->getName());
            $item->setProductAdminSku($orderItem->getSku());
            $item->setProductOptions(
                $this->jsonSerializer->serialize($orderItem->getData('product_options')));
            $item->setRmaEntityId($rmaId);
            $rmaItemsCollection->addItem($item);
        }

        $rma->setData('deck_rma_items_collection', $rmaItemsCollection);
    }
}
