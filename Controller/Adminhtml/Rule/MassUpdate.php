<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Ui\Component\MassAction\Filter;
use WillSco\EasyShippingRulesM2\Api\RuleRepositoryInterface;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\AbstractMassUpdate;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory;

class MassUpdate extends AbstractMassUpdate
{
    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::rule';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param RuleRepositoryInterface $repository
     * @param array $allowedFields
     */
    public function __construct(
        Context $context,
        Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly RuleRepositoryInterface $repository,
        array $allowedFields = []
    ) {
        parent::__construct($context, $filter, $allowedFields);
    }

    /**
     * @inheritdoc
     */
    protected function createCollection(): AbstractDb
    {
        return $this->collectionFactory->create();
    }

    /**
     * @inheritdoc
     */
    protected function saveItem(AbstractModel $item): void
    {
        /** @var \WillSco\EasyShippingRulesM2\Api\Data\RuleInterface $item */
        $this->repository->save($item);
    }
}
