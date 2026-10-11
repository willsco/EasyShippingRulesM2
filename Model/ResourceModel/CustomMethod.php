<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel;

class CustomMethod extends AbstractMethod
{
    public const MAIN_TABLE = 'willsco_esr_custom_method';
    public const STORE_TABLE = 'willsco_esr_custom_method_store';
    public const RULE_TABLE = 'willsco_esr_custom_method_rule';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'method_id');
    }

    /**
     * @inheritdoc
     */
    public function getStoreLinkTable(): string
    {
        return self::STORE_TABLE;
    }

    /**
     * @inheritdoc
     */
    public function getRuleLinkTable(): string
    {
        return self::RULE_TABLE;
    }
}
