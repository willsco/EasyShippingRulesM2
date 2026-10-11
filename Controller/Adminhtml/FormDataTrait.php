<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

/**
 * Normalizes values posted by the admin forms.
 */
trait FormDataTrait
{
    /**
     * Empty means "not set"; a decimal comma is accepted.
     *
     * @param mixed $value
     * @return float|null
     */
    private function toNullableFloat($value): ?float
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $value = trim((string)$value);
        if (!str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * Posted ID list as unique integers.
     *
     * @param mixed $value
     * @return int[]
     */
    private function toIdList($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return array_values(array_unique(array_map('intval', array_filter((array)$value, 'is_numeric'))));
    }

    /**
     * Store views, defaulting to "All Store Views" when nothing is selected (e.g. single store mode).
     *
     * @param mixed $value
     * @return int[]
     */
    private function toStoreIds($value): array
    {
        $storeIds = $this->toIdList($value);

        return $storeIds ?: [Store::DEFAULT_STORE_ID];
    }

    /**
     * Posted value, checked against the option source.
     *
     * @param mixed $value
     * @param OptionSourceInterface $source
     * @param string $label
     * @return string
     * @throws LocalizedException
     */
    private function toOption($value, OptionSourceInterface $source, string $label): string
    {
        foreach ($source->toOptionArray() as $option) {
            if ((string)$option['value'] === (string)$value) {
                return (string)$value;
            }
        }

        throw new LocalizedException(__('Please select a valid "%1".', __($label)));
    }

    /**
     * Trimmed posted value that must not be empty.
     *
     * @param mixed $value
     * @param string $label
     * @return string
     * @throws LocalizedException
     */
    private function toRequiredString($value, string $label): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            throw new LocalizedException(__('"%1" is required.', __($label)));
        }

        return $value;
    }
}
