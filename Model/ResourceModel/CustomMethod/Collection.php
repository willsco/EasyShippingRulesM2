<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod;

use WillSco\EasyShippingRulesM2\Model\CustomMethod;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\AbstractMethodCollection;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier as CarrierResource;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod as CustomMethodResource;

class Collection extends AbstractMethodCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(CustomMethod::class, CustomMethodResource::class);
    }

    /**
     * Keep only methods linked to at least one active carrier.
     *
     * @return $this
     */
    public function addActiveCarrierFilter(): self
    {
        $subSelect = $this->getConnection()->select()
            ->from(['link' => $this->getTable(CustomMethodResource::CARRIER_TABLE)], ['method_id'])
            ->join(
                ['carrier' => $this->getTable(CarrierResource::MAIN_TABLE)],
                'carrier.carrier_id = link.carrier_id',
                []
            )
            ->where('carrier.is_active = ?', 1);

        $this->getSelect()->where('main_table.method_id IN (?)', $subSelect);

        return $this;
    }
}
