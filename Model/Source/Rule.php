<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory;

class Rule implements OptionSourceInterface
{
    /**
     * Initialize dependencies.
     *
     * @param CollectionFactory $collectionFactory
     * @param bool $withEmptyOption
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly bool $withEmptyOption = false
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = $this->collectionFactory->create()->setOrder('name', 'ASC')->toOptionArray();
        if ($this->withEmptyOption) {
            array_unshift($options, ['value' => '', 'label' => __('-- None --')]);
        }

        return $options;
    }
}
