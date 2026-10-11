<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;

/**
 * Persists the store and rule links of a method.
 */
abstract class AbstractMethod extends AbstractDb
{
    /**
     * Link table that holds the method store views.
     *
     * @return string
     */
    abstract public function getStoreLinkTable(): string;

    /**
     * Link table that holds the method rules.
     *
     * @return string
     */
    abstract public function getRuleLinkTable(): string;

    /**
     * Empty form values must reach the decimal column as NULL ("use the rule amount").
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if ($object->getData(MethodInterface::AMOUNT) === '') {
            $object->setData(MethodInterface::AMOUNT, null);
        }

        return parent::_beforeSave($object);
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $methodId = (int)$object->getId();
        if ($methodId) {
            $object->setData(MethodInterface::STORE_IDS, $this->lookupStoreIds([$methodId])[$methodId] ?? []);
            $object->setData(MethodInterface::RULE_IDS, $this->lookupRuleIds([$methodId])[$methodId] ?? []);
        }

        return parent::_afterLoad($object);
    }

    /**
     * Links are only rewritten when present in the object, so partial saves keep them.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $methodId = (int)$object->getId();
        if ($object->hasData(MethodInterface::STORE_IDS)) {
            $this->saveLinks(
                $this->getStoreLinkTable(),
                'store_id',
                $methodId,
                (array)$object->getData(MethodInterface::STORE_IDS)
            );
        }
        if ($object->hasData(MethodInterface::RULE_IDS)) {
            $this->saveLinks(
                $this->getRuleLinkTable(),
                'rule_id',
                $methodId,
                (array)$object->getData(MethodInterface::RULE_IDS)
            );
        }

        return parent::_afterSave($object);
    }

    /**
     * Lookup store IDs.
     *
     * @param int[] $methodIds
     * @return array<int, int[]> store IDs indexed by method ID
     */
    public function lookupStoreIds(array $methodIds): array
    {
        return $this->lookupLinks($this->getStoreLinkTable(), 'store_id', $methodIds);
    }

    /**
     * Lookup rule IDs.
     *
     * @param int[] $methodIds
     * @return array<int, int[]> rule IDs indexed by method ID
     */
    public function lookupRuleIds(array $methodIds): array
    {
        return $this->lookupLinks($this->getRuleLinkTable(), 'rule_id', $methodIds);
    }

    /**
     * Lookup links.
     *
     * @param string $table
     * @param string $column
     * @param int[] $methodIds
     * @return array<int, int[]>
     */
    private function lookupLinks(string $table, string $column, array $methodIds): array
    {
        if (!$methodIds) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable($table), ['method_id', $column])
            ->where('method_id IN (?)', $methodIds);

        $links = [];
        foreach ($connection->fetchAll($select) as $row) {
            $links[(int)$row['method_id']][] = (int)$row[$column];
        }

        return $links;
    }

    /**
     * Save links.
     *
     * @param string $table
     * @param string $column
     * @param int $methodId
     * @param array $newIds
     * @return void
     */
    private function saveLinks(string $table, string $column, int $methodId, array $newIds): void
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable($table);

        $newIds = array_unique(array_map('intval', array_filter($newIds, 'is_numeric')));
        $oldIds = $this->lookupLinks($table, $column, [$methodId])[$methodId] ?? [];

        $delete = array_diff($oldIds, $newIds);
        if ($delete) {
            $connection->delete($tableName, ['method_id = ?' => $methodId, $column . ' IN (?)' => $delete]);
        }

        $insert = array_diff($newIds, $oldIds);
        if ($insert) {
            $rows = [];
            foreach ($insert as $id) {
                $rows[] = ['method_id' => $methodId, $column => $id];
            }
            $connection->insertMultiple($tableName, $rows);
        }
    }
}
