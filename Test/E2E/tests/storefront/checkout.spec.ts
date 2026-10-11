import { expect, test } from '@playwright/test';
import { openAdmin, uid } from '../../helpers/admin';
import { createCarrier, createCustomMethod, createRule } from '../../helpers/fixtures';
import { ConditionType } from '../../helpers/rules';
import { checkoutRate, price, startGuestCheckout } from '../../helpers/storefront';

test.describe('Storefront checkout', () => {
    test('a guest sees the custom method and places an order with it', async ({ page, browser }) => {
        const id = uid();
        const carrier = `Our Delivery ${id}`;
        const method = `Express ${id}`;
        await createCarrier(page, carrier);
        await createRule(page, `SP ${id}`, [
            { type: ConditionType.address('postcode'), operator: 'is between', value: '01000-000-05999-999' },
        ]);
        await createCustomMethod(page, { name: method, carrier, rules: [`SP ${id}`], amount: '7.9' });

        const { context: guest, shop } = await startGuestCheckout(browser, `guest-${id}@example.com`, '01310-100');

        const row = checkoutRate(shop, method);
        await expect(row).toContainText(carrier, { timeout: 60_000 });
        await expect(row).toContainText(price('7.90'));
        await row.locator('input[type="radio"]').check();
        await shop.locator('#shipping-method-buttons-container button.continue').click();

        // Check / Money order is the only payment method, so Luma selects it and hides the radio.
        const placeOrder = shop.locator('button.action.primary.checkout:visible');
        await expect(placeOrder).toBeVisible({ timeout: 120_000 });
        await expect(shop.locator('.opc-block-summary .totals.shipping')).toContainText(price('7.90'));
        await expect(shop.locator('.opc-block-summary .totals.shipping')).toContainText(`${carrier} - ${method}`);
        await expect(shop.locator('.opc-block-summary .grand.totals')).toContainText(price('107.90'));
        await placeOrder.click();

        await expect(shop.locator('.checkout-success')).toBeVisible({ timeout: 120_000 });
        const orderNumber = (await shop.locator('.checkout-success .order-number, .checkout-success p span').first()
            .innerText()).trim();
        await guest.close();

        // The order shows the module carrier in the admin.
        await openAdmin(page, 'sales/order/');
        const orderRow = page.locator('tr.data-row').filter({ hasText: orderNumber });
        await orderRow.locator('a').filter({ hasText: 'View' }).click();
        await expect(page.locator('.order-shipping-method')).toContainText(carrier, { timeout: 120_000 });
        await expect(page.locator('.order-shipping-method')).toContainText(method);
    });
});
