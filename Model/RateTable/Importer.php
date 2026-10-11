<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\RateTable;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Store\Model\Store;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\CustomMethodRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterfaceFactory;
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterfaceFactory;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier\CollectionFactory as CarrierCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod\CollectionFactory as CustomMethodCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\RateTable as RateTableResource;

/**
 * Replaces the rate tables of the methods listed in a spreadsheet, all or nothing.
 *
 * Methods are found by name and created when missing (enabled, all store views, no rules);
 * their carriers become the ones listed, created when missing. Methods not in the file are kept.
 */
class Importer
{
    /**
     * Errors listed at most, so a wrong file does not flood the page.
     */
    private const MAX_ERRORS = 20;

    /**
     * Initialize dependencies.
     *
     * @param CsvParser $parser
     * @param RateTableResource $rateTableResource
     * @param CustomMethodCollectionFactory $methodCollectionFactory
     * @param CustomMethodRepositoryInterface $methodRepository
     * @param CustomMethodInterfaceFactory $methodFactory
     * @param CarrierCollectionFactory $carrierCollectionFactory
     * @param CarrierRepositoryInterface $carrierRepository
     * @param CarrierInterfaceFactory $carrierFactory
     */
    public function __construct(
        private readonly CsvParser $parser,
        private readonly RateTableResource $rateTableResource,
        private readonly CustomMethodCollectionFactory $methodCollectionFactory,
        private readonly CustomMethodRepositoryInterface $methodRepository,
        private readonly CustomMethodInterfaceFactory $methodFactory,
        private readonly CarrierCollectionFactory $carrierCollectionFactory,
        private readonly CarrierRepositoryInterface $carrierRepository,
        private readonly CarrierInterfaceFactory $carrierFactory
    ) {
    }

    /**
     * Import.
     *
     * @param string $contents
     * @return array{rows: int, methods: int, created_methods: string[], created_carriers: string[]}
     * @throws LocalizedException listing what is wrong; nothing is saved then
     */
    public function import(string $contents): array
    {
        $parsed = $this->parser->parse($contents);
        if ($parsed['errors']) {
            throw new LocalizedException($this->errorList($parsed['errors']));
        }

        $summary = ['rows' => 0, 'methods' => 0, 'created_methods' => [], 'created_carriers' => []];
        $connection = $this->rateTableResource->getConnection();
        $connection->beginTransaction();
        try {
            $carrierIds = $this->resolveCarriers($parsed['methods'], $summary['created_carriers']);
            foreach ($parsed['methods'] as $name => $method) {
                $methodId = $this->saveMethod(
                    (string)$name,
                    array_map(static fn (string $carrier) => $carrierIds[$carrier], $method['carriers']),
                    $summary['created_methods']
                );
                $this->rateTableResource->replaceRows($methodId, $method['rows']);
                $summary['rows'] += count($method['rows']);
                $summary['methods']++;
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e instanceof LocalizedException
                ? $e
                : new LocalizedException(__('The rate table could not be imported: %1', $e->getMessage()), $e);
        }

        return $summary;
    }

    /**
     * Carrier IDs by name, creating the missing carriers.
     *
     * @param array $methods parsed methods
     * @param string[] $created names of created carriers
     * @return array<string, int>
     */
    private function resolveCarriers(array $methods, array &$created): array
    {
        $ids = [];
        foreach ($this->carrierCollectionFactory->create() as $carrier) {
            $ids[mb_strtolower((string)$carrier->getName())] = (int)$carrier->getId();
        }

        $result = [];
        foreach ($methods as $method) {
            foreach ($method['carriers'] as $name) {
                $key = mb_strtolower($name);
                if (!isset($ids[$key])) {
                    $carrier = $this->carrierFactory->create()->setName($name)->setIsActive(true);
                    $ids[$key] = (int)$this->carrierRepository->save($carrier)->getId();
                    $created[] = $name;
                }
                $result[$name] = $ids[$key];
            }
        }

        return $result;
    }

    /**
     * Save the method with the given carriers, creating it when missing; returns its ID.
     *
     * @param string $name
     * @param int[] $carrierIds
     * @param string[] $created names of created methods
     * @return int
     * @throws LocalizedException
     */
    private function saveMethod(string $name, array $carrierIds, array &$created): int
    {
        $matches = $this->methodCollectionFactory->create()
            ->addFieldToFilter('name', $name)
            ->getItems();
        if (count($matches) > 1) {
            throw new LocalizedException(__(
                'There are %1 custom methods named "%2". Rename them so the file points to only one.',
                count($matches),
                $name
            ));
        }

        /** @var CustomMethodInterface|null $method */
        $method = $matches ? reset($matches) : null;
        if ($method === null) {
            $method = $this->methodFactory->create()
                ->setName($name)
                ->setIsActive(true)
                ->setCalculationType(MethodInterface::CALCULATION_RATE_TABLE)
                ->setStoreIds([Store::DEFAULT_STORE_ID])
                ->setRuleIds([]);
            $created[] = $name;
        } elseif ($method->getCalculationType() !== MethodInterface::CALCULATION_RATE_TABLE) {
            throw new LocalizedException(__(
                'The custom method "%1" does not use the "Rate table" calculation type. '
                . 'Change it in Custom Methods, or use another name in the file.',
                $name
            ));
        }

        $method->setCarrierIds($carrierIds);

        return (int)$this->methodRepository->save($method)->getId();
    }

    /**
     * One message listing the errors.
     *
     * @param Phrase[] $errors
     * @return Phrase
     */
    private function errorList(array $errors): Phrase
    {
        $shown = array_map('strval', array_slice($errors, 0, self::MAX_ERRORS));
        if (count($errors) > self::MAX_ERRORS) {
            $shown[] = (string)__('... and %1 more.', count($errors) - self::MAX_ERRORS);
        }

        return __('Nothing was imported. Fix the file and try again: %1', implode(' ', $shown));
    }
}
