<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Model\Rule\ProgrammaticConditionPool;

/**
 * "Programmatic condition is valid / is not valid <condition>".
 */
class Programmatic extends AbstractCondition
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param ProgrammaticConditionPool $conditionPool
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly ProgrammaticConditionPool $conditionPool,
        private readonly LoggerInterface $logger,
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
        $this->setAttributeOption(['programmatic' => __('Programmatic Condition')]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function loadValueOptions()
    {
        $this->setValueOption($this->conditionPool->getOptions());

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * @inheritdoc
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * @inheritdoc
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['select'] = ['==', '!='];
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultOperatorOptions()
    {
        if (null === $this->_defaultOperatorOptions) {
            parent::getDefaultOperatorOptions();
            $this->_defaultOperatorOptions['=='] = __('is valid');
            $this->_defaultOperatorOptions['!='] = __('is not valid');
        }

        return $this->_defaultOperatorOptions;
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
     * Validate.
     *
     * @param AbstractModel $model quote address
     * @return bool
     */
    public function validate(AbstractModel $model)
    {
        $condition = $this->conditionPool->get((string)$this->getValue());
        if ($condition === null || !$model instanceof AddressInterface) {
            return false;
        }

        try {
            $result = $condition->isValid($model);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return false;
        }

        return $this->getOperatorForValidate() === '!=' ? !$result : $result;
    }
}
