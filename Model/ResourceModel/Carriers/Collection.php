<?php

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel\Carriers;

use WillSco\EasyShippingRulesM2\Model\Carriers as CarriersModel;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carriers as CarriersResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(CarriersModel::class, CarriersResourceModel::class);
    }
}
