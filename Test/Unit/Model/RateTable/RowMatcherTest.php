<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\RateTable;

use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\RateTable\RowMatcher;

class RowMatcherTest extends TestCase
{
    /**
     * @dataProvider rowProvider
     */
    public function testMatches(array $row, string $postcode, float $weight, float $subtotal, bool $expected): void
    {
        $this->assertSame($expected, (new RowMatcher())->matches($row, $postcode, $weight, $subtotal));
    }

    public static function rowProvider(): array
    {
        $saoPaulo = ['postcode_from' => '01000-000', 'postcode_to' => '05999-999'];
        $prefix = ['postcode_from' => '01000', 'postcode_to' => '05999'];

        return [
            'no bounds' => [[], '', 0.0, 0.0, true],
            'postcode inside' => [$saoPaulo, '01310-100', 1.0, 10.0, true],
            'postcode without hyphen' => [$saoPaulo, '01310100', 1.0, 10.0, true],
            'postcode on the upper bound' => [$saoPaulo, '05999-999', 1.0, 10.0, true],
            'postcode outside' => [$saoPaulo, '20040-020', 1.0, 10.0, false],
            'empty postcode with bounds' => [$saoPaulo, '', 1.0, 10.0, false],
            'prefix lower end' => [$prefix, '01000-000', 1.0, 10.0, true],
            'prefix upper end' => [$prefix, '05999-999', 1.0, 10.0, true],
            'prefix outside' => [$prefix, '06000-000', 1.0, 10.0, false],
            'only lower postcode bound' => [['postcode_from' => '20000000'], '99999-999', 1.0, 10.0, true],
            'weight inclusive bounds' => [['weight_from' => '1', 'weight_to' => '5'], '', 5.0, 0.0, true],
            'weight above' => [['weight_from' => '1', 'weight_to' => '5'], '', 5.01, 0.0, false],
            'weight empty bound' => [['weight_from' => '', 'weight_to' => null], '', 99.0, 0.0, true],
            'subtotal below' => [['subtotal_from' => '200'], '', 0.0, 199.99, false],
            'subtotal from' => [['subtotal_from' => '200'], '', 0.0, 200.0, true],
            'letters' => [['postcode_from' => 'SW1A 0AA', 'postcode_to' => 'SW1A 9ZZ'], 'sw1a 1aa', 0.0, 0.0, true],
        ];
    }
}
