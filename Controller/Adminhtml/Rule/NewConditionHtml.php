<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\ConditionFactory;
use WillSco\EasyShippingRulesM2\Model\Rule\ConditionTypes;
use WillSco\EasyShippingRulesM2\Model\RuleFactory;

/**
 * Renders a condition added in the rule editor.
 */
class NewConditionHtml extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::rule';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param ConditionFactory $conditionFactory
     * @param RuleFactory $ruleFactory
     * @param ConditionTypes $conditionTypes
     */
    public function __construct(
        Context $context,
        private readonly RawFactory $resultRawFactory,
        private readonly ConditionFactory $conditionFactory,
        private readonly RuleFactory $ruleFactory,
        private readonly ConditionTypes $conditionTypes
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return Raw
     */
    public function execute()
    {
        $result = $this->resultRawFactory->create();
        $typeParts = explode('|', str_replace('-', '/', (string)$this->getRequest()->getParam('type', '')));
        $type = $typeParts[0];

        // Only shipping rule conditions may be instantiated from request input.
        if (!$this->conditionTypes->isAllowed($type)) {
            return $result->setContents('');
        }

        // These end up in element IDs and inline JS: accept identifier characters only.
        $id = (string)$this->getRequest()->getParam('id');
        $form = (string)$this->getRequest()->getParam('form');
        $formName = (string)$this->getRequest()->getParam('form_namespace');
        if (!preg_match('/^[\w-]+$/', $id)
            || !preg_match('/^[A-Za-z_$][\w$]*$/', $form)
            || !preg_match('/^\w*$/', $formName)
        ) {
            return $result->setContents('');
        }

        /** @var AbstractCondition $condition */
        $condition = $this->conditionFactory->create($type);
        $condition->setId($id)
            ->setType($type)
            ->setRule($this->ruleFactory->create())
            ->setPrefix('conditions')
            ->setJsFormObject($form)
            ->setFormName($formName);

        if (!empty($typeParts[1])) {
            $condition->setAttribute($typeParts[1]);
        }

        return $result->setContents($condition->asHtmlRecursive());
    }
}
