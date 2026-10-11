import { expect, test } from '@playwright/test';
import { uid } from '../../helpers/admin';
import { createCarrier, createCustomMethod, createRule } from '../../helpers/fixtures';
import { ConditionType } from '../../helpers/rules';
import { checkoutRate, price, setCheckoutPostcode, startGuestCheckout } from '../../helpers/storefront';

test.describe('Storefront postcode ranges', () => {
    test('the checkout price follows the range of the postcode', async ({ page, browser }) => {
        const id = uid();
        const carrier = `Ranges ${id}`;
        const method = `Delivery ${id}`;
        await createCarrier(page, carrier);
        await createRule(page, `Capital ${id}`, [
            { type: ConditionType.address('postcode'), operator: 'is between', value: '01000-000-05999-999' },
        ], '9.9');
        await createRule(page, `Countryside ${id}`, [
            { type: ConditionType.address('postcode'), operator: 'is between', value: '13000000-19999999' },
        ], '19.9');
        await createCustomMethod(page, { name: method, carrier, rules: [`Capital ${id}`, `Countryside ${id}`] });

        const { context, shop } = await startGuestCheckout(browser, `ranges-${id}@example.com`, '01310-100');
        const rate = checkoutRate(shop, method);
        await expect(rate).toContainText(price('9.90'), { timeout: 60_000 });

        // Typed without the hyphen, compared with a range saved without it.
        await setCheckoutPostcode(shop, '13010000');
        await expect(rate).toContainText(price('19.90'), { timeout: 60_000 });

        await setCheckoutPostcode(shop, '20040-020');
        await expect(rate).toHaveCount(0, { timeout: 60_000 });
        await expect(checkoutRate(shop, 'Flat Rate')).toBeVisible();

        await context.close();
    });
});
