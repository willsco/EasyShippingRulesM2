<?php

/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Carriers;

use Magento\Framework\Message\ManagerInterface;
use WillSco\EasyShippingRulesM2\Helper\Data;

class CarrierDataProcessor
{

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;
    private Data $helper;

    public function __construct(
        ManagerInterface $messageManager,
        Data             $helper
    ) {
        $this->messageManager = $messageManager;
        $this->helper = $helper;
    }

    /**
     * @param $model
     * @return bool
     */
    public function validate($model): bool
    {
        $errorNo = true;
        if (!$model->getName() && is_string($model->getName())) {
            $this->messageManager->addErrorMessage(__("The field 'Carrier Name' can't be empty!"));
            $errorNo = false;
        }
        if (!is_numeric($model->getStatus())) {
            $this->messageManager->addErrorMessage(__("The field 'Status' can't be empty!"));
            $errorNo = false;
        }
        if (!is_numeric($model->getPosition())) {
            $this->messageManager->addErrorMessage(__("The field 'Position' can't be empty!"));
            $errorNo = false;
        }

        if ($this->helper->canCreateCarrier(
            $model->getName()
        ) == false) {
            $this->messageManager->addErrorMessage(
                __("Your DB have a carrier to this carrier.
                You can't create more than one carrier for an one service and same carriers.")
            );
            $errorNo = false;
        }
        return $errorNo;
    }
}
