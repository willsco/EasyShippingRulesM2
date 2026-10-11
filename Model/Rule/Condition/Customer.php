<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;

/**
 * Condition on an attribute of the customer that owns the quote.
 */
class Customer extends AbstractCondition implements ResetAfterRequestInterface
{
    use BetweenOperatorTrait;

    /**
     * @var array<int, CustomerModel|null>
     */
    private array $customers = [];

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param EavConfig $eavConfig
     * @param CustomerResource $customerResource
     * @param CustomerFactory $customerFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly EavConfig $eavConfig,
        private readonly CustomerResource $customerResource,
        private readonly CustomerFactory $customerFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setType(self::class);
    }

    /**
     * Drop cached data between requests of a long-running application server.
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->customers = [];
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

        return $this->validateAttribute($customer->getData($this->getAttribute()));
    }

    /**
     * Load customer.
     *
     * @param int $customerId
     * @return CustomerModel|null
     */
    private function loadCustomer(int $customerId): ?CustomerModel
    {
        if (!array_key_exists($customerId, $this->customers)) {
            $customer = $this->customerFactory->create();
            $this->customerResource->load($customer, $customerId);
            $this->customers[$customerId] = $customer->getId() ? $customer : null;
        }

        return $this->customers[$customerId];
    }
}
