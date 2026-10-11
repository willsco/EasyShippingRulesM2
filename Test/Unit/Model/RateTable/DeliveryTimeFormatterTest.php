<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\RateTable;

use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\RateTable\DeliveryTimeFormatter;

class DeliveryTimeFormatterTest extends TestCase
{
    /**
     * @dataProvider titleProvider
     */
    public function testFormat(string $deliveryTime, string $expected): void
    {
        $this->assertSame($expected, (new DeliveryTimeFormatter())->format('Express', $deliveryTime));
    }

    public static function titleProvider(): array
    {
        return [
            'none' => ['', 'Express'],
            'one day' => ['1', 'Express (1 business day)'],
            'days' => ['3', 'Express (3 business days)'],
            'range' => ['2-4', 'Express (2 to 4 business days)'],
        ];
    }
}
