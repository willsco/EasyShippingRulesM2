<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule;

use Magento\Rule\Model\Condition\AbstractCondition;

/**
 * Condition classes a shipping rule may contain.
 *
 * Condition types arrive in requests (new condition rows, posted rules), so only the conditions
 * of this module are accepted, not every condition class installed in Magento.
 */
class ConditionTypes
{
    private const NAMESPACE_PREFIX = 'WillSco\\EasyShippingRulesM2\\Model\\Rule\\Condition\\';

    /**
     * Whether the class may be used as a shipping rule condition.
     *
     * @param string $type class name, without the "|attribute" suffix
     * @return bool
     */
    public function isAllowed(string $type): bool
    {
        $type = ltrim($type, '\\');

        return str_starts_with($type, self::NAMESPACE_PREFIX)
            && class_exists($type)
            && is_subclass_of($type, AbstractCondition::class);
    }

    /**
     * Whether every node of a conditions tree (as built by asArray()) has an allowed type.
     *
     * @param array $node
     * @return bool
     */
    public function isTreeAllowed(array $node): bool
    {
        if (!$this->isAllowed((string)($node['type'] ?? ''))) {
            return false;
        }

        foreach ((array)($node['conditions'] ?? []) as $child) {
            if (!is_array($child) || !$this->isTreeAllowed($child)) {
                return false;
            }
        }

        return true;
    }
}
