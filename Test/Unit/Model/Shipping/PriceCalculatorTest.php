<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\Shipping;

use Magento\Quote\Model\Quote\Address\RateRequest;
use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\RuleInterface;
use WillSco\EasyShippingRulesM2\Model\Shipping\PriceCalculator;

class PriceCalculatorTest extends TestCase
{
    /**
     * @var PriceCalculator
     */
    private PriceCalculator $calculator;

    /**
     * @var RateRequest
     */
    private RateRequest $request;

    protected function setUp(): void
    {
        $this->calculator = new PriceCalculator();
        $this->request = new RateRequest(['package_qty' => 3, 'package_value' => 200.0]);
    }

    /**
     * Test calculate.
     *
     * @dataProvider calculationProvider
     * @param string $type
     * @param float|null $methodAmount
     * @param float|null $ruleAmount
     * @param float $ratePrice
     * @param float $expected
     */
    public function testCalculate(
        string $type,
        ?float $methodAmount,
        ?float $ruleAmount,
        float $ratePrice,
        float $expected
    ): void {
        $method = $this->createMock(MethodInterface::class);
        $method->method('getCalculationType')->willReturn($type);
        $method->method('getAmount')->willReturn($methodAmount);
        $rule = $this->createMock(RuleInterface::class);
        $rule->method('getAmount')->willReturn($ruleAmount);

        $this->assertSame($expected, $this->calculator->calculate($method, $rule, $this->request, $ratePrice));
    }

    /**
     * Data provider for calculation.
     *
     * @return array
     */
    public function calculationProvider(): array
    {
        return [
            'fixed' => [MethodInterface::CALCULATION_FIXED, 15.0, 99.0, 0.0, 15.0],
            'fixed falls back to rule' => [MethodInterface::CALCULATION_FIXED, null, 12.5, 0.0, 12.5],
            'zero on method means free, not "use rule"' => [MethodInterface::CALCULATION_FIXED, 0.0, 12.5, 0.0, 0.0],
            'nothing set' => [MethodInterface::CALCULATION_FIXED, null, null, 0.0, 0.0],
            'per item' => [MethodInterface::CALCULATION_FIXED_PER_ITEM, 2.0, null, 0.0, 6.0],
            'percent of subtotal' => [MethodInterface::CALCULATION_PERCENT_SUBTOTAL, 10.0, null, 0.0, 20.0],
            'percent of rate' => [MethodInterface::CALCULATION_PERCENT_RATE, 50.0, null, 30.0, 15.0],
            'never negative' => [MethodInterface::CALCULATION_FIXED, -5.0, null, 0.0, 0.0],
        ];
    }
}
