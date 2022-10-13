<?php

namespace WillSco\EasyShippingRulesM2\Model;

use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carriers as CarriersResourceModel;
use Magento\Framework\Model\AbstractModel;

class Carriers extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(CarriersResourceModel::class);
    }
}
