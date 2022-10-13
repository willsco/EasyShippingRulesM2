<?php
namespace WillSco\EasyShippingRulesM2\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Carriers extends AbstractDb
{
    const MAIN_TABLE = 'willsco_esrm2_carriers';
    const ID_FIELD_NAME = 'id';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
