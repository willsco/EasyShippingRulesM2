<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use WillSco\EasyShippingRulesM2\Model\RuleValidator;

/**
 * Reuses other shipping rules: "Shipping Rule is valid / is not valid 1,2,3".
 */
class ShippingRule extends AbstractCondition
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param UrlInterface $backendUrl
     * @param RuleValidator $ruleValidator
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly UrlInterface $backendUrl,
        private readonly RuleValidator $ruleValidator,
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
        $this->setAttributeOption(['rule' => __('Shipping Rule')]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getInputType()
    {
        return 'boolean';
    }

    /**
     * @inheritdoc
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['boolean'] = ['==', '!='];
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
     * Chooser trigger shown next to the value.
     *
     * @return string
     */
    public function getValueAfterElementHtml()
    {
        $image = $this->_assetRepo->getUrl('images/rule_chooser_trigger.gif');

        return '<a href="javascript:void(0)" class="rule-chooser-trigger"><img src="' . $image
            . '" alt="" class="v-middle rule-chooser-trigger" title="' . __('Open Chooser') . '" /></a>';
    }

    /**
     * Get value element chooser URL.
     *
     * @return string
     */
    public function getValueElementChooserUrl()
    {
        $params = $this->getJsFormObject() ? ['form' => $this->getJsFormObject()] : [];

        return $this->backendUrl->getUrl('easyshippingrules/rule/chooser', $params);
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
     * "is valid" requires every listed rule to be valid; "is not valid" requires every one to fail.
     *
     * @param AbstractModel $model quote address
     * @return bool
     */
    public function validate(AbstractModel $model)
    {
        $ruleIds = array_filter(array_map('intval', explode(',', (string)$this->getValueParsed())));
        if (!$ruleIds) {
            return false;
        }

        $expected = $this->getOperatorForValidate() === '==';
        foreach ($ruleIds as $ruleId) {
            $result = $this->ruleValidator->isValid($ruleId, $model);
            if ($result === null || $result !== $expected) {
                return false;
            }
        }

        return true;
    }
}
