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
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface;
use WillSco\EasyShippingRulesM2\Api\CustomMethodRepositoryInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod as CustomMethodResource;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod\CollectionFactory;

class CustomMethodRepository implements CustomMethodRepositoryInterface
{
    /**
     * @var array<int, CustomMethodInterface>
     */
    private array $instances = [];

    /**
     * Initialize dependencies.
     *
     * @param CustomMethodResource $resource
     * @param CustomMethodFactory $methodFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CustomMethodResource $resource,
        private readonly CustomMethodFactory $methodFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(CustomMethodInterface $method): CustomMethodInterface
    {
        try {
            /** @var CustomMethod $method */
            $this->resource->save($method);
        } catch (\Exception $e) {
            // The original message may expose database details: log it, show a generic one.
            $this->logger->error($e);
            throw new CouldNotSaveException(__('Could not save the custom method.'), $e);
        }
        unset($this->instances[(int)$method->getId()]);

        return $method;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $methodId): CustomMethodInterface
    {
        if (!isset($this->instances[$methodId])) {
            $method = $this->methodFactory->create();
            $this->resource->load($method, $methodId);
            if (!$method->getId()) {
                throw new NoSuchEntityException(__('The custom method with ID "%1" does not exist.', $methodId));
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
    public function delete(CustomMethodInterface $method): bool
    {
        try {
            /** @var CustomMethod $method */
            $this->resource->delete($method);
        } catch (\Exception $e) {
            // The original message may expose database details: log it, show a generic one.
            $this->logger->error($e);
            throw new CouldNotDeleteException(__('Could not delete the custom method.'), $e);
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
