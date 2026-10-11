<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Carrier;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::carrier';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CarrierRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly CarrierRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $title = __('New Carrier');

        if ($id) {
            try {
                $title = __('Edit Carrier "%1"', $this->repository->getById($id)->getName());
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This carrier no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('WillSco_EasyShippingRulesM2::carrier');
        $resultPage->getConfig()->getTitle()->prepend(__('Carriers'));
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
