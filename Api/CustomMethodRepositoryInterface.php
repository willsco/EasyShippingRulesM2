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
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodSearchResultsInterface;

interface CustomMethodRepositoryInterface
{
    /**
     * Save.
     *
     * @param CustomMethodInterface $method
     * @return CustomMethodInterface
     * @throws CouldNotSaveException
     */
    public function save(CustomMethodInterface $method): CustomMethodInterface;

    /**
     * Get by ID.
     *
     * @param int $methodId
     * @return CustomMethodInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $methodId): CustomMethodInterface;

    /**
     * Get list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CustomMethodSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CustomMethodSearchResultsInterface;

    /**
     * Delete.
     *
     * @param CustomMethodInterface $method
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(CustomMethodInterface $method): bool;

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
