<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\Tester;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\Tester\InputParser;

class InputParserTest extends TestCase
{
    /**
     * @var InputParser
     */
    private InputParser $parser;

    protected function setUp(): void
    {
        $defaultStore = $this->createMock(StoreInterface::class);
        $defaultStore->method('getId')->willReturn(1);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getDefaultStoreView')->willReturn($defaultStore);

        $this->parser = new InputParser($storeManager);
    }

    public function testParseItems(): void
    {
        $this->assertSame(
            ['ABC' => 3.0, 'XYZ' => 1.0, 'Q' => 1.0],
            $this->parser->parseItems("ABC, 2\r\n\nXYZ\n ABC ,1 \nQ, -5")
        );
    }

    public function testParseUsesDefaultStoreAndNormalizesCountry(): void
    {
        $input = $this->parser->parse([
            'country_id' => ' br ',
            'postcode' => ' 01310-100 ',
            'customer_group_id' => '0',
            'items' => 'ABC, 1',
        ]);

        $this->assertSame(1, $input->storeId);
        $this->assertSame('BR', $input->countryId);
        $this->assertSame('01310-100', $input->postcode);
        $this->assertSame(0, $input->customerGroupId);
        $this->assertSame('', $input->customerEmail);
        $this->assertNull($input->activeRuleIds);
    }

    public function testParseActiveRuleIds(): void
    {
        $input = $this->parser->parse([
            'country_id' => 'BR',
            'items' => 'ABC',
            'rule_ids' => ['', '7', '3', '7', 'x'],
        ]);
        $this->assertSame([7, 3], $input->activeRuleIds);

        $none = $this->parser->parse(['country_id' => 'BR', 'items' => 'ABC', 'rule_ids' => ['']]);
        $this->assertSame([], $none->activeRuleIds);
    }

    public function testCountryIsRequired(): void
    {
        $this->expectException(LocalizedException::class);
        $this->parser->parse(['items' => 'ABC']);
    }
}
