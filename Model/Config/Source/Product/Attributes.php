<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Config\Source\Product;

use Magento\Eav\Model\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

/**
 * Attributes Source
 */
class Attributes implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * @var AttributeCollectionFactory
     */
    protected $attributes;

    /**
     * Exclude incompatible product attributes from the mapping.
     *
     * @var array
     */
    protected $excludedAttributeCodes = [
        'meta_title',
        'tier_price',
        'category_ids',
        'required_options',
        'has_options',
        'image_label',
        'small_image_label',
        'thumbnail_label',
        'url_key',
        'url_path'
    ];

    protected $allowedInputTypes = [
        'text'
    ];

    /**
     * Attributes constructor.
     *
     * @param AttributeCollectionFactory $collectionFactory
     */
    public function __construct(
        AttributeCollectionFactory $collectionFactory
    ) {
        $this->attributes = $collectionFactory;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->attributes->create()->addVisibleFilter();

        $attributeArray[] = [
            'label' => __('---- Not used ----'),
            'value' => '',
        ];

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            if ($this->isAllowed($attribute)) {
                $attributeArray[] = [
                    'label' => $attribute->getFrontendLabel(),
                    'value' => $attribute->getAttributeCode(),
                ];
            }
        }
        return $attributeArray;
    }

    /**
     * Check whether attribute is allowed to be shown in options list
     *
     * @param Attribute $attribute
     * @return bool
     */
    protected function isAllowed($attribute)
    {
        return in_array($attribute->getFrontendInput(), $this->allowedInputTypes)
            && !in_array($attribute->getAttributeCode(), $this->excludedAttributeCodes);
    }
}
