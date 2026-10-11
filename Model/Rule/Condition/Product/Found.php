<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition\Product;

use Magento\Rule\Model\Condition\Context;
use Magento\SalesRule\Model\Rule\Condition\Product\Found as SalesRuleFound;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\Product;

/**
 * "If an item is FOUND in the cart with ALL of these conditions true".
 */
class Found extends SalesRuleFound
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Product $ruleConditionProduct
     * @param array $data
     */
    public function __construct(
        Context $context,
        Product $ruleConditionProduct,
        array $data = []
    ) {
        parent::__construct($context, $ruleConditionProduct, $data);
        $this->setType(self::class);
    }

    /**
     * Only item and product attributes, built with this module's product condition.
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $itemAttributes = [];
        $productAttributes = [];
        foreach ($this->_ruleConditionProd->loadAttributeOptions()->getAttributeOption() as $code => $label) {
            $option = ['value' => Product::class . '|' . $code, 'label' => $label];
            if (str_starts_with($code, 'quote_item_') || str_starts_with($code, 'parent::quote_item_')) {
                $itemAttributes[] = $option;
            } else {
                $productAttributes[] = $option;
            }
        }

        return [
            ['value' => '', 'label' => __('Please choose a condition to add.')],
            ['label' => __('Cart Item Attribute'), 'value' => $itemAttributes],
            ['label' => __('Product Attribute'), 'value' => $productAttributes],
        ];
    }
}
