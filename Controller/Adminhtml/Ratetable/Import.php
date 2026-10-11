<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Ratetable;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Model\RateTable\Importer;
use WillSco\EasyShippingRulesM2\Model\RateTable\UploadStorage;

/**
 * Import the spreadsheet confirmed on the preview, or discard it.
 */
class Import extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = Index::ADMIN_RESOURCE;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Importer $importer
     * @param UploadStorage $uploadStorage
     * @param Session $session
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly Importer $importer,
        private readonly UploadStorage $uploadStorage,
        private readonly Session $session,
        private readonly LoggerInterface $logger
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
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $token = (string)$this->getRequest()->getParam('token');

        // Only the file previewed last in this admin session can be imported.
        $contents = $token !== '' && $token === (string)$this->session->getData(Preview::SESSION_TOKEN)
            ? $this->uploadStorage->load($token)
            : null;
        $this->session->unsetData(Preview::SESSION_TOKEN);
        $this->uploadStorage->delete($token);

        if ($this->getRequest()->getParam('cancel')) {
            $this->messageManager->addNoticeMessage(__('The import was canceled. Nothing was changed.'));
            return $redirect;
        }
        if ($contents === null) {
            $this->messageManager->addErrorMessage(__('This preview has expired. Upload the file again.'));
            return $redirect;
        }

        try {
            $summary = $this->importer->import($contents);
            $this->messageManager->addSuccessMessage(__(
                'Imported %1 rate line(s) into %2 method(s).',
                $summary['rows'],
                $summary['methods']
            ));
            if ($summary['created_methods']) {
                $this->messageManager->addNoticeMessage(__(
                    'New custom methods (enabled, all store views, no rules): %1',
                    implode(', ', $summary['created_methods'])
                ));
            }
            if ($summary['created_carriers']) {
                $this->messageManager->addNoticeMessage(__(
                    'New carriers: %1',
                    implode(', ', $summary['created_carriers'])
                ));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->messageManager->addErrorMessage(__('The import failed. See the exception log for details.'));
        }

        return $redirect;
    }
}
