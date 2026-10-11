<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\RateTable;

use Magento\Quote\Model\Quote\Address\RateRequest;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\RateTable as RateTableResource;

/**
 * Prices of "rate table" methods for a rate request: the first matching row of each table wins.
 */
class RateFinder
{
    /**
     * Initialize dependencies.
     *
     * @param RateTableResource $resource
     * @param RowMatcher $rowMatcher
     */
    public function __construct(
        private readonly RateTableResource $resource,
        private readonly RowMatcher $rowMatcher
    ) {
    }

    /**
     * Price and delivery time by method ID; methods without a matching row are left out.
     *
     * Subtotal is the package value after discounts, weight the package weight.
     *
     * @param int[] $methodIds
     * @param RateRequest $request
     * @return array<int, array{price: float, delivery_time: string}>
     */
    public function findRates(array $methodIds, RateRequest $request): array
    {
        $postcode = (string)$request->getDestPostcode();
        $weight = (float)$request->getPackageWeight();
        $subtotal = (float)$request->getPackageValueWithDiscount();

        $rates = [];
        foreach ($this->resource->getCandidateRows($methodIds, (string)$request->getDestCountryId()) as $row) {
            $methodId = (int)$row['method_id'];
            if (!isset($rates[$methodId]) && $this->rowMatcher->matches($row, $postcode, $weight, $subtotal)) {
                $rates[$methodId] = [
                    'price' => round(max((float)$row['price'], 0.0), 4),
                    'delivery_time' => (string)($row['delivery_time'] ?? ''),
                ];
            }
        }

        return $rates;
    }
}
