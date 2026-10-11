import { Browser, BrowserContext, expect, Locator, Page } from '@playwright/test';
import { testData } from './fixtures';

export interface GuestCheckout {
    context: BrowserContext;
    shop: Page;
}

/** Add the test product to a guest cart and fill the shipping address up to the postcode. */
export async function startGuestCheckout(browser: Browser, email: string, postcode: string): Promise<GuestCheckout> {
    // A fresh context: a guest without the admin session.
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const shop = await context.newPage();

    await shop.goto(`/index.php/catalog/product/view/id/${testData().productId}`);
    await shop.locator('#product-addtocart-button').click();
    await expect(shop.locator('.message-success')).toContainText('You added', { timeout: 60_000 });

    await shop.goto('/index.php/checkout/');
    const shipping = shop.locator('#shipping');
    await expect(shipping.locator('#customer-email')).toBeVisible({ timeout: 120_000 });
    await shipping.locator('#customer-email').fill(email);
    await shipping.locator('input[name="firstname"]').fill('Ana');
    await shipping.locator('input[name="lastname"]').fill('Silva');
    await shipping.locator('input[name="street[0]"]').fill('Av. Paulista, 1000');
    await shipping.locator('select[name="country_id"]').selectOption('BR');
    await shipping.locator('select[name="region_id"]').selectOption({ label: 'São Paulo' });
    await shipping.locator('input[name="city"]').fill('São Paulo');
    await shipping.locator('input[name="telephone"]').fill('11999999999');
    await setCheckoutPostcode(shop, postcode);

    return { context, shop };
}

/** Change the shipping postcode; the checkout estimates the rates again on its own. */
export async function setCheckoutPostcode(shop: Page, postcode: string): Promise<void> {
    const input = shop.locator('#shipping input[name="postcode"]');
    await input.fill(postcode);
    await input.blur();
}

/** Shipping rate rows of the checkout. */
export function checkoutRate(shop: Page, method: string): Locator {
    return shop.locator('#checkout-shipping-method-load tr').filter({ hasText: method });
}

/** A storefront price in any locale: `price('9.90')` matches "$9.90" and "R$ 9,90". */
export function price(amount: string): RegExp {
    const [units, cents] = amount.split('.');
    return new RegExp(`(^|[^\d.,])${units}[.,]${cents}(?!\d)`);
}
