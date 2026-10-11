<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

/**
 * Validates active rules against a quote address.
 *
 * Active rules are loaded once per request, and a rule that (directly or indirectly)
 * references itself through a "Shipping Rule" condition is treated as not valid
 * instead of recursing forever. The rule tester can also choose which rules count as active.
 * While rates are processed, each rule is evaluated at most once (see runMemoized()).
 */
class RuleValidator implements ResetAfterRequestInterface
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
     * Results of the current memoized run, by rule ID and validated object; null outside of one.
     *
     * @var array<string, bool>|null
     */
    private ?array $results = null;

    /**
     * How many times a cycle was cut, so results depending on a cut are not memoized.
     *
     * @var int
     */
    private int $cycleCuts = 0;

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
     * Drop cached data between requests of a long-running application server.
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->activeRules = null;
        $this->results = null;
        $this->validating = [];
        $this->cycleCuts = 0;
        $this->simulatedRuleIds = null;
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

        $key = $ruleId . ':' . spl_object_id($address);
        if (isset($this->results[$key])) {
            return $this->results[$key];
        }

        if (isset($this->validating[$ruleId])) {
            $this->cycleCuts++;
            return false;
        }

        $cycleCuts = $this->cycleCuts;
        $this->validating[$ruleId] = true;
        try {
            $valid = (bool)$rule->validate($address);
        } finally {
            unset($this->validating[$ruleId]);
        }

        // A result reached through a cut cycle depends on where the evaluation started.
        if ($this->results !== null && $cycleCuts === $this->cycleCuts) {
            $this->results[$key] = $valid;
        }

        return $valid;
    }

    /**
     * Run a callback in which each rule result is computed once and reused.
     *
     * Only for a callback that validates a single address that does not change meanwhile,
     * like one rate request: methods and "Shipping Rule" conditions then share the results.
     *
     * @param callable $callback
     * @return mixed callback result
     */
    public function runMemoized(callable $callback)
    {
        if ($this->results !== null) {
            return $callback();
        }

        $this->results = [];
        try {
            return $callback();
        } finally {
            $this->results = null;
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
        $previousResults = $this->results;
        $this->simulatedRuleIds = array_map('intval', $ruleIds);
        $this->activeRules = null;
        $this->results = null;
        try {
            return $callback();
        } finally {
            $this->simulatedRuleIds = $previous;
            $this->activeRules = null;
            $this->results = $previousResults;
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
