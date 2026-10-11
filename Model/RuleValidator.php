<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use Magento\Framework\Model\AbstractModel;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

/**
 * Validates active rules against a quote address.
 *
 * Active rules are loaded once per request, and a rule that (directly or indirectly)
 * references itself through a "Shipping Rule" condition is treated as not valid
 * instead of recursing forever. The rule tester can also choose which rules count as active.
 */
class RuleValidator
{
    /**
     * @var array<int, Rule>|null
     */
    private ?array $activeRules = null;

    /**
     * @var array<int, true>
     */
    private array $validating = [];

    /**
     * Rules treated as active instead of the enabled ones, while the rule tester runs.
     *
     * @var int[]|null
     */
    private ?array $simulatedRuleIds = null;

    /**
     * Initialize dependencies.
     *
     * @param RuleCollectionFactory $ruleCollectionFactory
     */
    public function __construct(
        private readonly RuleCollectionFactory $ruleCollectionFactory
    ) {
    }

    /**
     * Whether valid.
     *
     * @param int $ruleId
     * @param AbstractModel $address
     * @return bool|null null when the rule does not exist or is inactive
     */
    public function isValid(int $ruleId, AbstractModel $address): ?bool
    {
        $rule = $this->getActiveRule($ruleId);
        if ($rule === null) {
            return null;
        }

        if (isset($this->validating[$ruleId])) {
            return false;
        }

        $this->validating[$ruleId] = true;
        try {
            return (bool)$rule->validate($address);
        } finally {
            unset($this->validating[$ruleId]);
        }
    }

    /**
     * First rule, in the given order, that is valid for the address.
     *
     * @param int[] $ruleIds
     * @param AbstractModel $address
     * @return Rule|null
     */
    public function getFirstValidRule(array $ruleIds, AbstractModel $address): ?Rule
    {
        foreach ($ruleIds as $ruleId) {
            if ($this->isValid((int)$ruleId, $address)) {
                return $this->getActiveRule((int)$ruleId);
            }
        }

        return null;
    }

    /**
     * Run a callback with exactly the given rules treated as active, enabled or not.
     *
     * @param int[] $ruleIds
     * @param callable $callback
     * @return mixed callback result
     */
    public function runWithActiveRules(array $ruleIds, callable $callback)
    {
        $previous = $this->simulatedRuleIds;
        $this->simulatedRuleIds = array_map('intval', $ruleIds);
        $this->activeRules = null;
        try {
            return $callback();
        } finally {
            $this->simulatedRuleIds = $previous;
            $this->activeRules = null;
        }
    }

    /**
     * Get active rule.
     *
     * @param int $ruleId
     * @return Rule|null
     */
    public function getActiveRule(int $ruleId): ?Rule
    {
        if ($this->activeRules === null) {
            $this->activeRules = [];
            if ($this->simulatedRuleIds === []) {
                return null;
            }
            $collection = $this->ruleCollectionFactory->create();
            if ($this->simulatedRuleIds === null) {
                $collection->addActiveFilter();
            } else {
                $collection->addFieldToFilter('main_table.rule_id', ['in' => $this->simulatedRuleIds]);
            }
            foreach ($collection as $rule) {
                $this->activeRules[(int)$rule->getId()] = $rule;
            }
        }

        return $this->activeRules[$ruleId] ?? null;
    }
}
