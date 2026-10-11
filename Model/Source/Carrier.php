<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier\CollectionFactory;

class Carrier implements OptionSourceInterface
{
    /**
     * Initialize dependencies.
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return $this->collectionFactory->create()->setOrder('name', 'ASC')->toOptionArray();
    }
}
