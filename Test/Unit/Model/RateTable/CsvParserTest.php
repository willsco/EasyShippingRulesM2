<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model\RateTable;

use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\RateTable\CsvFormat;
use WillSco\EasyShippingRulesM2\Model\RateTable\CsvParser;

class CsvParserTest extends TestCase
{
    /**
     * @var CsvParser
     */
    private CsvParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CsvParser(new CsvFormat());
    }

    public function testParsesAnExcelFileInPortuguese(): void
    {
        $result = $this->parser->parse(implode("\r\n", [
            'Transportadora;Método;País;CEP de;CEP até;Peso até;Preço;Prazo',
            'Nossa Entrega | Correios;Expresso;br;01000;05999;5;12,90;2 a 4 dias úteis',
            '"Correios|Nossa Entrega";Expresso;;20000;28999;;"1.234,50";3',
            ';;;;;;;',
            'Nossa Entrega;Econômico;*;;;;0;',
        ]));

        $this->assertSame([], $result['errors']);
        $this->assertSame(['Correios', 'Nossa Entrega'], $result['methods']['Expresso']['carriers']);
        $this->assertSame(
            [
                'dest_country_id' => 'BR',
                'postcode_from' => '01000',
                'postcode_to' => '05999',
                'weight_from' => null,
                'weight_to' => 5.0,
                'subtotal_from' => null,
                'subtotal_to' => null,
                'price' => 12.9,
                'delivery_time' => '2-4',
            ],
            $result['methods']['Expresso']['rows'][0]
        );
        $this->assertSame(1234.5, $result['methods']['Expresso']['rows'][1]['price']);
        $this->assertSame('3', $result['methods']['Expresso']['rows'][1]['delivery_time']);
        $this->assertSame('', $result['methods']['Econômico']['rows'][0]['delivery_time']);
        $this->assertSame('', $result['methods']['Econômico']['rows'][0]['dest_country_id']);
        $this->assertSame(0.0, $result['methods']['Econômico']['rows'][0]['price']);
    }

    public function testReportsEveryInvalidLine(): void
    {
        $result = $this->parser->parse(implode("\n", [
            'carrier,method,country,weight_from,weight_to,price',
            'Our Delivery,Express,BR,,,10',
            'Our Delivery,,BR,,,10',
            'Our Delivery,Express,Brazil,,,10',
            'Our Delivery,Express,BR,5,1,10',
            'Our Delivery,Express,BR,,,',
            'Our Delivery,Express,BR,,,-1',
            'Our Delivery,Express,BR,abc,,10',
            'Other Carrier,Express,BR,,,10',
        ]));

        $errors = array_map('strval', $result['errors']);
        $this->assertCount(7, $errors, implode("\n", $errors));
        $this->assertStringStartsWith('Line 3:', $errors[0]);
        $this->assertStringContainsString('"Brazil"', $errors[1]);
        $this->assertStringContainsString('"weight_from" is greater than "weight_to"', $errors[2]);
        $this->assertStringContainsString('price is empty', $errors[3]);
        $this->assertStringContainsString('cannot be negative', $errors[4]);
        $this->assertStringContainsString('"abc"', $errors[5]);
        $this->assertStringContainsString('same carriers', $errors[6]);
    }

    public function testRejectsAnInvalidDeliveryTime(): void
    {
        $result = $this->parser->parse("carrier,method,price,prazo\nA,B,10,4-2\nA,B,10,soon");
        $errors = array_map('strval', $result['errors']);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('Line 2: "4-2" is not a delivery time', $errors[0]);
        $this->assertStringContainsString('Line 3: "soon"', $errors[1]);
    }

    public function testRequiresTheMainColumns(): void
    {
        $errors = array_map('strval', $this->parser->parse("carrier,price\nOur Delivery,10")['errors']);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('missing: method', $errors[0]);
    }

    public function testRejectsEmptyFiles(): void
    {
        $this->assertNotEmpty($this->parser->parse('')['errors']);
        $this->assertNotEmpty($this->parser->parse("carrier,method,price\n")['errors']);
    }
}
