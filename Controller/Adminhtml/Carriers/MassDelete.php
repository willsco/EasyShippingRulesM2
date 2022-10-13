<?php

/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Carriers;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use WillSco\EasyShippingRulesM2\Model\CarriersRepository;

class MassDelete extends Action
{
    /**
     * @var Filter
     */
    protected Filter $filter;
    /**
     * @var CarriersRepository
     */
    protected CarriersRepository $carrier;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CarriersRepository $carrier
     */
    public function __construct(Context $context, Filter $filter, CarriersRepository $carrier)
    {
        $this->filter = $filter;
        $this->carrier = $carrier;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->carrier->getCollection());

        $success = 0;
        $error = 0;

        foreach ($collection as $carrier) {
            $data = (array)$carrier->getData();
            $id = $data["id"];
            $carrierObj = $this->carrier->getById($id);
            if ($carrierObj->delete()) {
                $success++;
            } else {
                $error++;
            }
        }
        if ($error == 0) {
            if ($success > 1) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 carriers have been deleted.', $success)
                );
            } else {
                $this->messageManager->addSuccessMessage(__('The carrier has been deleted.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Impossible to delete %1 carriers.', $error));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
