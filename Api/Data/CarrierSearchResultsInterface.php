<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results of carriers.
 */
interface CarrierSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return \WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface[]
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param \WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
