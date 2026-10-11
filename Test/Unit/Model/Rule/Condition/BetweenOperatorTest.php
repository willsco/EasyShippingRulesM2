<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\Rule\Condition;

use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\BetweenOperator;

class BetweenOperatorTest extends TestCase
{
    /**
     * @var BetweenOperator
     */
    private BetweenOperator $operator;

    protected function setUp(): void
    {
        $this->operator = new BetweenOperator();
    }

    /**
     * Test parse range.
     *
     * @dataProvider rangeProvider
     * @param string $value
     * @param array|null $expected
     */
    public function testParseRange(string $value, ?array $expected): void
    {
        $this->assertSame($expected, $this->operator->parseRange($value));
    }

    /**
     * Data provider for range.
     *
     * @return array
     */
    public function rangeProvider(): array
    {
        return [
            'numbers' => ['10-50', ['10', '50']],
            'spaces' => [' 10 - 50 ', ['10', '50']],
            'decimal comma' => ['1,5-3,25', ['1.5', '3.25']],
            'decimal dot' => ['1.5-3.25', ['1.5', '3.25']],
            'hyphenated postcodes' => ['01000-000-01999-999', ['01000-000', '01999-999']],
            'single value' => ['10', null],
            'missing bound' => ['10-', null],
            'three parts' => ['1-2-3', null],
        ];
    }

    /**
     * Test is in range.
     *
     * @dataProvider inRangeProvider
     * @param string $value
     * @param string $min
     * @param string $max
     * @param bool $expected
     */
    public function testIsInRange(string $value, string $min, string $max, bool $expected): void
    {
        $this->assertSame($expected, $this->operator->isInRange($value, $min, $max));
    }

    /**
     * Data provider for in range.
     *
     * @return array
     */
    public function inRangeProvider(): array
    {
        return [
            'inside' => ['20', '10', '50', true],
            'lower bound' => ['10', '10', '50', true],
            'upper bound' => ['50', '10', '50', true],
            'below' => ['9.99', '10', '50', false],
            'numeric, not lexical' => ['100', '20', '30', false],
            'decimal comma value' => ['10,5', '10', '11', true],
            'postcode with hyphen in plain range' => ['01310-100', '01000000', '01999999', true],
            'plain postcode in hyphenated range' => ['01310100', '01000-000', '01999-999', true],
            'postcode outside' => ['02000-000', '01000-000', '01999-999', false],
            'text' => ['m', 'a', 'z', true],
            'text case insensitive' => ['M', 'a', 'z', true],
            'text outside' => ['zz', 'a', 'z', false],
        ];
    }

    /**
     * Test is valid range.
     *
     * @dataProvider validRangeProvider
     * @param string $value
     * @param bool $expected
     */
    public function testIsValidRange(string $value, bool $expected): void
    {
        $this->assertSame($expected, $this->operator->isValidRange($value));
    }

    /**
     * Data provider for valid range.
     *
     * @return array
     */
    public function validRangeProvider(): array
    {
        return [
            'numbers' => ['10-50', true],
            'same bounds' => ['10-10', true],
            'hyphenated postcodes' => ['01000-000-05999-999', true],
            'plain postcodes' => ['01000000-05999999', true],
            'decimal comma' => ['1,5-3,25', true],
            'text' => ['a-z', true],
            'reversed' => ['50-10', false],
            'reversed postcodes' => ['05999-999-01000-000', false],
            'single postcode' => ['01000-000', false],
            'single value' => ['10', false],
            'missing bound' => ['01000-000-', false],
            'empty' => ['', false],
            'numeric, not lexical' => ['9-10', true],
        ];
    }

    public function testIsBetween(): void
    {
        $this->assertTrue($this->operator->isBetween('><'));
        $this->assertTrue($this->operator->isBetween('<>'));
        $this->assertFalse($this->operator->isBetween('<=>'));
        $this->assertFalse($this->operator->isBetween(null));
    }
}
