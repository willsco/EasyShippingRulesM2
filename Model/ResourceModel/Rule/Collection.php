<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule as RuleResource;
use WillSco\EasyShippingRulesM2\Model\Rule;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'rule_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Rule::class, RuleResource::class);
    }

    /**
     * Add active filter.
     *
     * @return $this
     */
    public function addActiveFilter(): self
    {
        return $this->addFieldToFilter('main_table.is_active', 1);
    }

    /**
     * Keep rules whose dates include the given day (YYYY-MM-DD); empty dates do not limit.
     *
     * @param string $date
     * @return $this
     */
    public function addDateFilter(string $date): self
    {
        $this->getSelect()
            ->where('main_table.from_date IS NULL OR main_table.from_date <= ?', $date)
            ->where('main_table.to_date IS NULL OR main_table.to_date >= ?', $date);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('rule_id', 'name');
    }
}
