<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Config\Source\Allmethods;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;

/**
 * "All shipping methods" plus every method of the active carriers, as "carrier_method" codes.
 */
class ShippingMethods implements OptionSourceInterface
{
    /**
     * Initialize dependencies.
     *
     * @param Allmethods $allMethods
     */
    public function __construct(
        private readonly Allmethods $allMethods
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [['value' => ExistingMethodInterface::ALL_SHIPPING_METHODS, 'label' => __('All Shipping Methods')]];

        foreach ($this->allMethods->toOptionArray(true) as $carrier) {
            if (!is_array($carrier['value'] ?? null)) {
                continue;
            }
            foreach ($carrier['value'] as $method) {
                $options[] = ['value' => $method['value'], 'label' => $carrier['label'] . ' - ' . $method['label']];
            }
        }

        return $options;
    }
}
