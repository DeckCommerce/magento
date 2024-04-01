<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2024 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Source;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Config;

/**
* Class AllShippingMethods
 */
class AllShippingMethods extends \Magento\Shipping\Model\Config\Source\Allmethods
{

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var null | array
     */
    protected $customMethods = null;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $shippingConfig
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $shippingConfig,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        parent::__construct($scopeConfig, $shippingConfig);
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Extend default toOptionArray to add possible dynamically created shipping methods
     * that don't exist in the default Magento shipping methods list
     *
     * @param $isActiveOnlyFlag
     * @return array|array[]
     */
    public function toOptionArray($isActiveOnlyFlag = false)
    {
        $methods = parent::toOptionArray($isActiveOnlyFlag);
        $existingMethods = [];

        foreach ($methods as $carrier) {
            if (isset($carrier['value']) && is_array($carrier['value'])) {
                foreach ($carrier['value'] as $method) {
                    if (is_array($method) && isset($method['value'])) {
                        $existingMethods[$method['value']] = true;
                    }
                }
            }
        }

        if (is_null($this->customMethods)) {
            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection
                ->distinct(true)
                ->addFieldToSelect(['shipping_method', 'shipping_description'])
                ->addFieldToFilter('shipping_method', ['neq' => ''])
                ->addFieldToFilter('shipping_method', ['notnull' => true]);

            $this->customMethods = [];
            foreach ($orderCollection as $order) {
                $shippingMethod = $order->getShippingMethod();
                $shippingDescription = $order->getShippingDescription();

                if (!isset($existingMethods[$shippingMethod])) {
                    $this->customMethods[] = [
                        'value' => $shippingMethod,
                        'label' => '[' . $shippingMethod . '] ' . $shippingDescription,
                    ];
                }
            }
        }

        if (!empty($this->customMethods)) {
            $methods[] = ['label' => __('Used Custom Shipping Methods'), 'value' => $this->customMethods];
        }

        return $methods;
    }
}
