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
    public function getCarrierId(): ?int
    {
        $carrierId = $this->getData(self::CARRIER_ID);

        return $carrierId ? (int)$carrierId : null;
    }

    /**
     * @inheritdoc
     */
    public function setCarrierId(?int $carrierId): CustomMethodInterface
    {
        return $this->setData(self::CARRIER_ID, $carrierId);
    }
}
