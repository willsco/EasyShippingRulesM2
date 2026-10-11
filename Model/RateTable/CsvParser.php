<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\RateTable;

use Magento\Framework\Phrase;

/**
 * Reads a rate table spreadsheet into rows grouped by method, reporting every invalid line.
 */
class CsvParser
{
    /**
     * Initialize dependencies.
     *
     * @param CsvFormat $format
     */
    public function __construct(
        private readonly CsvFormat $format
    ) {
    }

    /**
     * Parse.
     *
     * @param string $contents file contents, in any delimiter and number format of CsvFormat
     * @return array{methods: array<string, array{carriers: string[], rows: array[]}>, errors: Phrase[]}
     */
    public function parse(string $contents): array
    {
        $contents = $this->format->toUtf8($contents);
        $firstLine = strtok($contents, "\r\n");
        if ($firstLine === false || trim($firstLine) === '') {
            return ['methods' => [], 'errors' => [__('The file is empty.')]];
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);
        $delimiter = $this->format->detectDelimiter($firstLine);

        try {
            $columns = $this->readHeader((array)fgetcsv($handle, null, $delimiter, '"', ''));
            if ($columns instanceof Phrase) {
                return ['methods' => [], 'errors' => [$columns]];
            }

            $methods = [];
            $errors = [];
            $line = 1;
            while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $line++;
                if ($cells === [null] || !array_filter($cells, static fn ($cell) => trim((string)$cell) !== '')) {
                    continue;
                }

                $values = [];
                foreach ($columns as $index => $key) {
                    $values[$key] = trim((string)($cells[$index] ?? ''));
                }

                $row = $this->readRow($values, $line, $errors);
                if ($row === null) {
                    continue;
                }

                $method = $row['method'];
                if (!isset($methods[$method])) {
                    $methods[$method] = ['carriers' => $row['carriers'], 'rows' => []];
                } elseif ($methods[$method]['carriers'] !== $row['carriers']) {
                    $errors[] = __(
                        'Line %1: method "%2" is listed with other carriers on a previous line; '
                        . 'every line of a method must have the same carriers.',
                        $line,
                        $method
                    );
                    continue;
                }
                $methods[$method]['rows'][] = $row['data'];
            }
        } finally {
            fclose($handle);
        }

        if (!$methods && !$errors) {
            $errors[] = __('The file has no rate lines.');
        }

        return ['methods' => $methods, 'errors' => $errors];
    }

    /**
     * Column key by cell index, or the error when a required column is missing.
     *
     * @param array $header
     * @return array<int, string>|Phrase
     */
    private function readHeader(array $header)
    {
        $columns = [];
        foreach ($header as $index => $name) {
            $key = $this->format->resolveColumn((string)$name);
            if ($key !== null) {
                $columns[$index] = $key;
            }
        }

        $missing = array_diff(['carrier', 'method', 'price'], $columns);
        if ($missing) {
            return __(
                'The first line must name the columns; missing: %1. Export a file to see the expected layout.',
                implode(', ', $missing)
            );
        }

        return $columns;
    }

    /**
     * Validated row, or null after adding its errors.
     *
     * @param array $values cell values by column key
     * @param int $line
     * @param Phrase[] $errors
     * @return array{method: string, carriers: string[], data: array}|null
     */
    private function readRow(array $values, int $line, array &$errors): ?array
    {
        $count = count($errors);

        $method = $values['method'] ?? '';
        if ($method === '') {
            $errors[] = __('Line %1: the method name is empty.', $line);
        }
        $carriers = array_values(array_unique(array_filter(array_map(
            'trim',
            explode(CsvFormat::CARRIER_SEPARATOR, $values['carrier'] ?? '')
        ), 'strlen')));
        sort($carriers);
        if (!$carriers) {
            $errors[] = __('Line %1: the carrier is empty.', $line);
        }

        $country = strtoupper($values['country'] ?? '');
        $country = $country === '*' ? '' : $country;
        if ($country !== '' && !preg_match('/^[A-Z]{2}$/', $country)) {
            $errors[] = __('Line %1: "%2" is not a two-letter country code such as BR.', $line, $values['country']);
        }

        $data = [
            'dest_country_id' => $country,
            'postcode_from' => $values['postcode_from'] ?? '',
            'postcode_to' => $values['postcode_to'] ?? '',
        ];
        foreach (['postcode_from', 'postcode_to'] as $key) {
            if (strlen($data[$key]) > 20) {
                $errors[] = __('Line %1: the postcode "%2" is too long.', $line, $data[$key]);
            }
        }

        foreach (['weight', 'subtotal'] as $range) {
            $from = $this->readNumber($values, $range . '_from', $line, $errors);
            $to = $this->readNumber($values, $range . '_to', $line, $errors);
            if ($from !== null && $to !== null && $from > $to) {
                $errors[] = __('Line %1: "%2" is greater than "%3".', $line, $range . '_from', $range . '_to');
            }
            $data[$range . '_from'] = $from;
            $data[$range . '_to'] = $to;
        }

        $countBeforePrice = count($errors);
        $data['price'] = $this->readNumber($values, 'price', $line, $errors);
        if ($data['price'] === null && count($errors) === $countBeforePrice) {
            $errors[] = __('Line %1: the price is empty. Use 0 for free shipping.', $line);
        } elseif ($data['price'] !== null && $data['price'] < 0) {
            $errors[] = __('Line %1: the price cannot be negative.', $line);
        }

        try {
            $data['delivery_time'] = $this->format->parseDeliveryTime($values['delivery_time'] ?? '');
        } catch (\InvalidArgumentException $e) {
            $errors[] = __(
                'Line %1: "%2" is not a delivery time. Use business days such as 3 or 2-4.',
                $line,
                $values['delivery_time']
            );
        }

        return count($errors) === $count ? ['method' => $method, 'carriers' => $carriers, 'data' => $data] : null;
    }

    /**
     * Number of a column, or null when empty or invalid (adding the error).
     *
     * @param array $values cell values by column key
     * @param string $key
     * @param int $line
     * @param Phrase[] $errors
     * @return float|null
     */
    private function readNumber(array $values, string $key, int $line, array &$errors): ?float
    {
        try {
            return $this->format->parseNumber($values[$key] ?? '');
        } catch (\InvalidArgumentException $e) {
            $errors[] = __('Line %1: "%2" in column "%3" is not a number.', $line, $values[$key], $key);
            return null;
        }
    }
}
