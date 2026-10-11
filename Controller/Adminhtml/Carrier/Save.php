<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Carrier;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterfaceFactory;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\FormDataTrait;

class Save extends Action implements HttpPostActionInterface
{
    use FormDataTrait;

    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::carrier';
    public const PERSISTOR_KEY = 'willsco_esr_carrier';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param CarrierRepositoryInterface $repository
     * @param CarrierInterfaceFactory $carrierFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        private readonly CarrierRepositoryInterface $repository,
        private readonly CarrierInterfaceFactory $carrierFactory,
        private readonly DataPersistorInterface $dataPersistor
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
        $redirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/*/');
        }

        $id = (int)($data[CarrierInterface::CARRIER_ID] ?? 0);
        try {
            $carrier = $id ? $this->repository->getById($id) : $this->carrierFactory->create();
            $carrier->setName($this->toRequiredString($data[CarrierInterface::NAME] ?? '', 'Name'))
                ->setIsActive((bool)($data[CarrierInterface::IS_ACTIVE] ?? true))
                ->setSortOrder((int)($data[CarrierInterface::SORT_ORDER] ?? 0));

            $this->repository->save($carrier);
            $this->messageManager->addSuccessMessage(__('The carrier has been saved.'));
            $this->dataPersistor->clear(self::PERSISTOR_KEY);

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['id' => $carrier->getId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This carrier no longer exists.'));
            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the carrier.'));
        }

        $this->dataPersistor->set(self::PERSISTOR_KEY, $data);

        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }
}
