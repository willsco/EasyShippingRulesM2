<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\AbstractModel;
use WillSco\EasyShippingRulesM2\Api\Data\RuleInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule as RuleResource;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\Combine;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\CombineFactory;

/**
 * Shipping rule: only conditions, no actions.
 */
class Rule extends AbstractModel implements RuleInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'willsco_esr_rule';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param TimezoneInterface $localeDate
     * @param CombineFactory $combineFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param ExtensionAttributesFactory|null $extensionFactory
     * @param AttributeValueFactory|null $customAttributeFactory
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        private readonly CombineFactory $combineFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
        ?ExtensionAttributesFactory $extensionFactory = null,
        ?AttributeValueFactory $customAttributeFactory = null,
        ?Json $serializer = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data,
            $extensionFactory,
            $customAttributeFactory,
            $serializer
        );
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(RuleResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getConditionsInstance(): Combine
    {
        return $this->combineFactory->create();
    }

    /**
     * Shipping rules have no actions; prices live in the rule amount and in the methods.
     *
     * @return null
     */
    public function getActionsInstance()
    {
        return null;
    }

    /**
     * Overridden so the parent never builds (and serializes) an actions tree.
     *
     * @return null
     */
    public function getActions()
    {
        return null;
    }

    /**
     * ID of the conditions fieldset, also the name of its JS object in the rule editor.
     *
     * @param string $formName
     * @return string
     */
    public function getConditionsFieldSetId(string $formName = ''): string
    {
        return $formName . 'rule_conditions_fieldset_' . $this->getId();
    }

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
    public function setName(string $name): RuleInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setDescription(?string $description): RuleInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
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
    public function setIsActive(bool $isActive): RuleInterface
    {
        return $this->setData(self::IS_ACTIVE, (int)$isActive);
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
    public function setAmount(?float $amount): RuleInterface
    {
        return $this->setData(self::AMOUNT, $amount);
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
    public function setSortOrder(int $sortOrder): RuleInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function getFromDate(): ?string
    {
        return $this->getDateValue(self::FROM_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setFromDate(?string $fromDate): RuleInterface
    {
        return $this->setData(self::FROM_DATE, $fromDate ?: null);
    }

    /**
     * @inheritdoc
     */
    public function getToDate(): ?string
    {
        return $this->getDateValue(self::TO_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setToDate(?string $toDate): RuleInterface
    {
        return $this->setData(self::TO_DATE, $toDate ?: null);
    }

    /**
     * Whether the given day (YYYY-MM-DD) is within the rule dates, both inclusive.
     *
     * @param string $date
     * @return bool
     */
    public function isWithinDates(string $date): bool
    {
        $from = $this->getFromDate();
        $to = $this->getToDate();

        return ($from === null || $from <= $date) && ($to === null || $to >= $date);
    }

    /**
     * Date column as YYYY-MM-DD (the database may add a time).
     *
     * @param string $key
     * @return string|null
     */
    private function getDateValue(string $key): ?string
    {
        $value = (string)$this->getData($key);

        return $value === '' ? null : substr($value, 0, 10);
    }

    /**
     * @inheritdoc
     */
    public function getConditionsJson(): ?string
    {
        // The parent drops the serialized column once the conditions tree is built.
        if ($this->hasData(self::CONDITIONS_SERIALIZED)) {
            $value = $this->getData(self::CONDITIONS_SERIALIZED);

            return $value === null || $value === '' ? null : (string)$value;
        }

        return $this->_conditions ? $this->serializer->serialize($this->getConditions()->asArray()) : null;
    }

    /**
     * @inheritdoc
     */
    public function setConditionsJson(?string $conditionsJson): RuleInterface
    {
        $this->_conditions = null;

        return $this->setData(self::CONDITIONS_SERIALIZED, $conditionsJson);
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
