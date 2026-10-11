<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use WillSco\EasyShippingRulesM2\Api\Data\RuleInterface;

class Rule extends AbstractDb
{
    public const MAIN_TABLE = 'willsco_esr_rule';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'rule_id');
    }

    /**
     * Empty form values must reach the decimal column as NULL.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if ($object->getData(RuleInterface::AMOUNT) === '') {
            $object->setData(RuleInterface::AMOUNT, null);
        }

        return parent::_beforeSave($object);
    }
}
