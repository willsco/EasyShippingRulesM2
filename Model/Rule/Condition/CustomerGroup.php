<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;

/**
 * "Customer Group is one of / is not one of ...", including NOT LOGGED IN for guests.
 */
class CustomerGroup extends AbstractCondition
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly GroupCollectionFactory $groupCollectionFactory,
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
        $this->setAttributeOption(['customer_group_id' => __('Customer Group')]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function loadValueOptions()
    {
        $options = [];
        foreach ($this->groupCollectionFactory->create()->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }
        $this->setValueOption($options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getInputType()
    {
        return 'multiselect';
    }

    /**
     * @inheritdoc
     */
    public function getValueElementType()
    {
        return 'multiselect';
    }

    /**
     * Only "is one of" and "is not one of".
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['multiselect'] = ['()', '!()'];
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeElement()
    {
        return parent::getAttributeElement()->setShowAsText(true);
    }

    /**
     * Get explicit apply.
     *
     * @return bool
     */
    public function getExplicitApply()
    {
        return true;
    }

    /**
     * Selected group IDs, always as an array of strings.
     *
     * The parent turns a single selected value into a scalar and only splits it back when it is truthy,
     * so selecting only NOT LOGGED IN ("0") would never match.
     *
     * @return string[]
     */
    public function getValueParsed()
    {
        if (!$this->hasValueParsed()) {
            $value = $this->getValue();
            $values = is_array($value)
                ? $value
                : preg_split('#\s*[,;]\s*#', (string)$value, -1, PREG_SPLIT_NO_EMPTY);
            $this->setValueParsed(array_map('strval', $values));
        }

        return $this->getData('value_parsed');
    }

    /**
     * Validate against the customer group of the quote (0 = NOT LOGGED IN).
     *
     * @param AbstractModel $model quote address
     * @return bool
     */
    public function validate(AbstractModel $model)
    {
        $quote = $model->getQuote();
        $groupId = $quote ? $quote->getCustomerGroupId() : $model->getCustomerGroupId();

        return $this->validateAttribute((string)(int)$groupId);
    }
}
