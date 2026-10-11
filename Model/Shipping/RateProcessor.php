<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Shipping;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Store\Model\ScopeInterface;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;
use WillSco\EasyShippingRulesM2\Model\CustomMethod;
use WillSco\EasyShippingRulesM2\Model\ExistingMethod;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod\CollectionFactory as CustomMethodCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\ExistingMethod\CollectionFactory as ExistingMethodCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\RuleValidator;

/**
 * Adds custom method rates and applies existing method actions to a rate result.
 */
class RateProcessor
{
    public const XML_PATH_DEFAULT_RULE = 'easyshippingrules/general/default_rule';

    /**
     * Initialize dependencies.
     *
     * @param CustomMethodCollectionFactory $customMethodCollectionFactory
     * @param ExistingMethodCollectionFactory $existingMethodCollectionFactory
     * @param RuleValidator $ruleValidator
     * @param PriceCalculator $priceCalculator
     * @param CarrierCode $carrierCode
     * @param MethodFactory $rateMethodFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly CustomMethodCollectionFactory $customMethodCollectionFactory,
        private readonly ExistingMethodCollectionFactory $existingMethodCollectionFactory,
        private readonly RuleValidator $ruleValidator,
        private readonly PriceCalculator $priceCalculator,
        private readonly CarrierCode $carrierCode,
        private readonly MethodFactory $rateMethodFactory,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Custom methods are added first so existing method actions can also target them.
     *
     * The new rate list is built apart from the result, on copies of the rates, and swapped in at the
     * end: an exception halfway leaves the carriers' rates untouched instead of half of them.
     *
     * @param Result $result
     * @param RateRequest $request
     * @param Address $address
     * @return void
     */
    public function process(Result $result, RateRequest $request, Address $address): void
    {
        $storeId = (int)$request->getStoreId();

        $rates = $this->ruleValidator->runMemoized(function () use ($result, $request, $address, $storeId) {
            $rates = array_merge(
                $result->getAllRates(),
                $this->buildCustomMethodRates($request, $address, $storeId)
            );

            return $this->applyExistingMethods($rates, $request, $address, $storeId);
        });

        $result->reset();
        foreach ($rates as $rate) {
            $result->append($rate);
        }
    }

    /**
     * Rates of the custom methods that apply.
     *
     * @param RateRequest $request
     * @param Address $address
     * @param int $storeId
     * @return Method[]
     */
    private function buildCustomMethodRates(RateRequest $request, Address $address, int $storeId): array
    {
        if (!$this->isDefaultRuleValid($address, $storeId)) {
            return [];
        }

        $limitCarriers = $request->getLimitCarrier() ? (array)$request->getLimitCarrier() : null;
        // Free shipping granted by a Cart Price Rule zeroes custom methods, as native carriers do.
        $freeShipping = (bool)$request->getFreeShipping();

        $methods = $this->customMethodCollectionFactory->create()
            ->addActiveFilter()
            ->addStoreFilter($storeId)
            ->addActiveCarrierFilter();

        $rates = [];
        /** @var CustomMethod $method */
        foreach ($methods as $method) {
            $carrierCode = $this->carrierCode->build((int)$method->getCarrierId());
            if ($limitCarriers !== null && !in_array($carrierCode, $limitCarriers, true)) {
                continue;
            }

            $rule = $this->ruleValidator->getFirstValidRule($this->sortedRuleIds($method->getRuleIds()), $address);
            if ($rule === null) {
                continue;
            }

            $price = $freeShipping ? 0.0 : $this->priceCalculator->calculate($method, $rule, $request);

            /** @var Method $rate */
            $rate = $this->rateMethodFactory->create();
            $rate->setCarrier($carrierCode);
            $rate->setCarrierTitle($method->getData('carrier_name'));
            $rate->setMethod($this->carrierCode->buildMethod((int)$method->getId()));
            $rate->setMethodTitle($method->getName());
            $rate->setPrice($price);
            $rate->setCost($price);

            $rates[] = $rate;
        }

        return $rates;
    }

    /**
     * Apply existing methods.
     *
     * @param array $rates rates and carrier errors
     * @param RateRequest $request
     * @param Address $address
     * @param int $storeId
     * @return array
     */
    private function applyExistingMethods(array $rates, RateRequest $request, Address $address, int $storeId): array
    {
        $methods = $this->existingMethodCollectionFactory->create()
            ->addActiveFilter()
            ->addStoreFilter($storeId)
            ->setOrder('main_table.method_id', 'ASC');

        /** @var ExistingMethod $method */
        foreach ($methods as $method) {
            $rule = $this->ruleValidator->getFirstValidRule($this->sortedRuleIds($method->getRuleIds()), $address);
            if ($rule === null) {
                continue;
            }

            $changed = [];
            foreach ($rates as $rate) {
                if (!$rate instanceof Method || !$this->matches($method, $rate)) {
                    $changed[] = $rate;
                    continue;
                }

                if ($method->getActionType() === ExistingMethodInterface::ACTION_HIDE) {
                    continue;
                }

                // The result still holds the original rate until the swap.
                $rate = clone $rate;
                if ($method->getCarrierTitle() !== null) {
                    $rate->setCarrierTitle($method->getCarrierTitle());
                }
                if ($method->getMethodTitle() !== null) {
                    $rate->setMethodTitle($method->getMethodTitle());
                }

                // Overwriting with no value anywhere would silently make the rate free: keep its price.
                if ($method->getActionType() === ExistingMethodInterface::ACTION_OVERWRITE
                    && $method->getAmount() === null
                    && $rule->getAmount() === null
                ) {
                    $changed[] = $rate;
                    continue;
                }

                $ratePrice = (float)$rate->getPrice();
                $amount = $this->priceCalculator->calculate($method, $rule, $request, $ratePrice);
                $rate->setPrice(match ($method->getActionType()) {
                    ExistingMethodInterface::ACTION_SURCHARGE => $ratePrice + $amount,
                    ExistingMethodInterface::ACTION_DISCOUNT => max($ratePrice - $amount, 0.0),
                    default => $amount,
                });

                $changed[] = $rate;
            }
            $rates = $changed;
        }

        return $rates;
    }

    /**
     * Matches.
     *
     * @param ExistingMethodInterface $method
     * @param Method $rate
     * @return bool
     */
    private function matches(ExistingMethodInterface $method, Method $rate): bool
    {
        $codes = $method->getShippingMethods();

        return in_array(ExistingMethodInterface::ALL_SHIPPING_METHODS, $codes, true)
            || in_array($rate->getCarrier() . '_' . $rate->getMethod(), $codes, true);
    }

    /**
     * When a default rule is configured, custom methods are only offered if it is valid.
     *
     * A missing or inactive default rule does not block anything.
     *
     * @param Address $address
     * @param int $storeId
     * @return bool
     */
    private function isDefaultRuleValid(Address $address, int $storeId): bool
    {
        $ruleId = (int)$this->scopeConfig->getValue(self::XML_PATH_DEFAULT_RULE, ScopeInterface::SCOPE_STORE, $storeId);
        if (!$ruleId) {
            return true;
        }

        return $this->ruleValidator->isValid($ruleId, $address) !== false;
    }

    /**
     * Lowest rule ID wins when more than one rule is valid.
     *
     * @param int[] $ruleIds
     * @return int[]
     */
    private function sortedRuleIds(array $ruleIds): array
    {
        sort($ruleIds);

        return $ruleIds;
    }
}
