<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Tester;

/**
 * Cart described in the rule tester form.
 */
class SimulationInput
{
    /**
     * Initialize the input.
     *
     * @param int $storeId
     * @param string $countryId
     * @param string $region region code or name
     * @param string $postcode
     * @param string $city
     * @param int $customerGroupId used for guests
     * @param string $customerEmail when set, the cart belongs to this customer
     * @param array $items quantities indexed by SKU
     * @param int[]|null $activeRuleIds rules treated as active; null keeps the enabled ones
     */
    public function __construct(
        public readonly int $storeId,
        public readonly string $countryId,
        public readonly string $region,
        public readonly string $postcode,
        public readonly string $city,
        public readonly int $customerGroupId,
        public readonly string $customerEmail,
        public readonly array $items,
        public readonly ?array $activeRuleIds = null
    ) {
    }
}
