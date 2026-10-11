<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Api\Data\RuleInterface;
use WillSco\EasyShippingRulesM2\Api\Data\RuleSearchResultsInterface;
use WillSco\EasyShippingRulesM2\Api\Data\RuleSearchResultsInterfaceFactory;
use WillSco\EasyShippingRulesM2\Api\RuleRepositoryInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule as RuleResource;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory;
use WillSco\EasyShippingRulesM2\Model\Rule\ConditionTypes;

class RuleRepository implements RuleRepositoryInterface, ResetAfterRequestInterface
{
    /**
     * @var array<int, RuleInterface>
     */
    private array $instances = [];

    /**
     * Initialize dependencies.
     *
     * @param RuleResource $resource
     * @param RuleFactory $ruleFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param RuleSearchResultsInterfaceFactory $searchResultsFactory
     * @param LoggerInterface $logger
     * @param ConditionTypes $conditionTypes
     * @param Json $json
     */
    public function __construct(
        private readonly RuleResource $resource,
        private readonly RuleFactory $ruleFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly RuleSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly LoggerInterface $logger,
        private readonly ConditionTypes $conditionTypes,
        private readonly Json $json
    ) {
    }

    /**
     * Drop cached data between requests of a long-running application server.
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->instances = [];
    }

    /**
     * @inheritdoc
     */
    public function save(RuleInterface $rule): RuleInterface
    {
        $this->validateConditions($rule);
        $this->validateDates($rule);
        try {
            /** @var Rule $rule */
            $this->resource->save($rule);
        } catch (\Exception $e) {
            // The original message may expose database details: log it, show a generic one.
            $this->logger->error($e);
            throw new CouldNotSaveException(__('Could not save the rule.'), $e);
        }
        unset($this->instances[(int)$rule->getId()]);

        return $rule;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $ruleId): RuleInterface
    {
        if (!isset($this->instances[$ruleId])) {
            $rule = $this->ruleFactory->create();
            $this->resource->load($rule, $ruleId);
            if (!$rule->getId()) {
                throw new NoSuchEntityException(__('The rule with ID "%1" does not exist.', $ruleId));
            }
            $this->instances[$ruleId] = $rule;
        }

        return $this->instances[$ruleId];
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RuleSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(RuleInterface $rule): bool
    {
        try {
            /** @var Rule $rule */
            $this->resource->delete($rule);
        } catch (\Exception $e) {
            // The original message may expose database details: log it, show a generic one.
            $this->logger->error($e);
            throw new CouldNotDeleteException(__('Could not delete the rule.'), $e);
        }
        unset($this->instances[(int)$rule->getId()]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $ruleId): bool
    {
        return $this->delete($this->getById($ruleId));
    }

    /**
     * Dates must be YYYY-MM-DD, the first not after the last.
     *
     * @param RuleInterface $rule
     * @return void
     * @throws CouldNotSaveException
     */
    private function validateDates(RuleInterface $rule): void
    {
        foreach ([$rule->getFromDate(), $rule->getToDate()] as $date) {
            if ($date !== null && \DateTime::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') !== $date) {
                throw new CouldNotSaveException(__('"%1" is not a valid date. Use YYYY-MM-DD.', $date));
            }
        }

        $from = $rule->getFromDate();
        $to = $rule->getToDate();
        if ($from !== null && $to !== null && $from > $to) {
            throw new CouldNotSaveException(
                __('The rule ends before it starts. Check the "Valid From" and "Valid To" dates.')
            );
        }
    }

    /**
     * Conditions may come from the REST API or an import: check them before any class is instantiated.
     *
     * @param RuleInterface $rule
     * @return void
     * @throws CouldNotSaveException
     */
    private function validateConditions(RuleInterface $rule): void
    {
        $json = $rule->getConditionsJson();
        if ($json === null) {
            return;
        }

        try {
            $tree = $this->json->unserialize($json);
        } catch (\InvalidArgumentException $e) {
            throw new CouldNotSaveException(__('The rule conditions are not valid JSON.'));
        }

        if (!is_array($tree) || !$this->conditionTypes->isTreeAllowed($tree)) {
            throw new CouldNotSaveException(
                __('The conditions contain a type that shipping rules do not support.')
            );
        }
    }
}
