<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod as CustomMethodResource;

class CustomMethod extends AbstractMethod implements CustomMethodInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'willsco_esr_custom_method';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(CustomMethodResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getCarrierIds(): array
    {
        return array_map('intval', (array)$this->getData(self::CARRIER_IDS));
    }

    /**
     * @inheritdoc
     */
    public function setCarrierIds(array $carrierIds): CustomMethodInterface
    {
        return $this->setData(self::CARRIER_IDS, $carrierIds);
    }
}
