<?php

namespace WillSco\EasyShippingRulesM2\Model\Shipping;

class Carrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    const EASY_SHIPPING_RULES_CODE = 'easyshippingrules';

    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        return false;
    }

    /**
     * Overwrite and returning false to break unnecessary functionality
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array();
    }
}
