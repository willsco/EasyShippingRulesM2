<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api;

use Magento\Quote\Api\Data\AddressInterface;

/**
 * Condition implemented in code and selectable in the rule editor.
 *
 * Register implementations in di.xml:
 *
 * <type name="WillSco\EasyShippingRulesM2\Model\Rule\ProgrammaticConditionPool">
 *     <arguments>
 *         <argument name="conditions" xsi:type="array">
 *             <item name="my_condition" xsi:type="object">Vendor\Module\Model\MyCondition</item>
 *         </argument>
 *     </arguments>
 * </type>
 */
interface ProgrammaticConditionInterface
{
    /**
     * Label shown in the rule editor.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Whether valid.
     *
     * @param AddressInterface $address quote shipping address being rated
     * @return bool
     */
    public function isValid(AddressInterface $address): bool;
}
