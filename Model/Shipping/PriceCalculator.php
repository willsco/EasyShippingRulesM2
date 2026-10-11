<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Shipping;

use Magento\Quote\Model\Quote\Address\RateRequest;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\RuleInterface;

/**
 * Turns a method amount (or the matched rule amount) into a price.
 */
class PriceCalculator
{
    /**
     * Price of a rate for the given method and matched rule.
     *
     * @param MethodInterface $method
     * @param RuleInterface $rule rule that made the method apply
     * @param RateRequest $request
     * @param float $ratePrice original rate price, for "percentage of rate"
     * @return float
     */
    public function calculate(
        MethodInterface $method,
        RuleInterface $rule,
        RateRequest $request,
        float $ratePrice = 0.0
    ): float {
        $amount = $method->getAmount() ?? $rule->getAmount() ?? 0.0;

        $price = match ($method->getCalculationType()) {
            MethodInterface::CALCULATION_FIXED_PER_ITEM => $amount * (float)$request->getPackageQty(),
            MethodInterface::CALCULATION_PERCENT_SUBTOTAL => (float)$request->getPackageValue() * $amount / 100,
            MethodInterface::CALCULATION_PERCENT_RATE => $ratePrice * $amount / 100,
            default => $amount,
        };

        return round(max($price, 0.0), 4);
    }
}
