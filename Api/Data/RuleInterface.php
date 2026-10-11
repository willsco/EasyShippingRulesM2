<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api\Data;

/**
 * Set of conditions validated against the quote address.
 */
interface RuleInterface
{
    public const RULE_ID = 'rule_id';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const IS_ACTIVE = 'is_active';
    public const CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const AMOUNT = 'amount';
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
     * Get description.
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set description.
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): self;

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
     * Price or percentage used by methods that have no amount of their own.
     *
     * @return float|null
     */
    public function getAmount(): ?float;

    /**
     * Set amount.
     *
     * @param float|null $amount
     * @return $this
     */
    public function setAmount(?float $amount): self;

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
