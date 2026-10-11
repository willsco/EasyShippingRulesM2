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
 * Persists the links of a method (store views, rules and, for custom methods, carriers).
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
     * Link tables by the data key that holds the linked IDs.
     *
     * @return array<string, array{table: string, column: string}>
     */
    public function getLinks(): array
    {
        return [
            MethodInterface::STORE_IDS => ['table' => $this->getStoreLinkTable(), 'column' => 'store_id'],
            MethodInterface::RULE_IDS => ['table' => $this->getRuleLinkTable(), 'column' => 'rule_id'],
        ];
    }

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
            foreach ($this->lookupAllLinks([$methodId]) as $key => $ids) {
                $object->setData($key, $ids[$methodId] ?? []);
            }
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
        foreach ($this->getLinks() as $key => $link) {
            if ($object->hasData($key)) {
                $this->saveLinks($link['table'], $link['column'], $methodId, (array)$object->getData($key));
            }
        }

        return parent::_afterSave($object);
    }

    /**
     * Linked IDs of the given methods, for every link table.
     *
     * @param int[] $methodIds
     * @return array<string, array<int, int[]>> IDs by data key, then by method ID
     */
    public function lookupAllLinks(array $methodIds): array
    {
        $result = [];
        foreach ($this->getLinks() as $key => $link) {
            $result[$key] = $this->lookupLinks($link['table'], $link['column'], $methodIds);
        }

        return $result;
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
