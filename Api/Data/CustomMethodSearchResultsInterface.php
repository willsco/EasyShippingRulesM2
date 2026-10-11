<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results of custom methods.
 */
interface CustomMethodSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return \WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface[]
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param \WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
