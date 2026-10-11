<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Ui\Component\Listing\Column;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use WillSco\EasyShippingRulesM2\Model\Source\ShippingMethods as ShippingMethodsSource;

/**
 * Target shipping methods by label, flagging codes no active carrier offers anymore.
 */
class ShippingMethods extends Column
{
    /**
     * Initialize dependencies.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ShippingMethodsSource $shippingMethodsSource
     * @param Json $json
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly ShippingMethodsSource $shippingMethodsSource,
        private readonly Json $json,
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
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        $labels = $this->shippingMethodsSource->getLabels();
        foreach ($dataSource['data']['items'] as &$item) {
            $codes = $this->decode($item[$name] ?? null);
            $item[$name] = implode(', ', array_map(
                static fn (string $code) => $labels[$code] ?? (string)__('%1 (unavailable)', $code),
                $codes
            ));
        }

        return $dataSource;
    }

    /**
     * Stored JSON list of codes.
     *
     * @param mixed $value
     * @return string[]
     */
    private function decode($value): array
    {
        if (is_array($value)) {
            return array_map('strval', $value);
        }

        try {
            $codes = $this->json->unserialize((string)$value);
        } catch (\InvalidArgumentException $e) {
            return [];
        }

        return is_array($codes) ? array_map('strval', $codes) : [];
    }
}
