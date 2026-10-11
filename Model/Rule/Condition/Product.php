<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

use Magento\SalesRule\Model\Rule\Condition\Product as SalesRuleProduct;

/**
 * Product / cart item attribute condition with "between" operators.
 */
class Product extends SalesRuleProduct
{
    use BetweenOperatorTrait;
}
