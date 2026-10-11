<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule;

use WillSco\EasyShippingRulesM2\Api\ProgrammaticConditionInterface;

/**
 * Programmatic conditions registered through di.xml.
 */
class ProgrammaticConditionPool
{
    /**
     * Initialize dependencies.
     *
     * @param ProgrammaticConditionInterface[] $conditions indexed by code
     */
    public function __construct(
        private readonly array $conditions = []
    ) {
        foreach ($conditions as $code => $condition) {
            if (!$condition instanceof ProgrammaticConditionInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Programmatic condition "%s" must implement %s.',
                    $code,
                    ProgrammaticConditionInterface::class
                ));
            }
        }
    }

    /**
     * Get.
     *
     * @param string $code
     * @return ProgrammaticConditionInterface|null
     */
    public function get(string $code): ?ProgrammaticConditionInterface
    {
        return $this->conditions[$code] ?? null;
    }

    /**
     * Get options.
     *
     * @return array<string, string> labels indexed by code
     */
    public function getOptions(): array
    {
        $options = [];
        foreach ($this->conditions as $code => $condition) {
            $options[$code] = $condition->getLabel();
        }

        return $options;
    }

    /**
     * Whether empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->conditions;
    }
}
