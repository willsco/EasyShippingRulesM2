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
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;
use WillSco\EasyShippingRulesM2\Model\Carrier;
use WillSco\EasyShippingRulesM2\Model\CustomMethod;
use WillSco\EasyShippingRulesM2\Model\ExistingMethod;
use WillSco\EasyShippingRulesM2\Model\RateTable\DeliveryTimeFormatter;
use WillSco\EasyShippingRulesM2\Model\RateTable\RateFinder;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier\CollectionFactory as CarrierCollectionFactory;
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
     * @param CarrierCollectionFactory $carrierCollectionFactory
     * @param RateFinder $rateFinder
     * @param DeliveryTimeFormatter $deliveryTimeFormatter
     */
    public function __construct(
        private readonly CustomMethodCollectionFactory $customMethodCollectionFactory,
        private readonly ExistingMethodCollectionFactory $existingMethodCollectionFactory,
        private readonly RuleValidator $ruleValidator,
        private readonly PriceCalculator $priceCalculator,
        private readonly CarrierCode $carrierCode,
        private readonly MethodFactory $rateMethodFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CarrierCollectionFactory $carrierCollectionFactory,
        private readonly RateFinder $rateFinder,
        private readonly DeliveryTimeFormatter $deliveryTimeFormatter
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

        /** @var CustomMethod[] $methods */
        $methods = $this->customMethodCollectionFactory->create()
            ->addActiveFilter()
            ->addStoreFilter($storeId)
            ->addActiveCarrierFilter()
            ->setOrder('main_table.method_id', 'ASC')
            ->getItems();
        if (!$methods) {
            return [];
        }

        $tableRates = $this->rateTableRates($methods, $request);
        $prices = [];
        $titles = [];
        foreach ($methods as $method) {
            $methodId = (int)$method->getId();
            $price = $this->getPrice($method, $request, $address, $tableRates);
            if ($price !== null) {
                $prices[$methodId] = $freeShipping ? 0.0 : $price;
                $titles[$methodId] = $this->deliveryTimeFormatter->format(
                    (string)$method->getName(),
                    $tableRates[$methodId]['delivery_time'] ?? ''
                );
            }
        }

        // One rate per method and carrier, grouped by carrier position.
        $rates = [];
        foreach ($this->getActiveCarriers() as $carrierId => $carrier) {
            $carrierCode = $this->carrierCode->build($carrierId);
            if ($limitCarriers !== null && !in_array($carrierCode, $limitCarriers, true)) {
                continue;
            }

            foreach ($methods as $method) {
                $methodId = (int)$method->getId();
                if (!isset($prices[$methodId]) || !in_array($carrierId, $method->getCarrierIds(), true)) {
                    continue;
                }

                /** @var Method $rate */
                $rate = $this->rateMethodFactory->create();
                $rate->setCarrier($carrierCode);
                $rate->setCarrierTitle($carrier->getName());
                $rate->setMethod($this->carrierCode->buildMethod($methodId));
                $rate->setMethodTitle($titles[$methodId]);
                $rate->setPrice($prices[$methodId]);
                $rate->setCost($prices[$methodId]);

                $rates[] = $rate;
            }
        }

        return $rates;
    }

    /**
     * Price of a custom method, or null when it does not apply to the cart.
     *
     * A rate table method needs a matching table row; when it has rules, one must also be valid.
     *
     * @param CustomMethodInterface $method
     * @param RateRequest $request
     * @param Address $address
     * @param array $tableRates rate table price and delivery time by method ID
     * @return float|null
     */
    private function getPrice(
        CustomMethodInterface $method,
        RateRequest $request,
        Address $address,
        array $tableRates
    ): ?float {
        $ruleIds = $method->getRuleIds();
        $rule = $ruleIds ? $this->ruleValidator->getFirstValidRule($ruleIds, $address) : null;

        if ($method->getCalculationType() === MethodInterface::CALCULATION_RATE_TABLE) {
            return $ruleIds && $rule === null ? null : ($tableRates[(int)$method->getId()]['price'] ?? null);
        }

        return $rule === null ? null : $this->priceCalculator->calculate($method, $rule, $request);
    }

    /**
     * Price and delivery time of the rate table methods with a matching row, by method ID.
     *
     * @param CustomMethodInterface[] $methods
     * @param RateRequest $request
     * @return array
     */
    private function rateTableRates(array $methods, RateRequest $request): array
    {
        $methodIds = [];
        foreach ($methods as $method) {
            if ($method->getCalculationType() === MethodInterface::CALCULATION_RATE_TABLE) {
                $methodIds[] = (int)$method->getId();
            }
        }

        return $methodIds ? $this->rateFinder->findRates($methodIds, $request) : [];
    }

    /**
     * Active carriers by ID, in position order.
     *
     * @return array<int, Carrier>
     */
    private function getActiveCarriers(): array
    {
        $collection = $this->carrierCollectionFactory->create()
            ->addFieldToFilter('is_active', 1)
            ->setOrder('sort_order', 'ASC')
            ->setOrder('carrier_id', 'ASC');

        $carriers = [];
        /** @var Carrier $carrier */
        foreach ($collection as $carrier) {
            $carriers[(int)$carrier->getId()] = $carrier;
        }

        return $carriers;
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
            ->setOrder('main_table.sort_order', 'ASC')
            ->setOrder('main_table.method_id', 'ASC');

        // Rates changed by a method with "Discard subsequent existing methods", by rate code.
        $locked = [];
        /** @var ExistingMethod $method */
        foreach ($methods as $method) {
            $rule = $this->ruleValidator->getFirstValidRule($method->getRuleIds(), $address);
            if ($rule === null) {
                continue;
            }

            $changed = [];
            foreach ($rates as $rate) {
                if (!$rate instanceof Method
                    || isset($locked[$this->rateCode($rate)])
                    || !$this->matches($method, $rate)
                ) {
                    $changed[] = $rate;
                    continue;
                }

                if ($method->getActionType() === ExistingMethodInterface::ACTION_HIDE) {
                    continue;
                }

                if ($method->isStopProcessing()) {
                    $locked[$this->rateCode($rate)] = true;
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
            || in_array($this->rateCode($rate), $codes, true);
    }

    /**
     * Shipping method code of a rate, as "carrier_method".
     *
     * @param Method $rate
     * @return string
     */
    private function rateCode(Method $rate): string
    {
        return $rate->getCarrier() . '_' . $rate->getMethod();
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
}
