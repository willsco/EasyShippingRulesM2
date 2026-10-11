<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\RateTable;

/**
 * Adds the delivery time of a rate table line to the method title shown at checkout.
 */
class DeliveryTimeFormatter
{
    /**
     * Title with the delivery time, in the locale of the current store.
     *
     * @param string $title method title
     * @param string $deliveryTime "N" or "N-M" business days; empty keeps the title
     * @return string
     */
    public function format(string $title, string $deliveryTime): string
    {
        if ($deliveryTime === '') {
            return $title;
        }

        [$min, $max] = array_pad(explode('-', $deliveryTime, 2), 2, $deliveryTime);
        if ($min !== $max) {
            return (string)__('%1 (%2 to %3 business days)', $title, $min, $max);
        }

        return $min === '1'
            ? (string)__('%1 (1 business day)', $title)
            : (string)__('%1 (%2 business days)', $title, $min);
    }
}
