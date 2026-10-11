<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;

/**
 * Condition on an attribute of the customer that owns the quote.
 */
class Customer extends AbstractCondition
{
    use BetweenOperatorTrait;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param EavConfig $eavConfig
     * @param CustomerResource $customerResource
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly EavConfig $eavConfig,
        private readonly CustomerResource $customerResource,
        private readonly CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setType(self::class);
    }

    /**
     * @inheritdoc
     */
    public function loadAttributeOptions()
    {
        $attributes = [];
        foreach ($this->customerResource->loadAllAttributes()->getAttributesByCode() as $attribute) {
            if ($attribute->getFrontendLabel()) {
                $attributes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
            }
        }
        asort($attributes);
        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * Get attribute object.
     *
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute|DataObject
     */
    public function getAttributeObject()
    {
        try {
            return $this->eavConfig->getAttribute(CustomerModel::ENTITY, $this->getAttribute());
        } catch (\Exception $e) {
            return new DataObject(['frontend_input' => 'text']);
        }
    }

    /**
     * @inheritdoc
     */
    public function getInputType()
    {
        return match ((string)$this->getAttributeObject()->getFrontendInput()) {
            'select' => 'select',
            'multiselect' => 'multiselect',
            'date', 'datetime' => 'date',
            'boolean' => 'boolean',
            default => 'string',
        };
    }

    /**
     * @inheritdoc
     */
    public function getValueElementType()
    {
        return match ((string)$this->getAttributeObject()->getFrontendInput()) {
            'select', 'boolean' => 'select',
            'multiselect' => 'multiselect',
            'date', 'datetime' => 'date',
            default => 'text',
        };
    }

    /**
     * @inheritdoc
     */
    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            $attribute = $this->getAttributeObject();
            $options = [];
            if ($attribute->getFrontendInput() === 'boolean') {
                $options = [['value' => '1', 'label' => __('Yes')], ['value' => '0', 'label' => __('No')]];
            } elseif ($attribute instanceof \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
                && $attribute->usesSource()
            ) {
                $options = $attribute->getSource()->getAllOptions(false);
            }
            $this->setData('value_select_options', $options);
        }

        return $this->getData('value_select_options');
    }

    /**
     * Validate against the customer of the quote; guests never match.
     *
     * @param AbstractModel $model quote address
     * @return bool
     */
    public function validate(AbstractModel $model)
    {
        $customerId = (int)($model->getQuote() ? $model->getQuote()->getCustomerId() : $model->getCustomerId());
        $customer = $customerId ? $this->loadCustomer($customerId) : null;

        if ($customer === null) {
            return false;
        }

        return $this->validateAttribute($this->getAttributeValue($customer, (string)$this->getAttribute()));
    }

    /**
     * Load customer; the repository keeps loaded customers for the request.
     *
     * @param int $customerId
     * @return CustomerInterface|null
     */
    private function loadCustomer(int $customerId): ?CustomerInterface
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Value of a system or custom attribute of the customer.
     *
     * @param CustomerInterface $customer
     * @param string $attributeCode
     * @return mixed
     */
    private function getAttributeValue(CustomerInterface $customer, string $attributeCode)
    {
        $customAttribute = $customer->getCustomAttribute($attributeCode);
        if ($customAttribute !== null) {
            return $customAttribute->getValue();
        }

        return $customer->__toArray()[$attributeCode] ?? null;
    }
}
