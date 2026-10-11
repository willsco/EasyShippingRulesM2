<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api\Data;

/**
 * New shipping method offered at checkout when one of its rules is valid.
 */
interface CustomMethodInterface extends MethodInterface
{
    public const CARRIER_IDS = 'carrier_ids';

    /**
     * Carriers the method is offered under; one rate per active carrier.
     *
     * @return int[]
     */
    public function getCarrierIds(): array;

    /**
     * Set carrier IDs.
     *
     * @param int[] $carrierIds
     * @return $this
     */
    public function setCarrierIds(array $carrierIds): self;
}
