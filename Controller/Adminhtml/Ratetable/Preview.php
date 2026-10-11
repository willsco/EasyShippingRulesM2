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
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Model\RateTable\Importer;
use WillSco\EasyShippingRulesM2\Model\RateTable\UploadStorage;

/**
 * Check an uploaded spreadsheet and show what importing it would change; nothing is saved yet.
 */
class Preview extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = Index::ADMIN_RESOURCE;

    /**
     * Session key of the token of the file awaiting confirmation.
     */
    public const SESSION_TOKEN = 'willsco_esr_rate_table_token';

    /**
     * Larger files are refused before parsing (about 100,000 lines).
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Importer $importer
     * @param UploadStorage $uploadStorage
     * @param Session $session
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
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
     * @return Page|Redirect
     */
    public function execute()
    {
        $file = $this->getRequest()->getFiles('rate_table_file');

        try {
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || !is_uploaded_file((string)$file['tmp_name'])
            ) {
                throw new LocalizedException(__('Choose a CSV file to import.'));
            }
            if ((int)$file['size'] > self::MAX_FILE_SIZE) {
                throw new LocalizedException(__('The file is larger than 10 MB.'));
            }

            $contents = (string)file_get_contents((string)$file['tmp_name']);
            $preview = $this->importer->preview($contents);
            $token = $this->uploadStorage->save($contents);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->messageManager->addErrorMessage(__('The import failed. See the exception log for details.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $previous = (string)$this->session->getData(self::SESSION_TOKEN);
        if ($previous !== '') {
            $this->uploadStorage->delete($previous);
        }
        $this->session->setData(self::SESSION_TOKEN, $token);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(Index::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Rate Tables'));
        $resultPage->getLayout()->getBlock('easyshippingrules.rate_table')
            ->setData('preview', $preview)
            ->setData('token', $token)
            ->setData('file_name', (string)($file['name'] ?? ''));

        return $resultPage;
    }
}
