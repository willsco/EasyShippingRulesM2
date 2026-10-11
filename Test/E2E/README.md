# End-to-end tests

Browser tests ([Playwright](https://playwright.dev)) for every admin screen of the module and for the storefront
checkout, run against a real Magento installed in Docker.

| Spec | Covers |
| --- | --- |
| `admin/carriers.spec.ts` | create, edit, "Save and Continue Edit", validation, duplicate names, inline grid editing, mass status change, delete from grid / form / mass action |
| `admin/rules.spec.ts` | condition editor (between operator, cart / customer group / product combination conditions), removing conditions, "Shipping Rule" chooser, grid actions |
| `admin/custom-methods.spec.ts` | create, validation, editing keeps rules and store views, mass carrier and calculation type changes, delete |
| `admin/existing-methods.spec.ts` | create, edit, mass action change, delete, validation |
| `admin/config.spec.ts` | Default Rule setting |
| `admin/rule-tester.spec.ts` | simulated rates and explanations, rules simulated as enabled or disabled, invalid products |
| `admin/postcode-ranges.spec.ts` | postcode ranges in the Rule Tester: both limits, "is not between", postcodes with or without the hyphen, a price per range, existing methods, invalid ranges rejected on save |
| `admin/security.spec.ts` | customer lookup in the Rule Tester requires access to customers, only shipping rule conditions are accepted by the condition editor and on save |
| `storefront/checkout.spec.ts` | guest checkout with a custom method, order shown in the admin |
| `storefront/postcode-ranges.spec.ts` | the checkout price changes with the range of the postcode typed |

## Running

Requirements: Docker with Compose v2 and about 6 GB of free memory.

```bash
cd Test/E2E/docker
docker compose up -d --build            # first start installs Magento 2.4.7-p10 (10-15 minutes)
docker compose run --rm playwright      # waits until Magento is healthy, then runs every test
```

The module under test is the working copy of this repository, mounted read-only into
`app/code/WillSco/EasyShippingRulesM2`; every start of the `magento` service runs `setup:upgrade`.
After changing PHP code, restart it with `docker compose restart magento`.

Useful variations:

```bash
PW_ARGS="tests/admin/rules.spec.ts" docker compose run --rm playwright   # one spec
PW_ARGS="-g 'inline'" docker compose run --rm playwright                  # tests matching a title
MAGENTO_VERSION=2.4.8 docker compose up -d --build                        # another Magento (fresh volumes)
docker compose down -v                                                    # remove everything
```

Results are written to `test-results/` (screenshots and traces of failures) and `playwright-report/`
(`npx playwright show-report`). Traces open with `npx playwright show-trace <trace.zip>`.

Magento is available at <http://localhost:8080/index.php/admin> (admin / Admin123456, any interface language) while the
environment is up.

## How the tests are written

- Every test creates its own data with unique names (`uid()`), so tests can run in any order and the
  environment can be reused between runs.
- Existing methods change every checkout, so their tests only use rules that match a postcode no other
  test uses.
- `helpers/admin.ts` wraps the Magento UI components (forms, grids, mass actions, modals),
  `helpers/rules.ts` drives the condition editor and `helpers/fixtures.ts` creates data.
- The tests log in as the `e2e` admin user (English interface), created by the environment; the `admin`
  user is left for manual use in any language. `e2e-tester` (password `E2eTests123`) may only open the
  Rule Tester and is used by the permission tests. The environment creates both users, so changes to
  `docker/setup.sh` need `docker compose up -d --build`.
- The environment follows the Magento Functional Testing Framework conventions: secret keys in admin
  URLs, admin captcha and two-factor authentication are disabled.
- The PHP image is based on Debian bookworm (ICU 72): Magento 2.4.7 calendars fail with ICU 76 for
  locales such as pt_BR, de_DE and fr_FR.
