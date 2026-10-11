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
    public const CARRIER_ID = 'carrier_id';

    /**
     * Get carrier ID.
     *
     * @return int|null
     */
    public function getCarrierId(): ?int;

    /**
     * Set carrier ID.
     *
     * @param int|null $carrierId
     * @return $this
     */
    public function setCarrierId(?int $carrierId): self;
}
