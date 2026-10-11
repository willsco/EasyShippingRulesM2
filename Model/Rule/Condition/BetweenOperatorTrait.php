<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Rule\Condition;

/**
 * Adds "is between" / "is not between" operators to string and numeric conditions.
 *
 * The value is a range written as "min-max", e.g. "10-50" or "1,5-3,5". Ranges of
 * hyphenated postcodes such as "01000-000-01999-999" are also accepted.
 */
trait BetweenOperatorTrait
{
    /**
     * @var BetweenOperator|null
     */
    private ?BetweenOperator $betweenOperator = null;

    /**
     * Operator labels, including the "between" ones.
     *
     * @return array
     */
    public function getDefaultOperatorOptions()
    {
        if (null === $this->_defaultOperatorOptions) {
            parent::getDefaultOperatorOptions();
            $this->_defaultOperatorOptions[BetweenOperator::BETWEEN] = __('is between');
            $this->_defaultOperatorOptions[BetweenOperator::NOT_BETWEEN] = __('is not between');
        }

        return $this->_defaultOperatorOptions;
    }

    /**
     * Get default operator input by type.
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            foreach (['string', 'numeric'] as $type) {
                $this->_defaultOperatorInputByType[$type][] = BetweenOperator::BETWEEN;
                $this->_defaultOperatorInputByType[$type][] = BetweenOperator::NOT_BETWEEN;
            }
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * Keep the range as typed; parent implementations would try to parse it as a single number.
     *
     * @param array $arr
     * @return $this
     */
    public function loadArray($arr)
    {
        if (!$this->getBetweenOperator()->isBetween($arr['operator'] ?? null) || !isset($arr['value'])) {
            return parent::loadArray($arr);
        }

        // An invalid range is kept as typed, so the admin can see and fix it; it never matches.
        $range = $this->getBetweenOperator()->parseRange((string)$arr['value']);
        $value = $range === null ? trim((string)$arr['value']) : implode('-', $range);
        unset($arr['value'], $arr['is_value_parsed']);
        parent::loadArray($arr);
        $this->setValue($value);

        return $this;
    }

    /**
     * Validate attribute.
     *
     * @param mixed $validatedValue
     * @return bool
     */
    public function validateAttribute($validatedValue)
    {
        $operator = $this->getOperatorForValidate();
        if (!$this->getBetweenOperator()->isBetween($operator)) {
            return parent::validateAttribute($validatedValue);
        }

        if (!is_scalar($validatedValue) || $validatedValue === '') {
            return false;
        }

        $range = $this->getBetweenOperator()->parseRange((string)$this->getValueParsed());
        if ($range === null) {
            return false;
        }

        $isInside = $this->getBetweenOperator()->isInRange((string)$validatedValue, $range[0], $range[1]);

        return $operator === BetweenOperator::BETWEEN ? $isInside : !$isInside;
    }

    /**
     * Stateless helper, created on demand so the core condition constructors stay untouched.
     *
     * @return BetweenOperator
     */
    private function getBetweenOperator(): BetweenOperator
    {
        return $this->betweenOperator ??= new BetweenOperator();
    }
}
