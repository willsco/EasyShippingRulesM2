<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\Phrase;
use WillSco\EasyShippingRulesM2\Model\RateTable\CsvFormat;

/**
 * Export and import forms of the rate tables page.
 */
class RateTable extends Template
{
    /**
     * @var string
     */
    protected $_template = 'WillSco_EasyShippingRulesM2::rate_table.phtml';

    /**
     * Export formats as value => label.
     *
     * @return array<string, Phrase>
     */
    public function getFormats(): array
    {
        return [
            CsvFormat::FORMAT_EXCEL_COMMA => __('Excel in Portuguese (";" and decimal comma)'),
            CsvFormat::FORMAT_STANDARD => __('Google Sheets / Excel in English ("," and decimal point)'),
        ];
    }

    /**
     * Columns of the spreadsheet with their descriptions.
     *
     * @return array<string, Phrase>
     */
    public function getColumnHelp(): array
    {
        return [
            'carrier' => __('Carrier name. Several carriers: separate them with "|". Created when missing.'),
            'method' => __('Custom method name. Created when missing, with the "Rate table" calculation type.'),
            'country' => __('Two-letter country code, e.g. BR. Empty: any country.'),
            'postcode_from' => __('Lowest postcode. Shorter values work as prefixes ("01000" covers 01000-000).'),
            'postcode_to' => __('Highest postcode. Empty: no limit.'),
            'weight_from' => __('Lowest cart weight. Empty: no limit.'),
            'weight_to' => __('Highest cart weight. Empty: no limit.'),
            'subtotal_from' => __('Lowest subtotal after discounts. Empty: no limit.'),
            'subtotal_to' => __('Highest subtotal after discounts. Empty: no limit.'),
            'price' => __('Shipping price. 0 means free.'),
        ];
    }

    /**
     * Get export URL.
     *
     * @return string
     */
    public function getExportUrl(): string
    {
        return $this->getUrl('easyshippingrules/ratetable/export');
    }

    /**
     * Get import URL.
     *
     * @return string
     */
    public function getImportUrl(): string
    {
        return $this->getUrl('easyshippingrules/ratetable/import');
    }
}
