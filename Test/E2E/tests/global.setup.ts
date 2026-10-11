import { expect, test as setup } from '@playwright/test';
import { mkdirSync, writeFileSync } from 'fs';
import { ADMIN_STATE } from '../playwright.config';
import { uid } from '../helpers/admin';
import { createProduct, DATA_FILE, TestData } from '../helpers/fixtures';

setup('log in to the admin and create the test product', async ({ page, request }) => {
    await page.goto('/index.php/admin/');
    await page.locator('#username').fill('e2e');
    await page.locator('#login').fill('E2eTests123');
    await page.locator('.action-login').click();
    await expect(page.locator('.page-title')).toHaveText(/Dashboard/, { timeout: 120_000 });

    mkdirSync('.auth', { recursive: true });
    await page.context().storageState({ path: ADMIN_STATE });

    const sku = `e2e-${uid()}`;
    const data: TestData = { sku, productId: await createProduct(request, sku) };
    writeFileSync(DATA_FILE, JSON.stringify(data));
});
