<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod as CustomMethodResource;
use WillSco\EasyShippingRulesM2\Model\Source\Carrier as CarrierSource;

/**
 * Names of the carriers of each custom method in the grid.
 */
class Carriers extends Column
{
    /**
     * Initialize dependencies.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CustomMethodResource $methodResource
     * @param CarrierSource $carrierSource
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly CustomMethodResource $methodResource,
        private readonly CarrierSource $carrierSource,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $names = [];
        foreach ($this->carrierSource->toOptionArray() as $option) {
            $names[(int)$option['value']] = (string)$option['label'];
        }

        $methodIds = array_map('intval', array_column($dataSource['data']['items'], 'method_id'));
        $links = $this->methodResource->lookupAllLinks($methodIds)[CustomMethodInterface::CARRIER_IDS] ?? [];

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $carrierIds = $links[(int)$item['method_id']] ?? [];
            $item[$name] = implode(', ', array_map(static fn (int $id) => $names[$id] ?? '#' . $id, $carrierIds));
        }

        return $dataSource;
    }
}
