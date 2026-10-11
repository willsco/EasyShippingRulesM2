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
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use WillSco\EasyShippingRulesM2\Api\Data\RuleInterface;
use WillSco\EasyShippingRulesM2\Api\RuleRepositoryInterface;
use WillSco\EasyShippingRulesM2\Block\Adminhtml\Rule\Conditions;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\FormDataTrait;
use WillSco\EasyShippingRulesM2\Model\Rule;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\BetweenOperator;
use WillSco\EasyShippingRulesM2\Model\Rule\ConditionTypes;
use WillSco\EasyShippingRulesM2\Model\RuleFactory;

class Save extends Action implements HttpPostActionInterface
{
    use FormDataTrait;

    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::rule';
    public const PERSISTOR_KEY = 'willsco_esr_rule';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param RuleRepositoryInterface $repository
     * @param RuleFactory $ruleFactory
     * @param DataPersistorInterface $dataPersistor
     * @param BetweenOperator $betweenOperator
     * @param ConditionTypes $conditionTypes
     */
    public function __construct(
        Context $context,
        private readonly RuleRepositoryInterface $repository,
        private readonly RuleFactory $ruleFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly BetweenOperator $betweenOperator,
        private readonly ConditionTypes $conditionTypes
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return Redirect
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/*/');
        }

        $id = (int)($data[RuleInterface::RULE_ID] ?? 0);
        try {
            /** @var Rule $rule */
            $rule = $id ? $this->repository->getById($id) : $this->ruleFactory->create();
            $rule->setName($this->toRequiredString($data[RuleInterface::NAME] ?? '', 'Rule Name'))
                ->setDescription(trim((string)($data[RuleInterface::DESCRIPTION] ?? '')) ?: null)
                ->setIsActive((bool)($data[RuleInterface::IS_ACTIVE] ?? true))
                ->setAmount($this->toNullableFloat($data[RuleInterface::AMOUNT] ?? null))
                ->setSortOrder((int)($data[RuleInterface::SORT_ORDER] ?? 0));

            // Condition inputs are posted as rule[conditions][1--1][...] by the rule editor.
            if (isset($data['rule']['conditions']) && is_array($data['rule']['conditions'])) {
                $this->validateTypes($data['rule']['conditions']);
                $this->validateRanges($data['rule']['conditions']);
                $rule->loadPost(['conditions' => $data['rule']['conditions']]);
            }

            $this->repository->save($rule);
            $this->messageManager->addSuccessMessage(__('The rule has been saved.'));
            $this->dataPersistor->clear(self::PERSISTOR_KEY);

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['id' => $rule->getId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This rule no longer exists.'));
            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the rule.'));
        }

        // The conditions editor is not a UI component: it reads what was typed from its own key.
        if (isset($data['rule']['conditions']) && is_array($data['rule']['conditions'])
            && $this->hasAllowedTypes($data['rule']['conditions'])
        ) {
            $this->dataPersistor->set(Conditions::PERSISTOR_KEY, $data['rule']['conditions']);
        }
        unset($data['rule']);
        $this->dataPersistor->set(self::PERSISTOR_KEY, $data);

        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }

    /**
     * Reject condition classes that do not belong to shipping rules.
     *
     * @param array $conditions Posted as rule[conditions][1--1][...]
     * @return void
     * @throws LocalizedException
     */
    private function validateTypes(array $conditions): void
    {
        if (!$this->hasAllowedTypes($conditions)) {
            throw new LocalizedException(__('The conditions contain a type that shipping rules do not support.'));
        }
    }

    /**
     * Whether every posted condition is a shipping rule condition.
     *
     * @param array $conditions
     * @return bool
     */
    private function hasAllowedTypes(array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $type = is_array($condition) ? explode('|', (string)($condition['type'] ?? ''))[0] : '';
            if (!$this->conditionTypes->isAllowed($type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reject "is between" conditions whose value is not a "min-max" range.
     *
     * Saved as is, such a condition would never match and the method would silently disappear.
     *
     * @param array $conditions Posted as rule[conditions][1--1][...]
     * @return void
     * @throws LocalizedException
     */
    private function validateRanges(array $conditions): void
    {
        foreach ($conditions as $condition) {
            if (!is_array($condition) || !$this->betweenOperator->isBetween($condition['operator'] ?? null)) {
                continue;
            }

            $value = trim((string)($condition['value'] ?? ''));
            if (!$this->betweenOperator->isValidRange($value)) {
                throw new LocalizedException(__(
                    '"%1" is not a valid range. Use "min-max" with the lower value first, '
                    . 'e.g. "10-50" or "01000-000-05999-999".',
                    $value
                ));
            }
        }
    }
}
