<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Shipping;

/**
 * Request-wide switch that lets the rule tester collect rates without this module.
 */
class ProcessorState
{
    /**
     * @var bool
     */
    private bool $enabled = true;

    /**
     * Whether custom and existing methods are applied to collected rates.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Run a callback with rule processing turned off.
     *
     * @param callable $callback
     * @return mixed callback result
     */
    public function runDisabled(callable $callback)
    {
        $previous = $this->enabled;
        $this->enabled = false;
        try {
            return $callback();
        } finally {
            $this->enabled = $previous;
        }
    }
}
