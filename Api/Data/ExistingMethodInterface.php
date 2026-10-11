<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api\Data;

/**
 * Changes rates returned by other carriers when one of its rules is valid.
 */
interface ExistingMethodInterface extends MethodInterface
{
    public const CARRIER_TITLE = 'carrier_title';
    public const METHOD_TITLE = 'method_title';
    public const ACTION_TYPE = 'action_type';
    public const SHIPPING_METHODS = 'shipping_methods';
    public const SORT_ORDER = 'sort_order';
    public const STOP_PROCESSING = 'stop_processing';

    public const ACTION_OVERWRITE = 'overwrite';
    public const ACTION_SURCHARGE = 'surcharge';
    public const ACTION_DISCOUNT = 'discount';
    public const ACTION_HIDE = 'hide';

    /**
     * Value of shipping_methods that matches every rate.
     */
    public const ALL_SHIPPING_METHODS = 'all';

    /**
     * Get carrier title.
     *
     * @return string|null
     */
    public function getCarrierTitle(): ?string;

    /**
     * Set carrier title.
     *
     * @param string|null $carrierTitle
     * @return $this
     */
    public function setCarrierTitle(?string $carrierTitle): self;

    /**
     * Get method title.
     *
     * @return string|null
     */
    public function getMethodTitle(): ?string;

    /**
     * Set method title.
     *
     * @param string|null $methodTitle
     * @return $this
     */
    public function setMethodTitle(?string $methodTitle): self;

    /**
     * Get action type.
     *
     * @return string
     */
    public function getActionType(): string;

    /**
     * Set action type.
     *
     * @param string $actionType
     * @return $this
     */
    public function setActionType(string $actionType): self;

    /**
     * Full shipping method codes ("carrier_method") the action applies to.
     *
     * @return string[]
     */
    public function getShippingMethods(): array;

    /**
     * Set shipping methods.
     *
     * @param string[] $shippingMethods
     * @return $this
     */
    public function setShippingMethods(array $shippingMethods): self;

    /**
     * Priority: existing methods run from the lowest value to the highest (then by ID).
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Set priority.
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self;

    /**
     * Whether the rates this method changes are left alone by the existing methods after it.
     *
     * @return bool
     */
    public function isStopProcessing(): bool;

    /**
     * Set stop processing.
     *
     * @param bool $stopProcessing
     * @return $this
     */
    public function setStopProcessing(bool $stopProcessing): self;
}
