<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel\ExistingMethod;

use WillSco\EasyShippingRulesM2\Model\ExistingMethod;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\AbstractMethodCollection;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\ExistingMethod as ExistingMethodResource;

class Collection extends AbstractMethodCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ExistingMethod::class, ExistingMethodResource::class);
    }
}
