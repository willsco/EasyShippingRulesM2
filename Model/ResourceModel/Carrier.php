<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Carrier extends AbstractDb
{
    public const MAIN_TABLE = 'willsco_esr_carrier';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'carrier_id');
    }
}
