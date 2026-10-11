<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Existingmethod;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use WillSco\EasyShippingRulesM2\Api\ExistingMethodRepositoryInterface;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::existing_method';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param ExistingMethodRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly ExistingMethodRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return Redirect
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $id = (int)$this->getRequest()->getParam('id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find an existing method to delete.'));
            return $redirect;
        }

        try {
            $this->repository->deleteById($id);
            $this->messageManager->addSuccessMessage(__('The existing method has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['id' => $id]);
        }

        return $redirect;
    }
}
