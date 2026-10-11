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
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\FormDataTrait;

/**
 * Saves rows edited directly in the carrier grid.
 */
class InlineEdit extends Action implements HttpPostActionInterface
{
    use FormDataTrait;

    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::carrier';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CarrierRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CarrierRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return Json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $items = (array)$this->getRequest()->getParam('items', []);

        if (!$this->getRequest()->getParam('isAjax') || !$items) {
            return $resultJson->setData(['messages' => [__('Please correct the data sent.')], 'error' => true]);
        }

        $messages = [];
        foreach ($items as $carrierId => $item) {
            try {
                $carrier = $this->repository->getById((int)$carrierId);
                if (array_key_exists(CarrierInterface::NAME, $item)) {
                    $carrier->setName($this->toRequiredString($item[CarrierInterface::NAME], 'Name'));
                }
                if (array_key_exists(CarrierInterface::IS_ACTIVE, $item)) {
                    $carrier->setIsActive((bool)$item[CarrierInterface::IS_ACTIVE]);
                }
                if (array_key_exists(CarrierInterface::SORT_ORDER, $item)) {
                    $carrier->setSortOrder((int)$item[CarrierInterface::SORT_ORDER]);
                }
                $this->repository->save($carrier);
            } catch (\Exception $e) {
                $messages[] = __('[Carrier ID: %1] %2', $carrierId, $e->getMessage());
            }
        }

        return $resultJson->setData(['messages' => $messages, 'error' => (bool)$messages]);
    }
}
