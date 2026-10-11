<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\Shipping;

use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\Shipping\CarrierCode;

class CarrierCodeTest extends TestCase
{
    public function testBuildAndParseRoundTrip(): void
    {
        $carrierCode = new CarrierCode();

        $code = $carrierCode->build(42);

        $this->assertSame('easyshippingrules42', $code);
        $this->assertStringNotContainsString('_', $code, 'Magento splits "carrier_method" on the first underscore');
        $this->assertSame(42, $carrierCode->parse($code));
    }

    /**
     * Test parse ignores other carriers.
     *
     * @dataProvider foreignCodeProvider
     * @param string|null $code
     */
    public function testParseIgnoresOtherCarriers(?string $code): void
    {
        $this->assertNull((new CarrierCode())->parse($code));
    }

    /**
     * Data provider for foreign code.
     *
     * @return array
     */
    public function foreignCodeProvider(): array
    {
        return [[null], [''], ['flatrate'], ['easyshippingrules'], ['easyshippingrules_3'], ['xeasyshippingrules3']];
    }
}
