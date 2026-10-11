<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use Magento\Framework\Model\AbstractModel;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;

/**
 * Accessors shared by custom and existing methods.
 */
abstract class AbstractMethod extends AbstractModel implements MethodInterface
{
    /**
     * @inheritdoc
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): MethodInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive(bool $isActive): MethodInterface
    {
        return $this->setData(self::IS_ACTIVE, (int)$isActive);
    }

    /**
     * @inheritdoc
     */
    public function getCalculationType(): string
    {
        return (string)($this->getData(self::CALCULATION_TYPE) ?: self::CALCULATION_FIXED);
    }

    /**
     * @inheritdoc
     */
    public function setCalculationType(string $calculationType): MethodInterface
    {
        return $this->setData(self::CALCULATION_TYPE, $calculationType);
    }

    /**
     * @inheritdoc
     */
    public function getAmount(): ?float
    {
        $amount = $this->getData(self::AMOUNT);

        return $amount === null || $amount === '' ? null : (float)$amount;
    }

    /**
     * @inheritdoc
     */
    public function setAmount(?float $amount): MethodInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @inheritdoc
     */
    public function getStoreIds(): array
    {
        return array_map('intval', (array)$this->getData(self::STORE_IDS));
    }

    /**
     * @inheritdoc
     */
    public function setStoreIds(array $storeIds): MethodInterface
    {
        return $this->setData(self::STORE_IDS, $storeIds);
    }

    /**
     * @inheritdoc
     */
    public function getRuleIds(): array
    {
        return array_map('intval', (array)$this->getData(self::RULE_IDS));
    }

    /**
     * @inheritdoc
     */
    public function setRuleIds(array $ruleIds): MethodInterface
    {
        return $this->setData(self::RULE_IDS, $ruleIds);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }
}
