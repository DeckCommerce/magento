<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Backend;

/**
 * PaymentMethodsMapping Config Backend model
 */
class PaymentMethodsMapping extends \Magento\Framework\App\Config\Value
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $jsonSerializer;

    /**
     * @var string[]
     */
    protected $availableMappingFields = [
        'PaymentProcessorSubTypeID',
        'PaymentProcessorSubTypeName',
        'Generic1',
        'Generic2',
        'Generic3',
        'Generic4',
        'Generic5',
        'AuthorizedAmount',
        'CapturedAmount',
        'CreditedAmount',
        'PaymentToken',
        'CreditCard',
        'EarlyCapture'
    ];

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate payments mapping JSON if it has proper format
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (!$value) {
            return;
        }

        try {

            $jsonData = $this->jsonSerializer->unserialize($value);

            foreach ($jsonData as $paymentMethodCode => $paymentMethodMappingData) {
                if (!is_array($paymentMethodMappingData) || is_numeric($paymentMethodCode)) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Invalid mapping.'));
                }

                foreach ($paymentMethodMappingData as $key => $value) {
                    if (is_object($value)) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __("Mapped value for {$key} can't be an object.")
                        );
                    }
                    if (is_array($value)) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __("Mapped value for {$key} can't be an array.")
                        );
                    }

                    if (!in_array($key, $this->availableMappingFields)) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __("Wrong mapping key: {$key}.")
                        );
                    }
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $field = $this->getFieldConfig();
            $label = $field && is_array($field) ? $field['label'] : 'value';
            $msg = __('Invalid %1. %2', $label, $e->getMessage());
            $error = new \Magento\Framework\Exception\LocalizedException($msg, $e);
            throw $error;
        } catch (\InvalidArgumentException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unable to read Payment Methods Mapping JSON.'));
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unable to read Payment Methods Mapping JSON.'));
        }
    }
}
