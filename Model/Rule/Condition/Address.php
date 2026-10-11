<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

use Magento\SalesRule\Model\Rule\Condition\Address as SalesRuleAddress;

/**
 * Cart (quote address) attribute condition with "between" operators.
 */
class Address extends SalesRuleAddress
{
    use BetweenOperatorTrait;
}
