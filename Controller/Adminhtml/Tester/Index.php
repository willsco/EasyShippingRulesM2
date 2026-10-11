<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Tester;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Model\Tester\InputParser;
use WillSco\EasyShippingRulesM2\Model\Tester\Simulator;

/**
 * Rule tester: shows the form and, when posted, the simulated rates.
 */
class Index extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'WillSco_EasyShippingRulesM2::tester';

    /**
     * Looking a customer up by email tells whether the email is registered: it needs access to customers.
     */
    public const CUSTOMER_RESOURCE = 'Magento_Customer::manage';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param InputParser $inputParser
     * @param Simulator $simulator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly InputParser $inputParser,
        private readonly Simulator $simulator,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Rule Tester'));

        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $resultPage;
        }
        if (!$this->_authorization->isAllowed(self::CUSTOMER_RESOURCE)) {
            unset($data['customer_email']);
        }

        // Errors are shown by the block: the page messages are already collected at this point.
        $block = $resultPage->getLayout()->getBlock('easyshippingrules.tester');
        $block->setData('input', $data);
        try {
            $block->setData('result', $this->simulator->simulate($this->inputParser->parse($data)));
        } catch (LocalizedException $e) {
            $block->setData('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error($e);
            $block->setData('error', __('The simulation failed. See the exception log for details.')->render());
        }

        return $resultPage;
    }
}
