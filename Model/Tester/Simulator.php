<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Tester;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;
use WillSco\EasyShippingRulesM2\Model\Carrier;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Carrier\CollectionFactory as CarrierCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod\CollectionFactory as CustomMethodCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\ExistingMethod\CollectionFactory as ExistingMethodCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\RuleValidator;
use WillSco\EasyShippingRulesM2\Model\Shipping\CarrierCode;
use WillSco\EasyShippingRulesM2\Model\Shipping\ProcessorState;
use WillSco\EasyShippingRulesM2\Model\Shipping\RateProcessor;

/**
 * Builds an in-memory cart (never saved), rates it with and without this module and explains the outcome.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Simulator
{
    /**
     * Initialize dependencies.
     *
     * @param QuoteFactory $quoteFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param RegionFactory $regionFactory
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param ScopeConfigInterface $scopeConfig
     * @param ProcessorState $processorState
     * @param RuleValidator $ruleValidator
     * @param CarrierCode $carrierCode
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param CarrierCollectionFactory $carrierCollectionFactory
     * @param CustomMethodCollectionFactory $customMethodCollectionFactory
     * @param ExistingMethodCollectionFactory $existingMethodCollectionFactory
     */
    public function __construct(
        private readonly QuoteFactory $quoteFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly RegionFactory $regionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Emulation $emulation,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProcessorState $processorState,
        private readonly RuleValidator $ruleValidator,
        private readonly CarrierCode $carrierCode,
        private readonly RuleCollectionFactory $ruleCollectionFactory,
        private readonly CarrierCollectionFactory $carrierCollectionFactory,
        private readonly CustomMethodCollectionFactory $customMethodCollectionFactory,
        private readonly ExistingMethodCollectionFactory $existingMethodCollectionFactory
    ) {
    }

    /**
     * Simulate shipping for the given input.
     *
     * @param SimulationInput $input
     * @return array{
     *     warnings: string[], cart: array, rates: array, rules: array,
     *     custom_methods: array, existing_methods: array
     * }
     * @throws LocalizedException when the cart cannot be built
     */
    public function simulate(SimulationInput $input): array
    {
        // Emulating the store also switches to its locale, so texts stay phrases until the admin locale is back.
        $this->emulation->startEnvironmentEmulation($input->storeId, Area::AREA_FRONTEND, true);
        try {
            $result = $input->activeRuleIds === null
                ? $this->run($input)
                : $this->ruleValidator->runWithActiveRules($input->activeRuleIds, fn () => $this->run($input));
        } catch (LocalizedException $e) {
            $error = $e;
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }

        if (isset($error)) {
            throw new LocalizedException(new Phrase($error->getRawMessage(), $error->getParameters()), $error);
        }
        array_walk_recursive($result, static function (&$value): void {
            if ($value instanceof Phrase) {
                $value = $value->render();
            }
        });

        return $result;
    }

    /**
     * Rate the cart and explain the outcome, inside the store emulation.
     *
     * @param SimulationInput $input
     * @return array
     * @throws LocalizedException when the cart cannot be built
     */
    private function run(SimulationInput $input): array
    {
        $warnings = [];
        $quote = $this->buildQuote($input, $warnings);
        $address = $quote->getShippingAddress();

        $before = $this->processorState->runDisabled(fn () => $this->collectRates($quote));
        $after = $this->collectRates($quote);

        return [
            'warnings' => $warnings,
            'cart' => [
                'subtotal' => (float)$address->getBaseSubtotal(),
                'qty' => (float)$address->getItemQty(),
                'weight' => (float)$address->getWeight(),
                'customer_group_id' => (int)$quote->getCustomerGroupId(),
            ],
            'rates' => $this->compareRates($before, $after),
            'rules' => $this->explainRules($address),
            'custom_methods' => $this->explainCustomMethods($address, $input->storeId),
            'existing_methods' => $this->explainExistingMethods($address, $input->storeId),
        ];
    }

    /**
     * Build the cart; problems with single items become warnings.
     *
     * @param SimulationInput $input
     * @param Phrase[] $warnings
     * @return Quote
     * @throws LocalizedException
     */
    private function buildQuote(SimulationInput $input, array &$warnings): Quote
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore($input->storeId);
        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setIsActive(false);

        if ($input->customerEmail !== '') {
            try {
                $customer = $this->customerRepository->get($input->customerEmail, (int)$store->getWebsiteId());
                $quote->assignCustomer($customer);
            } catch (NoSuchEntityException $e) {
                throw new LocalizedException(
                    __('No customer with email "%1" in this website.', $input->customerEmail)
                );
            }
        } else {
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerGroupId($input->customerGroupId);
        }

        foreach ($input->items as $sku => $qty) {
            try {
                $product = $this->productRepository->get($sku, false, $input->storeId, true);
                $result = $quote->addProduct($product, $qty);
                if (is_string($result)) {
                    $warnings[] = __('SKU "%1": %2', $sku, $result);
                }
            } catch (NoSuchEntityException $e) {
                $warnings[] = __('SKU "%1" does not exist.', $sku);
            } catch (LocalizedException $e) {
                $warnings[] = __('SKU "%1": %2', $sku, new Phrase($e->getRawMessage(), $e->getParameters()));
            }
        }

        if (!$quote->getAllVisibleItems()) {
            throw new LocalizedException(__('Add at least one product that can be added to the cart.'));
        }

        $address = $quote->getShippingAddress();
        $address->setCountryId($input->countryId)
            ->setPostcode($input->postcode)
            ->setCity($input->city);
        if ($input->region !== '') {
            $region = $this->regionFactory->create()->loadByCode($input->region, $input->countryId);
            $region->getId()
                ? $address->setRegionId($region->getId())->setRegion($region->getName())
                : $address->setRegion($input->region);
        }

        return $quote;
    }

    /**
     * Collect totals and shipping rates of the quote.
     *
     * @param Quote $quote
     * @return array<string, array{carrier: string, method: string, price: float, error: string}>
     */
    private function collectRates(Quote $quote): array
    {
        $address = $quote->getShippingAddress();
        $address->setCollectShippingRates(true);
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        $rates = [];
        foreach ($address->getAllShippingRates() as $rate) {
            $rates[(string)$rate->getCode()] = [
                'carrier' => (string)$rate->getCarrierTitle(),
                'method' => (string)$rate->getMethodTitle(),
                'price' => (float)$rate->getPrice(),
                'error' => (string)$rate->getErrorMessage(),
            ];
        }

        return $rates;
    }

    /**
     * Rates with this module, flagged against the rates without it.
     *
     * @param array $before
     * @param array $after
     * @return array
     */
    private function compareRates(array $before, array $after): array
    {
        $rates = [];
        foreach ($after as $code => $rate) {
            $original = $before[$code] ?? null;
            $status = 'unchanged';
            if ($original === null) {
                $status = 'added';
            } elseif ($original !== $rate) {
                $status = 'changed';
            }
            $rates[] = $rate + ['code' => $code, 'status' => $status, 'original' => $original];
        }
        foreach (array_diff_key($before, $after) as $code => $rate) {
            $rates[] = $rate + ['code' => $code, 'status' => 'hidden', 'original' => $rate];
        }

        return $rates;
    }

    /**
     * Validity of every rule for the simulated address.
     *
     * @param Address $address
     * @return array
     */
    private function explainRules(Address $address): array
    {
        $rules = [];
        foreach ($this->ruleCollectionFactory->create()->setOrder('rule_id', 'ASC') as $rule) {
            $valid = $this->ruleValidator->isValid((int)$rule->getId(), $address);
            $rules[] = [
                'id' => (int)$rule->getId(),
                'name' => (string)$rule->getName(),
                'status' => $valid === null ? 'disabled' : ($valid ? 'valid' : 'invalid'),
                'simulated' => $this->simulatedChange($rule->isActive(), $valid !== null),
            ];
        }

        return $rules;
    }

    /**
     * How the rule tester changed a rule: "enabled", "disabled" or "" when it kept its status.
     *
     * @param bool $enabled
     * @param bool $activeInSimulation
     * @return string
     */
    private function simulatedChange(bool $enabled, bool $activeInSimulation): string
    {
        if ($enabled === $activeInSimulation) {
            return '';
        }

        return $activeInSimulation ? 'enabled' : 'disabled';
    }

    /**
     * Why each custom method is (not) offered.
     *
     * @param Address $address
     * @param int $storeId
     * @return array
     */
    private function explainCustomMethods(Address $address, int $storeId): array
    {
        $carriers = [];
        /** @var Carrier $carrier */
        foreach ($this->carrierCollectionFactory->create() as $carrier) {
            $carriers[(int)$carrier->getId()] = $carrier;
        }
        $defaultRuleBlocks = $this->isBlockedByDefaultRule($address, $storeId);

        $result = [];
        foreach ($this->customMethodCollectionFactory->create()->setOrder('method_id', 'ASC') as $method) {
            $carrier = $carriers[(int)$method->getCarrierId()] ?? null;
            $reason = $this->commonReason($method, $address, $storeId);
            if ($reason === null && ($carrier === null || !$carrier->isActive())) {
                $reason = __('Its carrier is missing or disabled.');
            }
            if ($reason === null && $defaultRuleBlocks) {
                $reason = __('The Default Rule is not valid for this cart.');
            }
            $result[] = [
                'id' => (int)$method->getId(),
                'name' => (string)$method->getName(),
                'carrier' => $carrier ? (string)$carrier->getName() : '',
                'code' => $carrier ? $this->carrierCode->build((int)$carrier->getId()) . '_'
                    . $this->carrierCode->buildMethod((int)$method->getId()) : '',
                'applied' => $reason === null,
                'reason' => $reason ?? $this->validRuleText($method, $address),
            ];
        }

        return $result;
    }

    /**
     * Why each existing method is (not) applied.
     *
     * @param Address $address
     * @param int $storeId
     * @return array
     */
    private function explainExistingMethods(Address $address, int $storeId): array
    {
        $result = [];
        /** @var ExistingMethodInterface $method */
        foreach ($this->existingMethodCollectionFactory->create()->setOrder('method_id', 'ASC') as $method) {
            $reason = $this->commonReason($method, $address, $storeId);
            $result[] = [
                'id' => (int)$method->getId(),
                'name' => (string)$method->getName(),
                'action' => $method->getActionType(),
                'targets' => $method->getShippingMethods(),
                'applied' => $reason === null,
                'reason' => $reason ?? $this->validRuleText($method, $address),
            ];
        }

        return $result;
    }

    /**
     * Reason shared by both method types, or null when the method applies.
     *
     * @param MethodInterface $method
     * @param Address $address
     * @param int $storeId
     * @return Phrase|null
     */
    private function commonReason(MethodInterface $method, Address $address, int $storeId): ?Phrase
    {
        if (!$method->isActive()) {
            return __('The method is disabled.');
        }
        $storeIds = $method->getStoreIds();
        if (!in_array(Store::DEFAULT_STORE_ID, $storeIds, true) && !in_array($storeId, $storeIds, true)) {
            return __('The method is not assigned to this store view.');
        }
        if (!$method->getRuleIds()) {
            return __('The method has no rules.');
        }
        if ($this->ruleValidator->getFirstValidRule($this->sorted($method->getRuleIds()), $address) === null) {
            return __('None of its rules (%1) is valid for this cart.', implode(', ', $method->getRuleIds()));
        }

        return null;
    }

    /**
     * Text naming the rule that made the method apply.
     *
     * @param MethodInterface $method
     * @param Address $address
     * @return Phrase|string
     */
    private function validRuleText(MethodInterface $method, Address $address): Phrase|string
    {
        $rule = $this->ruleValidator->getFirstValidRule($this->sorted($method->getRuleIds()), $address);

        return $rule ? __('Applied by rule #%1 "%2".', $rule->getId(), $rule->getName()) : '';
    }

    /**
     * Whether the configured Default Rule keeps custom methods from being offered.
     *
     * @param Address $address
     * @param int $storeId
     * @return bool
     */
    private function isBlockedByDefaultRule(Address $address, int $storeId): bool
    {
        $ruleId = (int)$this->scopeConfig->getValue(
            RateProcessor::XML_PATH_DEFAULT_RULE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $ruleId && $this->ruleValidator->isValid($ruleId, $address) === false;
    }

    /**
     * Rule IDs in the order RateProcessor evaluates them.
     *
     * @param int[] $ruleIds
     * @return int[]
     */
    private function sorted(array $ruleIds): array
    {
        sort($ruleIds);

        return $ruleIds;
    }
}
