<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel;

use Magento\Framework\DataObject;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;

class ExistingMethod extends AbstractMethod
{
    public const MAIN_TABLE = 'willsco_esr_existing_method';
    public const STORE_TABLE = 'willsco_esr_existing_method_store';
    public const RULE_TABLE = 'willsco_esr_existing_method_rule';

    /**
     * Stored as JSON, exposed as array.
     *
     * @var array
     */
    protected $_serializableFields = [
        ExistingMethodInterface::SHIPPING_METHODS => [[], []],
    ];

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'method_id');
    }

    /**
     * Skip values that are already decoded, so collections and single loads can both call it.
     *
     * @param DataObject $object
     * @param string $field
     * @param mixed $defaultValue
     * @return void
     */
    protected function _unserializeField(DataObject $object, $field, $defaultValue = null)
    {
        if (!is_array($object->getData($field))) {
            parent::_unserializeField($object, $field, $defaultValue);
        }
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
