<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Plugin\Model\SalesRule;

use DeckCommerce\Integration\Helper\Data as HelperData;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\RulesApplier;

/**
 * SalesRule Rules Applier Plugin
 */
class RulesApplierPlugin
{

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * OrderPlugin constructor.
     *
     * @param HelperData $helper
     */
    public function __construct(HelperData $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Set extended item discount information
     *
     * @param AbstractItem $item
     * @param Collection $rules
     * @return bool
     */
    protected function setItemDiscounts($item, $rules)
    {
        $discounts = $item->getExtensionAttributes() ? $item->getExtensionAttributes()->getDiscounts() : [];
        if (empty($discounts)) {
            return false;
        }

        $rulesData = [];
        /** @var \Magento\SalesRule\Model\Data\RuleDiscount $discount */
        foreach ($discounts as $discount) {
            $discountData = $discount->getDiscountData();
            $rulesData[$discount->getRuleId()] = [
                'label'       => $discount->getRuleLabel(),
                'base_amount' => $discountData->getBaseAmount()
            ];
        }

        /** @var \Magento\SalesRule\Model\Rule $rule */
        foreach ($rules as $rule) {
            if ($rule->getCouponCode() && isset($rulesData[$rule->getId()])) {
                $rulesData[$rule->getId()]['coupon_code'] = $rule->getCouponCode();
            }
        }

        $additionalData = $this->helper->jsonDecode($item->getAdditionalData()) ?: [];
        $additionalData['discounts'] = $rulesData;
        $item->setAdditionalData($this->helper->jsonEncode($additionalData));

        return true;
    }

    /**
     * Save applied rules data details
     *
     * @param RulesApplier $subject
     * @param array $result
     * @param AbstractItem $item
     * @param Collection $rules
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterApplyRules(RulesApplier $subject, $result, $item, $rules)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        if ($item->getHasChildren() && $item->getChildren() && $item->isChildrenCalculated()) {
            foreach ($item->getChildren() as $childItem) {
                if ($this->setItemDiscounts($childItem, $rules)) {
                    try {
                        $childItem->save();
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
            return $result;
        }

        $this->setItemDiscounts($item, $rules);

        return $result;
    }
}
