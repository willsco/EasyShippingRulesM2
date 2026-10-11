<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api\Data;

/**
 * Fields shared by custom and existing methods.
 */
interface MethodInterface
{
    public const METHOD_ID = 'method_id';
    public const NAME = 'name';
    public const IS_ACTIVE = 'is_active';
    public const CALCULATION_TYPE = 'calculation_type';
    public const AMOUNT = 'amount';
    public const STORE_IDS = 'store_ids';
    public const RULE_IDS = 'rule_ids';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Amount is a fixed price for the whole cart.
     */
    public const CALCULATION_FIXED = 'fixed';

    /**
     * Amount is multiplied by the cart item quantity.
     */
    public const CALCULATION_FIXED_PER_ITEM = 'fixed_per_item';

    /**
     * Amount is a percentage of the cart subtotal.
     */
    public const CALCULATION_PERCENT_SUBTOTAL = 'percent_subtotal';

    /**
     * Amount is a percentage of the original rate price (existing methods only).
     */
    public const CALCULATION_PERCENT_RATE = 'percent_rate';

    /**
     * Price comes from the method's rate table row matching destination, weight and subtotal
     * (custom methods only).
     */
    public const CALCULATION_RATE_TABLE = 'rate_table';

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
     * Get calculation type.
     *
     * @return string
     */
    public function getCalculationType(): string;

    /**
     * Set calculation type.
     *
     * @param string $calculationType
     * @return $this
     */
    public function setCalculationType(string $calculationType): self;

    /**
     * Price or percentage. Null means "use the amount of the matched rule".
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
     * Get store IDs.
     *
     * @return int[]
     */
    public function getStoreIds(): array;

    /**
     * Set store IDs.
     *
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds): self;

    /**
     * Get rule IDs.
     *
     * @return int[]
     */
    public function getRuleIds(): array;

    /**
     * Set rule IDs.
     *
     * @param int[] $ruleIds
     * @return $this
     */
    public function setRuleIds(array $ruleIds): self;

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
