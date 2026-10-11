<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Tester;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Turns the rule tester form into a simulation input.
 */
class InputParser
{
    /**
     * Initialize dependencies.
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Parse posted data. Items are typed one per line as "SKU, qty" (qty defaults to 1).
     *
     * @param array $data
     * @return SimulationInput
     * @throws LocalizedException
     */
    public function parse(array $data): SimulationInput
    {
        $countryId = strtoupper(trim((string)($data['country_id'] ?? '')));
        if ($countryId === '') {
            throw new LocalizedException(__('"%1" is required.', __('Country')));
        }

        $storeId = (int)($data['store_id'] ?? 0);
        if (!$storeId) {
            $storeId = (int)$this->storeManager->getDefaultStoreView()->getId();
        }

        return new SimulationInput(
            $storeId,
            $countryId,
            trim((string)($data['region'] ?? '')),
            trim((string)($data['postcode'] ?? '')),
            trim((string)($data['city'] ?? '')),
            (int)($data['customer_group_id'] ?? 0),
            trim((string)($data['customer_email'] ?? '')),
            $this->parseItems((string)($data['items'] ?? '')),
            $this->parseRuleIds($data)
        );
    }

    /**
     * Rules chosen as active; null when the form did not send the list.
     *
     * @param array $data
     * @return int[]|null
     */
    private function parseRuleIds(array $data): ?array
    {
        if (!isset($data['rule_ids'])) {
            return null;
        }

        return array_values(array_unique(array_filter(array_map('intval', (array)$data['rule_ids']))));
    }

    /**
     * Quantities indexed by SKU; repeated SKUs are summed.
     *
     * @param string $text
     * @return array<string, float>
     */
    public function parseItems(string $text): array
    {
        $items = [];
        foreach (preg_split('/\R/', $text) as $line) {
            $parts = array_map('trim', explode(',', $line, 2));
            if ($parts[0] === '') {
                continue;
            }
            $qty = isset($parts[1]) && is_numeric($parts[1]) && (float)$parts[1] > 0 ? (float)$parts[1] : 1.0;
            $items[$parts[0]] = ($items[$parts[0]] ?? 0.0) + $qty;
        }

        return $items;
    }
}
