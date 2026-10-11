<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Deletes the records selected in a listing.
 */
abstract class AbstractMassDelete extends Action implements HttpPostActionInterface
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter
    ) {
        parent::__construct($context);
    }

    /**
     * Create collection.
     *
     * @return AbstractDb
     */
    abstract protected function createCollection(): AbstractDb;

    /**
     * Delete item.
     *
     * @param AbstractModel $item
     * @return void
     */
    abstract protected function deleteItem(AbstractModel $item): void;

    /**
     * Execute the action.
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $deleted = 0;
            foreach ($this->filter->getCollection($this->createCollection()) as $item) {
                $this->deleteItem($item);
                $deleted++;
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $deleted));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
