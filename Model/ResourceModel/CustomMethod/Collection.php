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
     * Keep only methods whose carrier exists and is active, ordered by carrier position.
     *
     * Adds the carrier name to each item as "carrier_name".
     *
     * @return $this
     */
    public function addActiveCarrierFilter(): self
    {
        $this->getSelect()
            ->join(
                ['carrier' => $this->getTable(CarrierResource::MAIN_TABLE)],
                'carrier.carrier_id = main_table.carrier_id AND carrier.is_active = 1',
                ['carrier_name' => 'carrier.name']
            )
            ->order('carrier.sort_order ASC')
            ->order('main_table.method_id ASC');

        return $this;
    }
}
