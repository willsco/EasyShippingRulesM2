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
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierSearchResultsInterface;

interface CarrierRepositoryInterface
{
    /**
     * Save.
     *
     * @param CarrierInterface $carrier
     * @return CarrierInterface
     * @throws CouldNotSaveException
     */
    public function save(CarrierInterface $carrier): CarrierInterface;

    /**
     * Get by ID.
     *
     * @param int $carrierId
     * @return CarrierInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $carrierId): CarrierInterface;

    /**
     * Get list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CarrierSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CarrierSearchResultsInterface;

    /**
     * Delete.
     *
     * @param CarrierInterface $carrier
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(CarrierInterface $carrier): bool;

    /**
     * Delete by ID.
     *
     * @param int $carrierId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $carrierId): bool;
}
