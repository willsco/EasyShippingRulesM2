<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Combine as RuleCombine;
use Magento\Rule\Model\Condition\Context;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\Product\Found;
use WillSco\EasyShippingRulesM2\Model\Rule\ProgrammaticConditionPool;

/**
 * Root condition of a shipping rule and the list of conditions it can hold.
 */
class Combine extends RuleCombine
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Address $addressCondition
     * @param Customer $customerCondition
     * @param ProgrammaticConditionPool $programmaticConditionPool
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Address $addressCondition,
        private readonly Customer $customerCondition,
        private readonly ProgrammaticConditionPool $programmaticConditionPool,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setType(self::class);
    }

    /**
     * @inheritdoc
     */
    public function getNewChildSelectOptions()
    {
        $options = [
            ['value' => self::class, 'label' => __('Conditions Combination')],
            ['value' => Found::class, 'label' => __('Product Attribute Combination')],
            ['value' => ShippingRule::class, 'label' => __('Shipping Rule')],
            ['value' => CustomerGroup::class, 'label' => __('Customer Group')],
        ];

        if (!$this->programmaticConditionPool->isEmpty()) {
            $options[] = ['value' => Programmatic::class, 'label' => __('Programmatic Condition')];
        }

        $options[] = [
            'label' => __('Cart Attribute'),
            'value' => $this->buildAttributeOptions($this->addressCondition, Address::class),
        ];
        $options[] = [
            'label' => __('Customer Attribute'),
            'value' => $this->buildAttributeOptions($this->customerCondition, Customer::class),
        ];

        return array_merge_recursive(parent::getNewChildSelectOptions(), $options);
    }

    /**
     * Build attribute options.
     *
     * @param AbstractCondition $condition
     * @param string $type
     * @return array
     */
    private function buildAttributeOptions(AbstractCondition $condition, string $type): array
    {
        $options = [];
        foreach ($condition->loadAttributeOptions()->getAttributeOption() as $code => $label) {
            $options[] = ['value' => $type . '|' . $code, 'label' => $label];
        }

        return $options;
    }
}
