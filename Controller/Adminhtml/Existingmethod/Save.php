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
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterfaceFactory;
use WillSco\EasyShippingRulesM2\Api\ExistingMethodRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\RuleRepositoryInterface;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\FormDataTrait;
use WillSco\EasyShippingRulesM2\Model\Source\ActionType;
use WillSco\EasyShippingRulesM2\Model\Source\CalculationType;
use WillSco\EasyShippingRulesM2\Model\Source\ShippingMethods;

class Save extends Action implements HttpPostActionInterface
{
    use FormDataTrait;

    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::existing_method';
    public const PERSISTOR_KEY = 'willsco_esr_existing_method';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param ExistingMethodRepositoryInterface $repository
     * @param ExistingMethodInterfaceFactory $methodFactory
     * @param DataPersistorInterface $dataPersistor
     * @param ActionType $actionTypeSource
     * @param CalculationType $calculationTypeSource configured with rate percentage in di.xml
     * @param RuleRepositoryInterface $ruleRepository
     * @param ShippingMethods $shippingMethodsSource
     */
    public function __construct(
        Context $context,
        private readonly ExistingMethodRepositoryInterface $repository,
        private readonly ExistingMethodInterfaceFactory $methodFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ActionType $actionTypeSource,
        private readonly CalculationType $calculationTypeSource,
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly ShippingMethods $shippingMethodsSource
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

        $id = (int)($data[ExistingMethodInterface::METHOD_ID] ?? 0);
        try {
            $method = $id ? $this->repository->getById($id) : $this->methodFactory->create();

            $shippingMethods = array_values(array_filter(
                array_map('strval', (array)($data[ExistingMethodInterface::SHIPPING_METHODS] ?? []))
            ));
            if (!$shippingMethods) {
                throw new LocalizedException(__('"%1" is required.', __('Shipping Methods')));
            }
            $unavailable = $this->shippingMethodsSource->getUnavailableCodes($shippingMethods);
            if ($unavailable) {
                throw new LocalizedException(__(
                    'No active carrier offers these shipping methods: %1.',
                    implode(', ', $unavailable)
                ));
            }

            $method->setName($this->toRequiredString($data[ExistingMethodInterface::NAME] ?? '', 'Name'))
                ->setCarrierTitle(trim((string)($data[ExistingMethodInterface::CARRIER_TITLE] ?? '')) ?: null)
                ->setMethodTitle(trim((string)($data[ExistingMethodInterface::METHOD_TITLE] ?? '')) ?: null)
                ->setIsActive((bool)($data[ExistingMethodInterface::IS_ACTIVE] ?? true))
                ->setActionType($this->toOption(
                    $data[ExistingMethodInterface::ACTION_TYPE] ?? '',
                    $this->actionTypeSource,
                    'Action'
                ))
                ->setCalculationType($this->toOption(
                    $data[ExistingMethodInterface::CALCULATION_TYPE] ?? '',
                    $this->calculationTypeSource,
                    'Calculation Type'
                ))
                ->setAmount($this->toNullableFloat($data[ExistingMethodInterface::AMOUNT] ?? null))
                ->setSortOrder((int)($data[ExistingMethodInterface::SORT_ORDER] ?? 0))
                ->setStopProcessing((bool)($data[ExistingMethodInterface::STOP_PROCESSING] ?? false))
                ->setShippingMethods($shippingMethods)
                ->setStoreIds($this->toStoreIds($data[ExistingMethodInterface::STORE_IDS] ?? []))
                ->setRuleIds($this->toIdList($data[ExistingMethodInterface::RULE_IDS] ?? []));
            $this->validateOverwriteAmount($method);

            $this->repository->save($method);
            $this->messageManager->addSuccessMessage(__('The existing method has been saved.'));
            $this->dataPersistor->clear(self::PERSISTOR_KEY);

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['id' => $method->getId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This existing method no longer exists.'));
            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while saving the existing method.')
            );
        }

        $this->dataPersistor->set(self::PERSISTOR_KEY, $data);

        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }

    /**
     * "Overwrite price" needs a value whichever rule wins, or the rate would become free.
     *
     * @param ExistingMethodInterface $method
     * @return void
     * @throws LocalizedException
     */
    private function validateOverwriteAmount(ExistingMethodInterface $method): void
    {
        if ($method->getActionType() !== ExistingMethodInterface::ACTION_OVERWRITE || $method->getAmount() !== null) {
            return;
        }

        foreach ($method->getRuleIds() as $ruleId) {
            try {
                $rule = $this->ruleRepository->getById($ruleId);
            } catch (NoSuchEntityException $e) {
                continue;
            }
            if ($rule->getAmount() === null) {
                throw new LocalizedException(__(
                    'Set a "%1" for "%2": rule #%3 "%4" has no value.',
                    __('Price / Percentage'),
                    __('Overwrite price'),
                    $ruleId,
                    $rule->getName()
                ));
            }
        }
    }
}
