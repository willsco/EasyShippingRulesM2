<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;

class ActionType implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => ExistingMethodInterface::ACTION_OVERWRITE, 'label' => __('Overwrite price')],
            ['value' => ExistingMethodInterface::ACTION_DISCOUNT, 'label' => __('Discount')],
            ['value' => ExistingMethodInterface::ACTION_SURCHARGE, 'label' => __('Surcharge')],
            ['value' => ExistingMethodInterface::ACTION_HIDE, 'label' => __('Hide')],
        ];
    }
}
