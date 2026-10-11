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
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;
use WillSco\EasyShippingRulesM2\Api\ExistingMethodRepositoryInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\ExistingMethod as ExistingMethodResource;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\ExistingMethod\CollectionFactory;

class ExistingMethodRepository implements ExistingMethodRepositoryInterface
{
    /**
     * @var array<int, ExistingMethodInterface>
     */
    private array $instances = [];

    /**
     * Initialize dependencies.
     *
     * @param ExistingMethodResource $resource
     * @param ExistingMethodFactory $methodFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ExistingMethodResource $resource,
        private readonly ExistingMethodFactory $methodFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(ExistingMethodInterface $method): ExistingMethodInterface
    {
        try {
            /** @var ExistingMethod $method */
            $this->resource->save($method);
        } catch (\Exception $e) {
            // The original message may expose database details: log it, show a generic one.
            $this->logger->error($e);
            throw new CouldNotSaveException(__('Could not save the existing method.'), $e);
        }
        unset($this->instances[(int)$method->getId()]);

        return $method;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $methodId): ExistingMethodInterface
    {
        if (!isset($this->instances[$methodId])) {
            $method = $this->methodFactory->create();
            $this->resource->load($method, $methodId);
            if (!$method->getId()) {
                throw new NoSuchEntityException(__('The existing method with ID "%1" does not exist.', $methodId));
            }
            $this->instances[$methodId] = $method;
        }

        return $this->instances[$methodId];
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
    public function delete(ExistingMethodInterface $method): bool
    {
        try {
            /** @var ExistingMethod $method */
            $this->resource->delete($method);
        } catch (\Exception $e) {
            // The original message may expose database details: log it, show a generic one.
            $this->logger->error($e);
            throw new CouldNotDeleteException(__('Could not delete the existing method.'), $e);
        }
        unset($this->instances[(int)$method->getId()]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $methodId): bool
    {
        return $this->delete($this->getById($methodId));
    }
}
