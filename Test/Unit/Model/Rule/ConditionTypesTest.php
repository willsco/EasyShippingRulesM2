<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\Rule;

use Magento\Rule\Model\Condition\AbstractCondition;
use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\Address;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\BetweenOperator;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\Combine;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\Product\Found;
use WillSco\EasyShippingRulesM2\Model\Rule\ConditionTypes;

class ConditionTypesTest extends TestCase
{
    /**
     * @dataProvider typeProvider
     */
    public function testIsAllowed(string $type, bool $expected): void
    {
        $this->assertSame($expected, (new ConditionTypes())->isAllowed($type));
    }

    public static function typeProvider(): array
    {
        return [
            'module combine' => [Combine::class, true],
            'module address' => [Address::class, true],
            'leading backslash' => ['\\' . Found::class, true],
            'module class that is not a condition' => [BetweenOperator::class, false],
            'core condition' => ['Magento\\SalesRule\\Model\\Rule\\Condition\\Address', false],
            'abstract core condition' => [AbstractCondition::class, false],
            'missing module class' => ['WillSco\\EasyShippingRulesM2\\Model\\Rule\\Condition\\Missing', false],
            'empty' => ['', false],
        ];
    }
}
