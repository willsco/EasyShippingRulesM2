<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Api\Data\RuleInterface;
use WillSco\EasyShippingRulesM2\Api\RuleRepositoryInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule as RuleResource;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory;

class RuleRepository implements RuleRepositoryInterface
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
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RuleResource $resource,
        private readonly RuleFactory $ruleFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(RuleInterface $rule): RuleInterface
    {
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
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
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
}
