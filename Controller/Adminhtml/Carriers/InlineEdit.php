<?php

/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Carriers;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use WillSco\EasyShippingRulesM2\Model\CarriersFactory;
use WillSco\EasyShippingRulesM2\Helper\Data as Helper;

class InlineEdit extends Action
{
    /**
     * @var bool|PageFactory
     */
    protected $resultPageFactory = false;
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;
    /**
     * @var Helper
     */
    protected $helper;
    /**
     * @var CarriersFactory
     */
    protected $CarriersFactory;
    /**
     * @var TimezoneInterface
     */
    protected $timeZoneInterface;

    const ADMIN_RESOURCE = 'ImaginationMedia_Correios::correios_menuoption1';

    /**
     * InlineEdit constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $jsonFactory
     * @param Helper $data
     * @param CarriersFactory $CarriersFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $jsonFactory,
        Helper $data,
        CarriersFactory $CarriersFactory,
        TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->helper = $data;
        $this->CarriersFactory = $CarriersFactory;
        $this->timeZoneInterface = $timezone;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }
        $cotacaoModel = $this->CarriersFactory->create();

        foreach ($postItems as $item) {
            $objCarrier = $cotacaoModel->load($item["id"]);
            if ($objCarrier->getData()) {
                $objCarrier->setName($item["name"]);
                $objCarrier->setStatus($item["status"]);
                $objCarrier->setPosition($item["position"]);

                if ((bool)$objCarrier->save() === false) {
                    $messages[] = __("The Carrier %s wasn't updated. Check the logs.", $objCarrier->getId());
                    $error = true;
                }
            }
        }
        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
