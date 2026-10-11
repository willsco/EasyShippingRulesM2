<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

/**
 * Parsing and comparison for the "between" operators.
 */
class BetweenOperator
{
    public const BETWEEN = '><';
    public const NOT_BETWEEN = '<>';

    /**
     * Whether the operator is "is between" or "is not between".
     *
     * @param mixed $operator
     * @return bool
     */
    public function isBetween($operator): bool
    {
        return $operator === self::BETWEEN || $operator === self::NOT_BETWEEN;
    }

    /**
     * Split "min-max" into its bounds.
     *
     * @param string $value
     * @return array{0: string, 1: string}|null
     */
    public function parseRange(string $value): ?array
    {
        $parts = array_map('trim', explode('-', trim($value)));

        if (count($parts) === 4) {
            // Hyphenated bounds, e.g. "01000-000-01999-999".
            $parts = [$parts[0] . '-' . $parts[1], $parts[2] . '-' . $parts[3]];
        }

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [$this->normalizeDecimal($parts[0]), $this->normalizeDecimal($parts[1])];
    }

    /**
     * Whether the value is a "min-max" range with the lower bound first.
     *
     * A single postcode such as "01000-000" reads as the range 01000 to 000 and is rejected.
     *
     * @param string $value
     * @return bool
     */
    public function isValidRange(string $value): bool
    {
        $range = $this->parseRange($value);

        return $range !== null && $this->isInRange($range[0], $range[0], $range[1]);
    }

    /**
     * Compare numerically when possible, otherwise as text ignoring hyphens and spaces.
     *
     * @param string $value
     * @param string $min
     * @param string $max
     * @return bool
     */
    public function isInRange(string $value, string $min, string $max): bool
    {
        $value = $this->normalizeDecimal(trim($value));
        if (!$this->areNumeric([$value, $min, $max])) {
            [$value, $min, $max] = array_map(
                fn (string $item): string => str_replace(['-', ' '], '', $item),
                [$value, $min, $max]
            );
        }

        if ($this->areNumeric([$value, $min, $max])) {
            return (float)$value >= (float)$min && (float)$value <= (float)$max;
        }

        return strcasecmp($value, $min) >= 0 && strcasecmp($value, $max) <= 0;
    }

    /**
     * Accept a decimal comma ("1,5") as typed by pt_BR / de_DE admins.
     *
     * @param string $value
     * @return string
     */
    private function normalizeDecimal(string $value): string
    {
        return preg_match('/^\d+,\d+$/', $value) ? str_replace(',', '.', $value) : $value;
    }

    /**
     * Whether every value is numeric.
     *
     * @param string[] $values
     * @return bool
     */
    private function areNumeric(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }
}
