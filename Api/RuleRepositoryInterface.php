<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use WillSco\EasyShippingRulesM2\Api\Data\RuleInterface;
use WillSco\EasyShippingRulesM2\Api\Data\RuleSearchResultsInterface;

interface RuleRepositoryInterface
{
    /**
     * Save.
     *
     * @param RuleInterface $rule
     * @return RuleInterface
     * @throws CouldNotSaveException
     */
    public function save(RuleInterface $rule): RuleInterface;

    /**
     * Get by ID.
     *
     * @param int $ruleId
     * @return RuleInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $ruleId): RuleInterface;

    /**
     * Get list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return RuleSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RuleSearchResultsInterface;

    /**
     * Delete.
     *
     * @param RuleInterface $rule
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(RuleInterface $rule): bool;

    /**
     * Delete by ID.
     *
     * @param int $ruleId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $ruleId): bool;
}
