<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\RateTable;

use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier\CollectionFactory as CarrierCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod\CollectionFactory as CustomMethodCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\RateTable as RateTableResource;

/**
 * Writes the rate tables of all "rate table" methods as a spreadsheet the importer reads back.
 */
class Exporter
{
    /**
     * Initialize dependencies.
     *
     * @param CsvFormat $format
     * @param RateTableResource $rateTableResource
     * @param CustomMethodCollectionFactory $methodCollectionFactory
     * @param CarrierCollectionFactory $carrierCollectionFactory
     */
    public function __construct(
        private readonly CsvFormat $format,
        private readonly RateTableResource $rateTableResource,
        private readonly CustomMethodCollectionFactory $methodCollectionFactory,
        private readonly CarrierCollectionFactory $carrierCollectionFactory
    ) {
    }

    /**
     * CSV contents with a UTF-8 BOM, so Excel shows accents correctly.
     *
     * A file without methods still has the header, as a template to fill in.
     *
     * @param string $format CsvFormat::FORMAT_*
     * @return string
     */
    public function export(string $format): string
    {
        [$delimiter, $decimal] = $this->format->getSeparators($format);

        $carrierNames = [];
        foreach ($this->carrierCollectionFactory->create() as $carrier) {
            $carrierNames[(int)$carrier->getId()] = (string)$carrier->getName();
        }

        /** @var CustomMethodInterface[] $methods */
        $methods = $this->methodCollectionFactory->create()
            ->addFieldToFilter('calculation_type', MethodInterface::CALCULATION_RATE_TABLE)
            ->setOrder('name', 'ASC')
            ->getItems();

        $rowsByMethod = [];
        foreach ($this->rateTableResource->getRows(array_keys($methods)) as $row) {
            $rowsByMethod[(int)$row['method_id']][] = $row;
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_keys(CsvFormat::COLUMNS), $delimiter, '"', '');
        foreach ($methods as $methodId => $method) {
            $carriers = array_filter(array_map(
                static fn (int $id) => $carrierNames[$id] ?? null,
                $method->getCarrierIds()
            ));
            sort($carriers);
            foreach ($rowsByMethod[(int)$methodId] ?? [] as $row) {
                fputcsv($handle, [
                    implode(' ' . CsvFormat::CARRIER_SEPARATOR . ' ', $carriers),
                    (string)$method->getName(),
                    (string)$row['dest_country_id'],
                    (string)$row['postcode_from'],
                    (string)$row['postcode_to'],
                    $this->format->formatNumber($row['weight_from'], $decimal),
                    $this->format->formatNumber($row['weight_to'], $decimal),
                    $this->format->formatNumber($row['subtotal_from'], $decimal),
                    $this->format->formatNumber($row['subtotal_to'], $decimal),
                    $this->format->formatNumber($row['price'], $decimal),
                ], $delimiter, '"', '');
            }
        }

        rewind($handle);
        $contents = (string)stream_get_contents($handle);
        fclose($handle);

        return $contents;
    }
}
