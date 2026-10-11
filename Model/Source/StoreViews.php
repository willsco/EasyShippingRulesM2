<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Source;

use Magento\Store\Model\Store;
use Magento\Store\Ui\Component\Listing\Column\Store\Options as StoreOptions;

/**
 * Store views grouped by website, plus "All Store Views".
 */
class StoreViews extends StoreOptions
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->currentOptions['All Store Views']['label'] = __('All Store Views');
        $this->currentOptions['All Store Views']['value'] = (string)Store::DEFAULT_STORE_ID;
        $this->generateCurrentOptions();
        $this->options = array_values($this->currentOptions);

        return $this->options;
    }
}
