<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Config\Source;

use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory;
use WillSco\EasyShippingRulesM2\Model\Source\Rule;

/**
 * Rules for the "Default Rule" setting, with an empty "none" option.
 */
class DefaultRule extends Rule
{
    /**
     * Initialize dependencies.
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        parent::__construct($collectionFactory, true);
    }
}
