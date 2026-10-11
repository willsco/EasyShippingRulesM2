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

    /**
     * Labels by shipping method code.
     *
     * @return array<string, string>
     */
    public function getLabels(): array
    {
        $labels = [];
        foreach ($this->toOptionArray() as $option) {
            $labels[(string)$option['value']] = (string)$option['label'];
        }

        return $labels;
    }

    /**
     * Codes that no active carrier offers (carrier disabled, removed or code renamed).
     *
     * @param string[] $codes
     * @return string[]
     */
    public function getUnavailableCodes(array $codes): array
    {
        return array_values(array_diff($codes, array_keys($this->getLabels())));
    }
}
