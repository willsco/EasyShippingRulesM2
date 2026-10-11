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
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodSearchResultsInterface;

interface ExistingMethodRepositoryInterface
{
    /**
     * Save.
     *
     * @param ExistingMethodInterface $method
     * @return ExistingMethodInterface
     * @throws CouldNotSaveException
     */
    public function save(ExistingMethodInterface $method): ExistingMethodInterface;

    /**
     * Get by ID.
     *
     * @param int $methodId
     * @return ExistingMethodInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $methodId): ExistingMethodInterface;

    /**
     * Get list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return ExistingMethodSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ExistingMethodSearchResultsInterface;

    /**
     * Delete.
     *
     * @param ExistingMethodInterface $method
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ExistingMethodInterface $method): bool;

    /**
     * Delete by ID.
     *
     * @param int $methodId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $methodId): bool;
}
