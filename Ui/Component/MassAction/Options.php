<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Ui\Component\MassAction;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\UrlInterface;

/**
 * Mass action sub-menu with one entry per option, posting "field" and "value" to "urlPath".
 *
 * Configure one virtual type per field in etc/adminhtml/di.xml.
 */
class Options implements \JsonSerializable
{
    /**
     * Initialize dependencies.
     *
     * @param UrlInterface $urlBuilder
     * @param OptionSourceInterface $optionSource
     * @param string $field
     * @param string $urlPath
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly OptionSourceInterface $optionSource,
        private readonly string $field,
        private readonly string $urlPath = '*/*/massUpdate'
    ) {
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        $actions = [];
        foreach ($this->optionSource->toOptionArray() as $option) {
            if (!isset($option['value']) || $option['value'] === '' || is_array($option['value'])) {
                continue;
            }
            $actions[] = [
                'type' => $this->field . '_' . $option['value'],
                'label' => $option['label'],
                'url' => $this->urlBuilder->getUrl(
                    $this->urlPath,
                    ['field' => $this->field, 'value' => $option['value']]
                ),
            ];
        }

        return $actions;
    }
}
