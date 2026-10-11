<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Integration\Model\Shipping;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\OfflineShipping\Model\SalesRule\Rule as FreeShippingSource;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\CustomMethodRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterfaceFactory;
use WillSco\EasyShippingRulesM2\Api\Data\CustomMethodInterfaceFactory;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterface;
use WillSco\EasyShippingRulesM2\Api\Data\ExistingMethodInterfaceFactory;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;
use WillSco\EasyShippingRulesM2\Api\ExistingMethodRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\RuleRepositoryInterface;
use WillSco\EasyShippingRulesM2\Model\Rule;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\Address;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\Combine;
use WillSco\EasyShippingRulesM2\Model\Rule\Condition\CustomerGroup;
use WillSco\EasyShippingRulesM2\Model\RuleFactory;
use WillSco\EasyShippingRulesM2\Model\Shipping\Carrier as EsrCarrier;
use WillSco\EasyShippingRulesM2\Model\Shipping\RateProcessor;

/**
 * Rates a real quote with Flat Rate ($5 per item, Magento default) and this module's methods.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoAppArea frontend
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RateProcessorTest extends TestCase
{
    private const SKU = 'esr-test-simple';
    private const SAO_PAULO = '01310-100';
    private const RIO = '20040-020';

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->createProduct();
    }

    public function testCustomMethodIsOfferedWhenItsRuleIsValid(): void
    {
        $carrier = $this->createCarrier('Our Delivery');
        $rule = $this->createRule('São Paulo', [$this->postcodeBetween('01000-000-05999-999')], 12.5);
        $method = $this->createCustomMethod($carrier, 'Express', [(int)$rule->getId()]);

        $rates = $this->collectRates($this->createQuote(self::SAO_PAULO, 2));

        $code = 'easyshippingrules' . $carrier->getId() . '_m' . $method->getId();
        $this->assertArrayHasKey($code, $rates);
        $this->assertSame(12.5, $rates[$code]['price'], 'Price falls back to the rule amount');
        $this->assertSame('Our Delivery', $rates[$code]['carrier']);
        $this->assertSame('Express', $rates[$code]['method']);
        $this->assertArrayHasKey('flatrate_flatrate', $rates);
    }

    public function testCartPriceRuleFreeShippingZeroesCustomMethods(): void
    {
        $carrier = $this->createCarrier('Our Delivery');
        $rule = $this->createRule('Always', [], 12.5);
        $method = $this->createCustomMethod($carrier, 'Express', [(int)$rule->getId()]);
        $this->createFreeShippingCartRule();

        $rates = $this->collectRates($this->createQuote(self::RIO, 2));

        $this->assertSame(0.0, $rates['easyshippingrules' . $carrier->getId() . '_m' . $method->getId()]['price']);
    }

    public function testCustomMethodIsNotOfferedWhenNoRuleIsValid(): void
    {
        $carrier = $this->createCarrier('Our Delivery');
        $rule = $this->createRule('São Paulo', [$this->postcodeBetween('01000-000-05999-999')]);
        $this->createCustomMethod($carrier, 'Express', [(int)$rule->getId()]);

        $rates = $this->collectRates($this->createQuote(self::RIO, 1));

        $this->assertSame(['flatrate_flatrate'], array_keys($rates));
    }

    public function testPerItemCalculationUsesTheMethodAmount(): void
    {
        $carrier = $this->createCarrier('Our Delivery');
        $rule = $this->createRule('Always', [], 99.0);
        $method = $this->createCustomMethod(
            $carrier,
            'Per item',
            [(int)$rule->getId()],
            MethodInterface::CALCULATION_FIXED_PER_ITEM,
            3.0
        );

        $rates = $this->collectRates($this->createQuote(self::RIO, 4));

        $this->assertSame(12.0, $rates['easyshippingrules' . $carrier->getId() . '_m' . $method->getId()]['price']);
    }

    public function testDisabledCarrierHidesItsMethods(): void
    {
        $carrier = $this->createCarrier('Our Delivery', false);
        $rule = $this->createRule('Always', []);
        $this->createCustomMethod($carrier, 'Express', [(int)$rule->getId()]);

        $rates = $this->collectRates($this->createQuote(self::RIO, 1));

        $this->assertSame(['flatrate_flatrate'], array_keys($rates));
    }

    public function testDefaultRuleBlocksCustomMethods(): void
    {
        $carrier = $this->createCarrier('Our Delivery');
        $always = $this->createRule('Always', []);
        $saoPaulo = $this->createRule('São Paulo', [$this->postcodeBetween('01000000-05999999')]);
        $this->createCustomMethod($carrier, 'Express', [(int)$always->getId()]);
        $this->objectManager->get(MutableScopeConfigInterface::class)->setValue(
            RateProcessor::XML_PATH_DEFAULT_RULE,
            (string)$saoPaulo->getId(),
            ScopeInterface::SCOPE_STORE,
            'default'
        );

        $this->assertCount(1, $this->collectRates($this->createQuote(self::RIO, 1)));
        $this->assertCount(2, $this->collectRates($this->createQuote(self::SAO_PAULO, 1)));
    }

    public function testExistingMethodSurchargeAndTitleOverride(): void
    {
        $rule = $this->createRule('Always', []);
        $this->createExistingMethod(
            ExistingMethodInterface::ACTION_SURCHARGE,
            ['flatrate_flatrate'],
            [(int)$rule->getId()],
            10.0,
            'Standard'
        );

        $rates = $this->collectRates($this->createQuote(self::RIO, 3));

        $this->assertSame(25.0, $rates['flatrate_flatrate']['price'], '3 x $5 + $10');
        $this->assertSame('Standard', $rates['flatrate_flatrate']['method']);
    }

    public function testExistingMethodDiscountNeverGoesBelowZero(): void
    {
        $rule = $this->createRule('Always', []);
        $this->createExistingMethod(ExistingMethodInterface::ACTION_DISCOUNT, ['all'], [(int)$rule->getId()], 50.0);

        $rates = $this->collectRates($this->createQuote(self::RIO, 1));

        $this->assertSame(0.0, $rates['flatrate_flatrate']['price']);
    }

    public function testOverwriteWithoutAnyValueKeepsTheRatePrice(): void
    {
        $rule = $this->createRule('Always', []);
        $this->createExistingMethod(
            ExistingMethodInterface::ACTION_OVERWRITE,
            ['flatrate_flatrate'],
            [(int)$rule->getId()],
            null,
            'Standard'
        );

        $rates = $this->collectRates($this->createQuote(self::RIO, 2));

        $this->assertSame(10.0, $rates['flatrate_flatrate']['price'], '2 x $5, not free');
        $this->assertSame('Standard', $rates['flatrate_flatrate']['method'], 'Titles still change');
    }

    public function testOverwriteFallsBackToTheRuleValue(): void
    {
        $rule = $this->createRule('Always', [], 7.0);
        $this->createExistingMethod(ExistingMethodInterface::ACTION_OVERWRITE, ['all'], [(int)$rule->getId()]);

        $rates = $this->collectRates($this->createQuote(self::RIO, 2));

        $this->assertSame(7.0, $rates['flatrate_flatrate']['price']);
    }

    public function testExistingMethodHidesRatesOnlyWhenItsRuleIsValid(): void
    {
        $rule = $this->createRule('Rio', [$this->postcodeBetween('20000000-28999999')]);
        $this->createExistingMethod(ExistingMethodInterface::ACTION_HIDE, ['all'], [(int)$rule->getId()]);

        $this->assertSame([], $this->collectRates($this->createQuote(self::RIO, 1)));
        $this->assertCount(1, $this->collectRates($this->createQuote(self::SAO_PAULO, 1)));
    }

    public function testCustomerGroupConditionMatchesGuests(): void
    {
        $carrier = $this->createCarrier('Our Delivery');
        $guests = $this->createRule('Guests', [[
            'type' => CustomerGroup::class,
            'attribute' => 'customer_group_id',
            'operator' => '()',
            'value' => ['0'],
        ]]);
        $method = $this->createCustomMethod($carrier, 'Guest delivery', [(int)$guests->getId()]);

        $rates = $this->collectRates($this->createQuote(self::RIO, 1));

        $this->assertArrayHasKey('easyshippingrules' . $carrier->getId() . '_m' . $method->getId(), $rates);
    }

    public function testRuleConditionsSurviveSaveAndLoad(): void
    {
        $rule = $this->createRule('São Paulo', [$this->postcodeBetween('01000-000-05999-999')]);

        /** @var Rule $loaded */
        $loaded = $this->objectManager->create(RuleFactory::class)->create()->load($rule->getId());
        $condition = $loaded->getConditions()->getConditions()[0];

        $this->assertInstanceOf(Address::class, $condition);
        $this->assertSame('><', $condition->getOperator());
        $this->assertSame('01000-000-05999-999', $condition->getValue());
    }

    public function testCarrierCodeResolvesToModuleCarrier(): void
    {
        $carrier = $this->createCarrier('Our Delivery');
        $carrier->setSortOrder(7);
        $this->objectManager->get(CarrierRepositoryInterface::class)->save($carrier);

        $instance = $this->objectManager->get(CarrierFactory::class)->get('easyshippingrules' . $carrier->getId());

        $this->assertInstanceOf(EsrCarrier::class, $instance);
        $this->assertSame(7, $instance->getSortOrder());
        $this->assertSame('Our Delivery', $instance->getConfigData('title'));
    }

    /**
     * Create the simple product used by every quote.
     *
     * @return void
     */
    private function createProduct(): void
    {
        /** @var ProductInterface $product */
        $product = $this->objectManager->get(ProductFactory::class)->create();
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setWebsiteIds([1])
            ->setName('ESR Test Product')
            ->setSku(self::SKU)
            ->setPrice(100)
            ->setWeight(1)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 1000, 'is_in_stock' => 1]);
        $this->objectManager->get(ProductRepositoryInterface::class)->save($product);
    }

    /**
     * Create a coupon-less Cart Price Rule granting free shipping to guests.
     *
     * @return void
     */
    private function createFreeShippingCartRule(): void
    {
        $this->objectManager->create(SalesRule::class)
            ->setName('ESR Free Shipping')
            ->setIsActive(1)
            ->setWebsiteIds([1])
            ->setCustomerGroupIds([0])
            ->setCouponType(SalesRule::COUPON_TYPE_NO_COUPON)
            ->setSimpleAction(SalesRule::BY_PERCENT_ACTION)
            ->setDiscountAmount(0)
            ->setSimpleFreeShipping(FreeShippingSource::FREE_SHIPPING_ADDRESS)
            ->save();
    }

    /**
     * Create an unsaved guest quote shipping to Brazil.
     *
     * @param string $postcode
     * @param int $qty
     * @return Quote
     */
    private function createQuote(string $postcode, int $qty): Quote
    {
        $quote = $this->objectManager->get(QuoteFactory::class)->create();
        $quote->setStoreId(1)->setCustomerIsGuest(true)->setCustomerGroupId(0);
        $product = $this->objectManager->get(ProductRepositoryInterface::class)->get(self::SKU, false, 1, true);
        $quote->addProduct($product, $qty);
        $quote->getShippingAddress()
            ->setCountryId('BR')
            ->setPostcode($postcode)
            ->setCity('Test');

        return $quote;
    }

    /**
     * Collect shipping rates of the quote.
     *
     * @param Quote $quote
     * @return array<string, array{carrier: string, method: string, price: float}>
     */
    private function collectRates(Quote $quote): array
    {
        $address = $quote->getShippingAddress();
        $address->setCollectShippingRates(true);
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        $rates = [];
        foreach ($address->getAllShippingRates() as $rate) {
            $rates[$rate->getCode()] = [
                'carrier' => (string)$rate->getCarrierTitle(),
                'method' => (string)$rate->getMethodTitle(),
                'price' => (float)$rate->getPrice(),
            ];
        }

        return $rates;
    }

    /**
     * Create a carrier.
     *
     * @param string $name
     * @param bool $active
     * @return CarrierInterface
     */
    private function createCarrier(string $name, bool $active = true): CarrierInterface
    {
        $carrier = $this->objectManager->get(CarrierInterfaceFactory::class)->create();
        $carrier->setName($name)->setIsActive($active)->setSortOrder(0);

        return $this->objectManager->get(CarrierRepositoryInterface::class)->save($carrier);
    }

    /**
     * Create an active rule whose conditions must all be true.
     *
     * @param string $name
     * @param array $conditions child conditions (type, attribute, operator, value)
     * @param float|null $amount
     * @return Rule
     */
    private function createRule(string $name, array $conditions, ?float $amount = null): Rule
    {
        $post = ['1' => ['type' => Combine::class, 'aggregator' => 'all', 'value' => '1', 'new_child' => '']];
        foreach (array_values($conditions) as $index => $condition) {
            $post['1--' . ($index + 1)] = $condition;
        }

        /** @var Rule $rule */
        $rule = $this->objectManager->get(RuleFactory::class)->create();
        $rule->setName($name)->setIsActive(true)->setAmount($amount);
        $rule->loadPost(['conditions' => $post]);

        $this->objectManager->get(RuleRepositoryInterface::class)->save($rule);

        return $rule;
    }

    /**
     * "Shipping Postcode is between ..." condition.
     *
     * @param string $range
     * @return array
     */
    private function postcodeBetween(string $range): array
    {
        return ['type' => Address::class, 'attribute' => 'postcode', 'operator' => '><', 'value' => $range];
    }

    /**
     * Create an active custom method for all store views.
     *
     * @param CarrierInterface $carrier
     * @param string $name
     * @param int[] $ruleIds
     * @param string $calculationType
     * @param float|null $amount
     * @return MethodInterface
     */
    private function createCustomMethod(
        CarrierInterface $carrier,
        string $name,
        array $ruleIds,
        string $calculationType = MethodInterface::CALCULATION_FIXED,
        ?float $amount = null
    ): MethodInterface {
        $method = $this->objectManager->get(CustomMethodInterfaceFactory::class)->create();
        $method->setCarrierId((int)$carrier->getId())
            ->setName($name)
            ->setIsActive(true)
            ->setCalculationType($calculationType)
            ->setAmount($amount)
            ->setStoreIds([0])
            ->setRuleIds($ruleIds);

        return $this->objectManager->get(CustomMethodRepositoryInterface::class)->save($method);
    }

    /**
     * Create an active existing method for all store views.
     *
     * @param string $action
     * @param string[] $shippingMethods
     * @param int[] $ruleIds
     * @param float|null $amount
     * @param string|null $methodTitle
     * @return void
     */
    private function createExistingMethod(
        string $action,
        array $shippingMethods,
        array $ruleIds,
        ?float $amount = null,
        ?string $methodTitle = null
    ): void {
        $method = $this->objectManager->get(ExistingMethodInterfaceFactory::class)->create();
        $method->setName('Test ' . $action)
            ->setIsActive(true)
            ->setActionType($action)
            ->setCalculationType(MethodInterface::CALCULATION_FIXED)
            ->setAmount($amount)
            ->setMethodTitle($methodTitle)
            ->setShippingMethods($shippingMethods)
            ->setStoreIds([0])
            ->setRuleIds($ruleIds);

        $this->objectManager->get(ExistingMethodRepositoryInterface::class)->save($method);
    }
}
