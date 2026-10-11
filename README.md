# Easy Shipping Rules for Magento 2

Create your own shipping methods and change the rates of the ones you already have, based on what is
in the cart, where it is going and who is buying, all from the admin, without writing code.

- [What you can do](#what-you-can-do)
- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Admin reference](#admin-reference)
- [How prices are calculated](#how-prices-are-calculated)
- [Developers](#developers)
- [Troubleshooting](#troubleshooting)
- [Uninstall](#uninstall)
- [License](#license)
- [Credits](#credits)

## What you can do

**Offer new shipping methods**
- "Express Delivery – $25" only for postcodes of your city.
- "Motorcycle courier" only when the cart weighs less than 5 kg.
- "Store pickup" only for logged-in customers of a given group.

**Change the rates of the carriers you already use** (Flat Rate, Table Rates, UPS, Correios…)
- Free shipping on Flat Rate for orders above $200.
- 10% discount on every rate for a specific customer group.
- $15 surcharge on all methods when the cart contains a bulky product.
- Hide a method for regions it does not serve.
- Rename a carrier or method title shown at checkout.

**Check your setup before customers do**
- The *Rule Tester* rates a sample cart and shows which rules are valid, which methods were added,
  changed or hidden, and why each method did or did not apply.

## How it works

| Concept | What it is |
| --- | --- |
| **Rule** | A set of conditions, built like a Cart Price Rule. A rule is either valid or not for the current cart. |
| **Carrier** | A group of custom methods. Its name is the carrier title shown at checkout. |
| **Custom Method** | A new shipping rate. It is offered when **at least one** of its rules is valid. |
| **Existing Method** | An action on rates returned by other carriers: overwrite, discount, surcharge, hide or rename. It runs when **at least one** of its rules is valid. |

Rules are evaluated after all carriers have returned their rates, so they apply wherever Magento
calculates shipping: cart estimate, checkout, multishipping, admin order creation and the REST and
GraphQL APIs.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.7 or later
- PHP 8.2 or 8.3

## Installation

### Composer (recommended)

```bash
composer require willsco/module-easyshippingrulesm2
bin/magento module:enable WillSco_EasyShippingRulesM2
bin/magento setup:upgrade
bin/magento setup:di:compile      # production mode only
bin/magento setup:static-content:deploy   # production mode only
bin/magento cache:flush
```

### Manual (zip)

1. Extract the package into `app/code/WillSco/EasyShippingRulesM2`.
2. Run the same `bin/magento` commands as above, starting at `module:enable`.

After installation, the menu **Stores > Easy Shipping Rules** is available to admin users whose role
includes the *Easy Shipping Rules* resources (System > Permissions > User Roles).

## Quick start

Example: free "Express Delivery" for orders of $200 or more in São Paulo city
(postcodes 01000-000 to 05999-999).

1. **Create the rule.** Go to *Stores > Easy Shipping Rules > Rules > Add New Rule*.
   - Rule Name: `São Paulo, $200+`
   - Conditions: *If ALL of these conditions are TRUE*
     - *Subtotal* **equals or greater than** `200`
     - *Shipping Postcode* **is between** `01000-000-05999-999`
   - Save.
2. **Create the carrier.** Go to *Carriers > Add New Carrier*. Name: `Our Delivery`. Save.
3. **Create the method.** Go to *Custom Methods > Add New Custom Method*.
   - Name: `Express Delivery`
   - Carrier: `Our Delivery`
   - Rules: `São Paulo, $200+`
   - Calculation Type: *Fixed (whole cart)*. Price / Percentage: `0`
   - Save.
4. Add products to the cart, estimate shipping with a São Paulo postcode, and
   "Our Delivery – Express Delivery" appears for free.

## Admin reference

### Rules

| Field | Description |
| --- | --- |
| Enabled | Disabled rules are never valid. |
| Rule Name / Description | For your reference. |
| Conditions | When the rule is valid. Leave empty to make the rule always valid. |
| Price / Percentage | Used by methods that have no price of their own. See [How prices are calculated](#how-prices-are-calculated). |

**Available conditions**

| Group | Examples |
| --- | --- |
| Cart Attribute | subtotal, total quantity, weight, shipping postcode, region, country, payment method |
| Product Attribute Combination | "If an item is FOUND / NOT FOUND in the cart with ALL / ANY of these conditions true": any product attribute (SKU, category, attribute set, custom attributes) and cart item price, quantity and row total |
| Customer Group | "Customer Group **is one of** / **is not one of** ...", including *NOT LOGGED IN* for guests |
| Customer Attribute | any customer attribute (gender, date of birth, custom attributes). Guests never match. |
| Shipping Rule | "Shipping Rule **is valid** / **is not valid** 3,7". Reuse rules inside other rules. |
| Conditions Combination | nest ALL / ANY groups |
| Programmatic Condition | conditions written in code by a developer (shown only when at least one is installed) |

As in Cart Price Rules, a product attribute is listed under *Product Attribute Combination* only when
*Use for Promo Rule Conditions* is *Yes* in *Stores > Attributes > Product*. Cart item price, quantity
and row total are always available.

**"is between" / "is not between".** Text and number attributes accept a range written as
`min-max`:

| Value | Matches |
| --- | --- |
| `100-500` | numbers from 100 to 500, inclusive |
| `1,5-3,5` | decimal comma is accepted |
| `01000000-01999999` | postcodes typed with or without hyphen |
| `01000-000-01999-999` | hyphenated postcodes |

### Carriers

| Field | Description |
| --- | --- |
| Enabled | Methods of a disabled carrier are not offered. |
| Name | Carrier title at checkout. Must be unique. |
| Position | Sort order of the carrier among the shipping options. |

### Custom Methods

| Field | Description |
| --- | --- |
| Enabled | |
| Name | Method title at checkout. |
| Carrier | Carrier the method is shown under. |
| Store View | Where the method is available. |
| Rules | The method is offered when at least one of these rules is valid. |
| Calculation Type | *Fixed (whole cart)*, *Fixed (per item qty)* or *Percentage of subtotal*. |
| Price / Percentage | Leave empty to use the value of the rule. `0` means free. |

Custom methods cost `0` when a Cart Price Rule grants free shipping to the shipment, as Table Rates and
the online carriers do.

### Existing Methods

| Field | Description |
| --- | --- |
| Enabled | |
| Name | Internal name, not shown to customers. |
| Store View | Where the action applies. |
| Rules | The action applies when at least one of these rules is valid. |
| Shipping Methods | Methods to change, or *All Shipping Methods* (including custom methods). |
| Action | *Overwrite price*, *Discount* (subtract, never below zero), *Surcharge* (add) or *Hide*. |
| Calculation Type | As for custom methods, plus *Percentage of rate* (of the original price). |
| Price / Percentage | Leave empty to use the value of the rule. *Overwrite price* requires it unless every linked rule has a value; if no value is found at checkout, the rate keeps its price. |
| Carrier Title / Method Title | Optional. Replace the titles shown at checkout. |

Several existing methods can apply to the same rate. They run in ID order, each one starting from the
result of the previous one.

### Rule Tester

*Stores > Easy Shipping Rules > Rule Tester*

Describe a cart (store view, products as `SKU, qty` lines, customer email or customer group, and the
shipping address) and click *Run Test*. Nothing is saved. The result shows:

- **Shipping Rates**: every rate with and without this module, marked *Added*, *Changed*, *Unchanged*
  or *Hidden*;
- **Rules**: whether each rule is valid for that cart;
- **Custom Methods** and **Existing Methods**: whether each one applied, and the reason when it did
  not (disabled, other store view, no valid rule, carrier disabled, Default Rule not valid).

The products must exist and be salable in the chosen store view. *Customer Email* is shown only to
admin users who may manage customers (*Customers > All Customers*), since the lookup tells whether an
email is registered.

*Rules Active in the Simulation* lists every rule, disabled ones marked *(disabled)*, with the enabled
rules selected. Select a disabled rule to try it before enabling it, or unselect an enabled rule to
see the cart without it. The simulation uses exactly the selected rules (also in *Shipping Rule*
conditions of other rules) and marks the changed ones in the result. Rules keep their status in the
store.

### Configuration

*Stores > Configuration > Sales > Easy Shipping Rules*

| Setting | Scope | Description |
| --- | --- | --- |
| Default Rule | Default, Website | When set, no custom method is offered unless this rule is valid. Existing methods are not affected. |

## How prices are calculated

1. **Amount**: the *Price / Percentage* of the method. If it is empty, the *Price / Percentage* of
   the first valid rule (lowest ID) is used instead. If neither is set, the amount is 0.
2. **Price**: the amount according to the *Calculation Type*:

   | Calculation Type | Price |
   | --- | --- |
   | Fixed (whole cart) | amount |
   | Fixed (per item qty) | amount × number of items |
   | Percentage of subtotal | subtotal × amount / 100 |
   | Percentage of rate | original rate × amount / 100 (existing methods only) |

3. **Existing methods** then apply their action to the original rate: overwrite it with the price,
   subtract it (minimum 0) or add it.

Prices are in the base currency. The subtotal follows the *Catalog Prices* tax setting (including or
excluding tax), as for Magento's own carriers.

## Developers

### Programmatic conditions

For logic that cannot be expressed with attributes (external APIs, stock rules…), implement
`WillSco\EasyShippingRulesM2\Api\ProgrammaticConditionInterface`:

```php
class VipCustomerCondition implements \WillSco\EasyShippingRulesM2\Api\ProgrammaticConditionInterface
{
    public function getLabel(): string
    {
        return 'Customer is VIP';
    }

    public function isValid(\Magento\Quote\Api\Data\AddressInterface $address): bool
    {
        // your logic
    }
}
```

and register it in your module's `etc/di.xml`:

```xml
<type name="WillSco\EasyShippingRulesM2\Model\Rule\ProgrammaticConditionPool">
    <arguments>
        <argument name="conditions" xsi:type="array">
            <item name="vip_customer" xsi:type="object">Vendor\Module\Model\VipCustomerCondition</item>
        </argument>
    </arguments>
</type>
```

It then appears in the rule editor as *Programmatic Condition is valid / is not valid: Customer is VIP*.

### Service contracts

| Repository | Data interface |
| --- | --- |
| `Api\CarrierRepositoryInterface` | `Api\Data\CarrierInterface` |
| `Api\RuleRepositoryInterface` | `Api\Data\RuleInterface` |
| `Api\CustomMethodRepositoryInterface` | `Api\Data\CustomMethodInterface` |
| `Api\ExistingMethodRepositoryInterface` | `Api\Data\ExistingMethodInterface` |

All repositories provide `save`, `getById`, `getList(SearchCriteria)`, `delete` and `deleteById`.
Models dispatch the standard save and delete events with the prefixes `willsco_esr_carrier`,
`willsco_esr_rule`, `willsco_esr_custom_method` and `willsco_esr_existing_method`
(e.g. `willsco_esr_rule_save_after`).

### Shipping codes

Custom method rates use the carrier code `easyshippingrules{carrierId}` and the method code
`m{methodId}`, so the shipping method of an order looks like `easyshippingrules3_m12`.

### Database

| Table | Content |
| --- | --- |
| `willsco_esr_carrier` | carriers |
| `willsco_esr_rule` | rules (conditions stored as JSON) |
| `willsco_esr_custom_method` | custom methods |
| `willsco_esr_existing_method` | existing methods (target shipping methods stored as JSON) |
| `willsco_esr_custom_method_store`, `willsco_esr_existing_method_store` | store view links |
| `willsco_esr_custom_method_rule`, `willsco_esr_existing_method_rule` | rule links |

### Tests

Unit tests (`Test/Unit`) and integration tests (`Test/Integration`) run with Magento's standard setup:

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/WillSco/EasyShippingRulesM2/Test/Unit

cd dev/tests/integration
../../../vendor/bin/phpunit -c phpunit.xml.dist ../../../app/code/WillSco/EasyShippingRulesM2/Test/Integration
```

The integration tests rate real quotes against Flat Rate and the module's carriers, rules and methods.

End-to-end browser tests (Playwright) cover every admin screen and a storefront checkout. They run
against Magento in Docker with one command; see [Test/E2E/README.md](Test/E2E/README.md).

GitHub Actions run the Magento Coding Standard, the unit tests on PHP 8.2 and 8.3, the integration
tests against Magento 2.4.7 (`.github/workflows/ci.yml`) and the end-to-end suite (`.github/workflows/e2e.yml`). The `Test/E2E`
folder and the workflows are excluded from the distributed package.

## Troubleshooting

Start with the [Rule Tester](#rule-tester): it explains why each method did or did not apply.

**A custom method does not show up.** Check that:
- the method, its carrier and at least one of its rules are enabled;
- the method is assigned to the current store view (or *All Store Views*);
- the rule conditions match the cart (try a rule with no conditions first);
- the *Default Rule* (if configured) is valid for the cart.

**A rate is not changed by an existing method.** Check that the right method is selected in
*Shipping Methods*. The list only shows carriers that are enabled in
*Stores > Configuration > Sales > Delivery Methods*.

**Errors.** Problems while applying rules are written to `var/log/system.log` with the prefix
`Easy Shipping Rules:`. They never break the checkout: the other shipping methods are still shown.

## Uninstall

Composer installation (removes the code and the module tables):

```bash
bin/magento module:uninstall WillSco_EasyShippingRulesM2 --remove-data
```

Manual installation: run `bin/magento module:disable WillSco_EasyShippingRulesM2`, delete
`app/code/WillSco/EasyShippingRulesM2`, run `bin/magento setup:upgrade`, then drop the
`willsco_esr_*` tables if you no longer need the data.

## License

[Open Software License 3.0](https://opensource.org/licenses/OSL-3.0). See [LICENSE.txt](LICENSE.txt)
and [COPYING.txt](COPYING.txt).

## Credits

Based on [Easy Shipping Rules](https://github.com/matheusgontijo/easy-shipping-rules) for Magento 1,
by Matheus Gontijo and contributors, also licensed under OSL 3.0.
