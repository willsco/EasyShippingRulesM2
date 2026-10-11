<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Shipping;

/**
 * Builds and parses the shipping carrier codes used for custom methods.
 *
 * Each module carrier becomes a Magento carrier code "easyshippingrules{carrierId}".
 * There is no underscore in it, because Magento splits "carrier_method" on the first one.
 */
class CarrierCode
{
    public const PREFIX = 'easyshippingrules';

    /**
     * Build.
     *
     * @param int $carrierId
     * @return string
     */
    public function build(int $carrierId): string
    {
        return self::PREFIX . $carrierId;
    }

    /**
     * Build method.
     *
     * @param int $methodId
     * @return string
     */
    public function buildMethod(int $methodId): string
    {
        return 'm' . $methodId;
    }

    /**
     * Parse.
     *
     * @param string|null $carrierCode
     * @return int|null module carrier ID, or null when the code is not one of ours
     */
    public function parse(?string $carrierCode): ?int
    {
        if ($carrierCode !== null && preg_match('/^' . self::PREFIX . '(\d+)$/', $carrierCode, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}
