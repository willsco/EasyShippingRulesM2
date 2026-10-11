<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml\Ratetable;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use WillSco\EasyShippingRulesM2\Model\RateTable\CsvFormat;
use WillSco\EasyShippingRulesM2\Model\RateTable\Exporter;

/**
 * Download the rate tables as CSV.
 */
class Export extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = Index::ADMIN_RESOURCE;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Exporter $exporter
     */
    public function __construct(
        Context $context,
        private readonly FileFactory $fileFactory,
        private readonly Exporter $exporter
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the action.
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $format = $this->getRequest()->getParam('format') === CsvFormat::FORMAT_EXCEL_COMMA
            ? CsvFormat::FORMAT_EXCEL_COMMA
            : CsvFormat::FORMAT_STANDARD;

        return $this->fileFactory->create(
            'shipping-rate-tables.csv',
            $this->exporter->export($format),
            DirectoryList::VAR_DIR,
            'text/csv'
        );
    }
}
