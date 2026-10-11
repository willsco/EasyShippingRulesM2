<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Custommethod;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Ui\Component\MassAction\Filter;
use WillSco\EasyShippingRulesM2\Api\CustomMethodRepositoryInterface;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\AbstractMassDelete;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod\CollectionFactory;

class MassDelete extends AbstractMassDelete
{
    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::custom_method';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CustomMethodRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly CustomMethodRepositoryInterface $repository
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
        /** @var \WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface $item */
        $this->repository->delete($item);
    }
}
