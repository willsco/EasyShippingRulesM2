<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\RateTable;

/**
 * Columns, delimiters and number format of the rate table spreadsheet.
 */
class CsvFormat
{
    /**
     * Comma separated, decimal point (Google Sheets, Excel in English).
     */
    public const FORMAT_STANDARD = 'standard';

    /**
     * Semicolon separated, decimal comma (Excel in Portuguese and most European languages).
     */
    public const FORMAT_EXCEL_COMMA = 'excel_comma';

    /**
     * Several carriers of one method are separated by this character in the carrier column.
     */
    public const CARRIER_SEPARATOR = '|';

    /**
     * Column keys, in export order, with the header names also accepted on import.
     */
    public const COLUMNS = [
        'carrier' => ['transportadora'],
        'method' => ['metodo'],
        'country' => ['pais', 'dest_country_id'],
        'postcode_from' => ['cep_de', 'cep_inicial'],
        'postcode_to' => ['cep_ate', 'cep_final'],
        'weight_from' => ['peso_de', 'peso_inicial'],
        'weight_to' => ['peso_ate', 'peso_final'],
        'subtotal_from' => ['subtotal_de', 'valor_de'],
        'subtotal_to' => ['subtotal_ate', 'valor_ate'],
        'price' => ['preco', 'valor_frete'],
    ];

    /**
     * Delimiter and decimal separator of a format.
     *
     * @param string $format
     * @return array{0: string, 1: string}
     */
    public function getSeparators(string $format): array
    {
        return $format === self::FORMAT_EXCEL_COMMA ? [';', ','] : [',', '.'];
    }

    /**
     * Delimiter of a file, from its header line.
     *
     * @param string $headerLine
     * @return string
     */
    public function detectDelimiter(string $headerLine): string
    {
        $counts = [];
        foreach ([',', ';', "\t"] as $delimiter) {
            $counts[$delimiter] = substr_count($headerLine, $delimiter);
        }
        arsort($counts);

        return (string)array_key_first($counts);
    }

    /**
     * Column key of a header cell ("Preço", "CEP até" and "price" all work), or null if unknown.
     *
     * @param string $header
     * @return string|null
     */
    public function resolveColumn(string $header): ?string
    {
        $name = strtolower(trim($header));
        $name = strtr($name, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
        ]);
        $name = trim((string)preg_replace('/[^a-z0-9]+/', '_', $name), '_');

        foreach (self::COLUMNS as $key => $aliases) {
            if ($name === $key || in_array($name, $aliases, true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Number typed in a spreadsheet: "12.90", "12,90", "1.234,56" or "1,234.56"; null when empty.
     *
     * @param string $value
     * @return float|null
     * @throws \InvalidArgumentException when it is not a number
     */
    public function parseNumber(string $value): ?float
    {
        $value = str_replace([' ', "\u{00A0}"], '', trim($value));
        if ($value === '') {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');
        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            // Decimal comma: dots are thousands separators.
            $value = str_replace(['.', ','], ['', '.'], $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        if (!is_numeric($value)) {
            throw new \InvalidArgumentException($value);
        }

        return (float)$value;
    }

    /**
     * Number without trailing zeros, with the decimal separator of the format; empty for null.
     *
     * @param mixed $value
     * @param string $decimalSeparator
     * @return string
     */
    public function formatNumber($value, string $decimalSeparator): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatted = rtrim(rtrim(number_format((float)$value, 4, '.', ''), '0'), '.');

        return str_replace('.', $decimalSeparator, $formatted);
    }

    /**
     * File contents as UTF-8 without BOM; files saved by Excel as "CSV" are Windows-1252.
     *
     * @param string $contents
     * @return string
     */
    public function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        return mb_check_encoding($contents, 'UTF-8')
            ? $contents
            : (string)mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
    }
}
