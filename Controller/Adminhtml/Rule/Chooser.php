<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use WillSco\EasyShippingRulesM2\Block\Adminhtml\Rule\Chooser as ChooserBlock;

/**
 * Grid used by the "Shipping Rule" condition to pick rules.
 */
class Chooser extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::rule';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        private readonly RawFactory $resultRawFactory,
        private readonly LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return Raw
     */
    public function execute()
    {
        $block = $this->layoutFactory->create()->createBlock(
            ChooserBlock::class,
            'easyshippingrules_rule_chooser',
            ['data' => ['js_form_object' => $this->getRequest()->getParam('form')]]
        );

        return $this->resultRawFactory->create()->setContents($block->toHtml());
    }
}
