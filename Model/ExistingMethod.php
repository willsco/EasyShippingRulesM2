<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\ExistingMethod as ExistingMethodResource;

class ExistingMethod extends AbstractMethod implements ExistingMethodInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'willsco_esr_existing_method';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ExistingMethodResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getCarrierTitle(): ?string
    {
        return $this->getData(self::CARRIER_TITLE) ?: null;
    }

    /**
     * @inheritdoc
     */
    public function setCarrierTitle(?string $carrierTitle): ExistingMethodInterface
    {
        return $this->setData(self::CARRIER_TITLE, $carrierTitle);
    }

    /**
     * @inheritdoc
     */
    public function getMethodTitle(): ?string
    {
        return $this->getData(self::METHOD_TITLE) ?: null;
    }

    /**
     * @inheritdoc
     */
    public function setMethodTitle(?string $methodTitle): ExistingMethodInterface
    {
        return $this->setData(self::METHOD_TITLE, $methodTitle);
    }

    /**
     * @inheritdoc
     */
    public function getActionType(): string
    {
        return (string)($this->getData(self::ACTION_TYPE) ?: self::ACTION_OVERWRITE);
    }

    /**
     * @inheritdoc
     */
    public function setActionType(string $actionType): ExistingMethodInterface
    {
        return $this->setData(self::ACTION_TYPE, $actionType);
    }

    /**
     * @inheritdoc
     */
    public function getShippingMethods(): array
    {
        return array_values(array_filter((array)$this->getData(self::SHIPPING_METHODS)));
    }

    /**
     * @inheritdoc
     */
    public function setShippingMethods(array $shippingMethods): ExistingMethodInterface
    {
        return $this->setData(self::SHIPPING_METHODS, $shippingMethods);
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setSortOrder(int $sortOrder): ExistingMethodInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function isStopProcessing(): bool
    {
        return (bool)$this->getData(self::STOP_PROCESSING);
    }

    /**
     * @inheritdoc
     */
    public function setStopProcessing(bool $stopProcessing): ExistingMethodInterface
    {
        return $this->setData(self::STOP_PROCESSING, (int)$stopProcessing);
    }
}
