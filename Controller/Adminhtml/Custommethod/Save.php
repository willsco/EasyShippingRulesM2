<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Custommethod;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use WillSco\EasyShippingRulesM2\Api\CustomMethodRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterfaceFactory;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\FormDataTrait;
use WillSco\EasyShippingRulesM2\Model\Source\CalculationType;

class Save extends Action implements HttpPostActionInterface
{
    use FormDataTrait;

    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::custom_method';
    public const PERSISTOR_KEY = 'willsco_esr_custom_method';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param CustomMethodRepositoryInterface $repository
     * @param CustomMethodInterfaceFactory $methodFactory
     * @param DataPersistorInterface $dataPersistor
     * @param CalculationType $calculationTypeSource
     */
    public function __construct(
        Context $context,
        private readonly CustomMethodRepositoryInterface $repository,
        private readonly CustomMethodInterfaceFactory $methodFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly CalculationType $calculationTypeSource
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

        $id = (int)($data[CustomMethodInterface::METHOD_ID] ?? 0);
        try {
            $method = $id ? $this->repository->getById($id) : $this->methodFactory->create();
            $carrierId = (int)($data[CustomMethodInterface::CARRIER_ID] ?? 0);
            if (!$carrierId) {
                throw new LocalizedException(__('"%1" is required.', __('Carrier')));
            }

            $method->setCarrierId($carrierId)
                ->setName($this->toRequiredString($data[CustomMethodInterface::NAME] ?? '', 'Name'))
                ->setIsActive((bool)($data[CustomMethodInterface::IS_ACTIVE] ?? true))
                ->setCalculationType($this->toOption(
                    $data[CustomMethodInterface::CALCULATION_TYPE] ?? '',
                    $this->calculationTypeSource,
                    'Calculation Type'
                ))
                ->setAmount($this->toNullableFloat($data[CustomMethodInterface::AMOUNT] ?? null))
                ->setStoreIds($this->toStoreIds($data[CustomMethodInterface::STORE_IDS] ?? []))
                ->setRuleIds($this->toIdList($data[CustomMethodInterface::RULE_IDS] ?? []));

            $this->repository->save($method);
            $this->messageManager->addSuccessMessage(__('The custom method has been saved.'));
            $this->dataPersistor->clear(self::PERSISTOR_KEY);

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['id' => $method->getId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This custom method no longer exists.'));
            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the custom method.'));
        }

        $this->dataPersistor->set(self::PERSISTOR_KEY, $data);

        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }
}
