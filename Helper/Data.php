<?php

/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use WillSco\EasyShippingRulesM2\Model\Shipping\Carrier;
use WillSco\EasyShippingRulesM2\Model\CarriersFactory;

class Data extends AbstractHelper
{
    private CarriersFactory $carriersFactory;

    public function __construct(
        CarriersFactory $carriersFactory
    ) {
        $this->carriersFactory = $carriersFactory;
    }

    /**
     * @param string $carrierCode
     * @return string
     */
    public function getCarrierName(string $carrierCode)
    {
        if (strpos($carrierCode, Carrier::EASY_SHIPPING_RULES_CODE) === 0) {
            $carrierExploded = explode('_', $carrierCode);
            $carrierId       = $carrierExploded[1] ?? 0;

            //return Mage::getModel('easyshippingrules/carrier')
            //    ->load($carrierId)
            //    ->getName();
        }

        return false;
    }

    public function canCreateCarrier($name): bool
    {
        $collection = $this->carriersFactory->create()
            ->getCollection()
            ->addFilter("name", $name);
        if ($collection->count() > 0) {
            return false;
        } else {
            return true;
        }
    }
}
