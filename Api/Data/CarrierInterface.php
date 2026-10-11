<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api\Data;

/**
 * Carrier that groups custom shipping methods at checkout.
 */
interface CarrierInterface
{
    public const CARRIER_ID = 'carrier_id';
    public const NAME = 'name';
    public const IS_ACTIVE = 'is_active';
    public const SORT_ORDER = 'sort_order';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get ID.
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID.
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Whether active.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Set is active.
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self;

    /**
     * Get sort order.
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Set sort order.
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self;

    /**
     * Get created at.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Get updated at.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
