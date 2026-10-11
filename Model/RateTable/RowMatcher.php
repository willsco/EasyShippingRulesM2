<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\RateTable;

/**
 * Whether a rate table row applies to a destination, weight and subtotal.
 *
 * Every bound is inclusive and an empty bound means "no limit". Postcodes are compared without
 * spaces and hyphens; a shorter numeric bound is a prefix ("01000" to "05999" covers
 * 01000-000 to 05999-999).
 */
class RowMatcher
{
    /**
     * Whether the row matches.
     *
     * @param array $row rate table row (country is filtered beforehand)
     * @param string $postcode
     * @param float $weight
     * @param float $subtotal
     * @return bool
     */
    public function matches(array $row, string $postcode, float $weight, float $subtotal): bool
    {
        return $this->inRange($weight, $row['weight_from'] ?? null, $row['weight_to'] ?? null)
            && $this->inRange($subtotal, $row['subtotal_from'] ?? null, $row['subtotal_to'] ?? null)
            && $this->postcodeInRange(
                $postcode,
                (string)($row['postcode_from'] ?? ''),
                (string)($row['postcode_to'] ?? '')
            );
    }

    /**
     * Postcode without spaces and hyphens, upper case.
     *
     * @param string $postcode
     * @return string
     */
    public function normalizePostcode(string $postcode): string
    {
        return strtoupper((string)preg_replace('/[\s\-]+/', '', $postcode));
    }

    /**
     * Whether a number is within inclusive bounds.
     *
     * @param float $value
     * @param mixed $from
     * @param mixed $to
     * @return bool
     */
    private function inRange(float $value, $from, $to): bool
    {
        return ($from === null || $from === '' || $value >= (float)$from)
            && ($to === null || $to === '' || $value <= (float)$to);
    }

    /**
     * Whether a postcode is within inclusive bounds.
     *
     * @param string $postcode
     * @param string $from
     * @param string $to
     * @return bool
     */
    private function postcodeInRange(string $postcode, string $from, string $to): bool
    {
        $from = $this->normalizePostcode($from);
        $to = $this->normalizePostcode($to);
        if ($from === '' && $to === '') {
            return true;
        }

        $postcode = $this->normalizePostcode($postcode);
        if ($postcode === '') {
            return false;
        }

        return ($from === '' || strcmp($postcode, $this->extend($from, strlen($postcode), '0')) >= 0)
            && ($to === '' || strcmp($postcode, $this->extend($to, strlen($postcode), '9')) <= 0);
    }

    /**
     * Extend a shorter numeric bound to the postcode length, so it works as a prefix.
     *
     * @param string $bound
     * @param int $length
     * @param string $pad
     * @return string
     */
    private function extend(string $bound, int $length, string $pad): string
    {
        return ctype_digit($bound) && strlen($bound) < $length ? str_pad($bound, $length, $pad) : $bound;
    }
}
