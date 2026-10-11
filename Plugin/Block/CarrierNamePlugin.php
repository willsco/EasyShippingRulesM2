<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Plugin\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;
use WillSco\EasyShippingRulesM2\Model\Shipping\CarrierCode;

/**
 * Blocks that group rates read the carrier title from system config; use the module carrier name instead.
 *
 * Applied to admin order create, multishipping and PayPal Express review.
 */
class CarrierNamePlugin
{
    /**
     * Initialize dependencies.
     *
     * @param CarrierRepositoryInterface $carrierRepository
     * @param CarrierCode $carrierCode
     */
    public function __construct(
        private readonly CarrierRepositoryInterface $carrierRepository,
        private readonly CarrierCode $carrierCode
    ) {
    }

    /**
     * Plugin after get carrier name.
     *
     * @param object $subject
     * @param string $result
     * @param string $carrierCode
     * @return string
     */
    public function afterGetCarrierName($subject, $result, $carrierCode)
    {
        $carrierId = $this->carrierCode->parse((string)$carrierCode);
        if ($carrierId === null) {
            return $result;
        }

        try {
            return $this->carrierRepository->getById($carrierId)->getName() ?: $result;
        } catch (NoSuchEntityException $e) {
            return $result;
        }
    }
}
