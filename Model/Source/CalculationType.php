<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;

class CalculationType implements OptionSourceInterface
{
    /**
     * Initialize dependencies.
     *
     * @param bool $withRatePercentage also offer "percentage of the original rate" (existing methods)
     */
    public function __construct(
        private readonly bool $withRatePercentage = false
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => MethodInterface::CALCULATION_FIXED, 'label' => __('Fixed (whole cart)')],
            ['value' => MethodInterface::CALCULATION_FIXED_PER_ITEM, 'label' => __('Fixed (per item qty)')],
            ['value' => MethodInterface::CALCULATION_PERCENT_SUBTOTAL, 'label' => __('Percentage of subtotal')],
        ];
        if ($this->withRatePercentage) {
            $options[] = ['value' => MethodInterface::CALCULATION_PERCENT_RATE, 'label' => __('Percentage of rate')];
        }

        return $options;
    }
}
