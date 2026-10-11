<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\RateTable;

use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\RateTable\CsvFormat;

class CsvFormatTest extends TestCase
{
    /**
     * @dataProvider numberProvider
     */
    public function testParseNumber(string $value, ?float $expected): void
    {
        $this->assertSame($expected, (new CsvFormat())->parseNumber($value));
    }

    public static function numberProvider(): array
    {
        return [
            'empty' => ['', null],
            'integer' => ['12', 12.0],
            'decimal point' => ['12.90', 12.9],
            'decimal comma' => ['12,90', 12.9],
            'thousands dot, decimal comma' => ['1.234,56', 1234.56],
            'thousands comma, decimal point' => ['1,234.56', 1234.56],
            'spaces' => [' 1 234,5 ', 1234.5],
            'negative' => ['-3', -3.0],
        ];
    }

    public function testParseNumberRejectsText(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new CsvFormat())->parseNumber('R$ 10');
    }

    public function testFormatNumber(): void
    {
        $format = new CsvFormat();

        $this->assertSame('12,9', $format->formatNumber('12.9000', ','));
        $this->assertSame('100', $format->formatNumber('100.0000', '.'));
        $this->assertSame('0', $format->formatNumber('0.0000', ','));
        $this->assertSame('', $format->formatNumber(null, ','));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testResolveColumn(string $header, ?string $expected): void
    {
        $this->assertSame($expected, (new CsvFormat())->resolveColumn($header));
    }

    public static function headerProvider(): array
    {
        return [
            'key' => ['price', 'price'],
            'upper case' => ['POSTCODE_FROM', 'postcode_from'],
            'portuguese with accent' => ['Preço', 'price'],
            'portuguese with space' => ['CEP até', 'postcode_to'],
            'method accent' => ['Método', 'method'],
            'unknown' => ['notes', null],
        ];
    }

    public function testDetectDelimiter(): void
    {
        $format = new CsvFormat();

        $this->assertSame(';', $format->detectDelimiter('carrier;method;price'));
        $this->assertSame(',', $format->detectDelimiter('carrier,method,price'));
        $this->assertSame("\t", $format->detectDelimiter("carrier\tmethod\tprice"));
    }

    public function testToUtf8(): void
    {
        $format = new CsvFormat();

        $this->assertSame('Econômico', $format->toUtf8("\xEF\xBB\xBFEconômico"), 'BOM removed');
        $this->assertSame('Econômico', $format->toUtf8("Econ\xF4mico"), 'Windows-1252 converted');
    }
}
