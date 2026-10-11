<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Rows of the rate tables of custom methods ("rate table" calculation type).
 */
class RateTable extends AbstractDb
{
    public const MAIN_TABLE = 'willsco_esr_rate_table';

    /**
     * Row columns besides the row and method IDs, in spreadsheet order.
     */
    public const FIELDS = [
        'dest_country_id',
        'postcode_from',
        'postcode_to',
        'weight_from',
        'weight_to',
        'subtotal_from',
        'subtotal_to',
        'price',
        'delivery_time',
    ];

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'row_id');
    }

    /**
     * Rows of the given methods that may apply to a destination country, in table order.
     *
     * @param int[] $methodIds
     * @param string $countryId
     * @return array[]
     */
    public function getCandidateRows(array $methodIds, string $countryId): array
    {
        if (!$methodIds) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('method_id IN (?)', array_map('intval', $methodIds))
            ->where('dest_country_id IN (?)', array_unique(['', strtoupper($countryId)]))
            ->order('row_id ASC');

        return $connection->fetchAll($select);
    }

    /**
     * Rows of the given methods (all methods when null), by method then table order.
     *
     * @param int[]|null $methodIds
     * @return array[]
     */
    public function getRows(?array $methodIds = null): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->order(['method_id ASC', 'row_id ASC']);
        if ($methodIds !== null) {
            if (!$methodIds) {
                return [];
            }
            $select->where('method_id IN (?)', array_map('intval', $methodIds));
        }

        return $connection->fetchAll($select);
    }

    /**
     * Replace all rows of a method, keeping the given order.
     *
     * @param int $methodId
     * @param array[] $rows with the keys of FIELDS
     * @return void
     */
    public function replaceRows(int $methodId, array $rows): void
    {
        $connection = $this->getConnection();
        $connection->delete($this->getMainTable(), ['method_id = ?' => $methodId]);

        $insert = [];
        foreach ($rows as $row) {
            $data = ['method_id' => $methodId];
            foreach (self::FIELDS as $field) {
                $data[$field] = $row[$field] ?? null;
            }
            $data['dest_country_id'] = (string)$data['dest_country_id'];
            $data['postcode_from'] = (string)$data['postcode_from'];
            $data['postcode_to'] = (string)$data['postcode_to'];
            $data['delivery_time'] = (string)$data['delivery_time'];
            $insert[] = $data;
        }

        // Large tables are inserted in chunks to stay under max_allowed_packet.
        foreach (array_chunk($insert, 1000) as $chunk) {
            $connection->insertMultiple($this->getMainTable(), $chunk);
        }
    }
}
