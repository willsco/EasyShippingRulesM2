<?php

/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Carriers;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

use WillSco\EasyShippingRulesM2\Model\CarriersFactory;

class Save extends Action
{

    protected CarrierDataProcessor $dataProcessor;
    /**
     * @var DataPersistorInterface
     */
    protected DataPersistorInterface $dataPersistor;

    /**
     * @var CarriersFactory
     */
    protected CarriersFactory $carriersFactory;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param CarrierDataProcessor $dataProcessor
     * @param DataPersistorInterface $dataPersistor
     * @param CarriersFactory $carrier
     */
    public function __construct(
        Action\Context         $context,
        CarrierDataProcessor   $dataProcessor,
        DataPersistorInterface $dataPersistor,
        CarriersFactory        $carrier
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->dataPersistor = $dataPersistor;
        $this->carriersFactory = $carrier;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->carriersFactory->create();
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
            }

            $model->setName($data["name"]);
            $model->setStatus($data["status"]);
            $model->setPosition($data["position"]);

            if (!$this->dataProcessor->validate($model)) {
                $this->messageManager->addErrorMessage(__('Invalid carrier data.'));
                $this->dataPersistor->clear('willsco_esrm2_carriers');
                return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
            }

            try {
                if ($model->save()) {
                    $this->messageManager->addSuccessMessage(__('You saved the new carrier.'));
                    $this->dataPersistor->clear('willsco_esrm2_carriers');
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath(
                            '*/*/edit',
                            ['id' => $model->getId(), '_current' => true]
                        );
                    }
                } else {
                    $this->messageManager->addErrorMessage(__('Was not possible to save this new carrier.'));
                    $this->dataPersistor->clear('willsco_esrm2_carriers');
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while saving the new carrier.')
                );
            }

            $this->dataPersistor->set('willsco_esrm2_carriers', $data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['id' => $this->getRequest()->getParam('id')]
            );
        }
        return $resultRedirect->setPath('*/*/');
    }
}
