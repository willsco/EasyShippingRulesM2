<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Ratetable;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Model\RateTable\Importer;

/**
 * Import an uploaded rate table spreadsheet.
 */
class Import extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = Index::ADMIN_RESOURCE;

    /**
     * Larger files are refused before parsing (about 100,000 lines).
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Importer $importer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly Importer $importer,
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

            $summary = $this->importer->import((string)file_get_contents((string)$file['tmp_name']));
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
