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
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier as CarrierResource;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier\CollectionFactory;

class CarrierRepository implements CarrierRepositoryInterface
{
    /**
     * @var array<int, CarrierInterface>
     */
    private array $instances = [];

    /**
     * Initialize dependencies.
     *
     * @param CarrierResource $resource
     * @param CarrierFactory $carrierFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CarrierResource $resource,
        private readonly CarrierFactory $carrierFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(CarrierInterface $carrier): CarrierInterface
    {
        try {
            /** @var Carrier $carrier */
            $this->resource->save($carrier);
        } catch (AlreadyExistsException $e) {
            throw new CouldNotSaveException(
                __('A carrier named "%1" already exists.', $carrier->getName()),
                $e
            );
        } catch (\Exception $e) {
            // The original message may expose database details: log it, show a generic one.
            $this->logger->error($e);
            throw new CouldNotSaveException(__('Could not save the carrier.'), $e);
        }
        unset($this->instances[(int)$carrier->getId()]);

        return $carrier;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $carrierId): CarrierInterface
    {
        if (!isset($this->instances[$carrierId])) {
            $carrier = $this->carrierFactory->create();
            $this->resource->load($carrier, $carrierId);
            if (!$carrier->getId()) {
                throw new NoSuchEntityException(__('The carrier with ID "%1" does not exist.', $carrierId));
            }
            $this->instances[$carrierId] = $carrier;
        }

        return $this->instances[$carrierId];
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
    public function delete(CarrierInterface $carrier): bool
    {
        try {
            /** @var Carrier $carrier */
            $this->resource->delete($carrier);
        } catch (\Exception $e) {
            // The original message may expose database details: log it, show a generic one.
            $this->logger->error($e);
            throw new CouldNotDeleteException(__('Could not delete the carrier.'), $e);
        }
        unset($this->instances[(int)$carrier->getId()]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $carrierId): bool
    {
        return $this->delete($this->getById($carrierId));
    }
}
