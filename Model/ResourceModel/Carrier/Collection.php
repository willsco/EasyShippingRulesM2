<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use WillSco\EasyShippingRulesM2\Model\Carrier;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier as CarrierResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'carrier_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Carrier::class, CarrierResource::class);
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('carrier_id', 'name');
    }
}
