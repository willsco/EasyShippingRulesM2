<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Carrier;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Ui\Component\MassAction\Filter;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\AbstractMassDelete;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier\CollectionFactory;

class MassDelete extends AbstractMassDelete
{
    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::carrier';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CarrierRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly CarrierRepositoryInterface $repository
    ) {
        parent::__construct($context, $filter);
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
    protected function deleteItem(AbstractModel $item): void
    {
        /** @var \WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface $item */
        $this->repository->delete($item);
    }
}
