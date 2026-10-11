<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Block\Adminhtml\Rule;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions as ConditionsRenderer;
use Magento\Rule\Model\Condition\AbstractCondition;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule as RuleResource;
use WillSco\EasyShippingRulesM2\Model\Rule;
use WillSco\EasyShippingRulesM2\Model\RuleFactory;

/**
 * Conditions tree editor, embedded in the rule UI form through htmlContent.
 */
class Conditions extends Generic
{
    public const FORM_NAME = 'easyshippingrules_rule_form';

    /**
     * Conditions posted with a rule that failed to save, shown again instead of the saved ones.
     */
    public const PERSISTOR_KEY = 'willsco_esr_rule_conditions';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param ConditionsRenderer $conditionsRenderer
     * @param Fieldset $fieldsetRenderer
     * @param RuleFactory $ruleFactory
     * @param RuleResource $ruleResource
     * @param DataPersistorInterface $dataPersistor
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        private readonly ConditionsRenderer $conditionsRenderer,
        private readonly Fieldset $fieldsetRenderer,
        private readonly RuleFactory $ruleFactory,
        private readonly RuleResource $ruleResource,
        private readonly DataPersistorInterface $dataPersistor,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        $rule = $this->ruleFactory->create();
        $ruleId = (int)$this->getRequest()->getParam('id');
        if ($ruleId) {
            $this->ruleResource->load($rule, $ruleId);
        }
        $posted = $this->dataPersistor->get(self::PERSISTOR_KEY);
        if (is_array($posted)) {
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
            $rule->loadPost(['conditions' => $posted]);
        }

        $fieldsetId = $rule->getConditionsFieldSetId(self::FORM_NAME);
        $newChildUrl = $this->getUrl(
            '*/*/newConditionHtml/form/' . $fieldsetId,
            ['form_namespace' => self::FORM_NAME]
        );

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $this->fieldsetRenderer->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($newChildUrl)
            ->setFieldSetId($fieldsetId);

        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            ['legend' => __('Apply the rule only if the following conditions are met (leave blank to always apply).')]
        )->setRenderer($this->fieldsetRenderer);

        $fieldset->addField('conditions', 'text', [
            'name' => 'conditions',
            'label' => __('Conditions'),
            'title' => __('Conditions'),
            'data-form-part' => self::FORM_NAME,
        ])->setRule($rule)->setRenderer($this->conditionsRenderer);

        $this->prepareConditions($rule->getConditions(), $fieldsetId);
        $form->setValues($rule->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Tell every condition which UI form posts it and which JS object drives it.
     *
     * @param AbstractCondition $condition
     * @param string $jsFormObject
     * @return void
     */
    private function prepareConditions(AbstractCondition $condition, string $jsFormObject): void
    {
        $condition->setFormName(self::FORM_NAME);
        $condition->setJsFormObject($jsFormObject);
        foreach ((array)$condition->getConditions() as $child) {
            if ($child instanceof AbstractCondition) {
                $this->prepareConditions($child, $jsFormObject);
            }
        }
    }
}
