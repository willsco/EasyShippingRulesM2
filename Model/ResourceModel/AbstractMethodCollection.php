<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\Store;

/**
 * Method collection that loads store and rule links for all items at once.
 *
 * @method AbstractMethod getResource()
 */
abstract class AbstractMethodCollection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'method_id';

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
     * Keep methods assigned to the given store view or to all store views.
     *
     * A subquery is used instead of a join so a method linked to both is not returned twice.
     *
     * @param int $storeId
     * @return $this
     */
    public function addStoreFilter(int $storeId): self
    {
        $subSelect = $this->getConnection()->select()
            ->from($this->getTable($this->getResource()->getStoreLinkTable()), ['method_id'])
            ->where('store_id IN (?)', array_unique([Store::DEFAULT_STORE_ID, $storeId]));

        $this->getSelect()->where('main_table.method_id IN (?)', $subSelect);

        return $this;
    }

    /**
     * Attach links and decode serialized fields, which the resource only does for single loads.
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $methodIds = array_map('intval', array_keys($this->_items));
        if ($methodIds) {
            $links = $this->getResource()->lookupAllLinks($methodIds);
            foreach ($this->_items as $methodId => $item) {
                $this->getResource()->unserializeFields($item);
                foreach ($links as $key => $ids) {
                    $item->setData($key, $ids[$methodId] ?? []);
                }
                $item->setDataChanges(false);
            }
        }

        return $this;
    }
}
