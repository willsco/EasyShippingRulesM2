import { Browser, expect, Page, test } from '@playwright/test';
import { adminUrl, expectError, filterGrid, gridRows, openAdmin, openGrid, uid, waitForAdmin } from '../../helpers/admin';
import { testData } from '../../helpers/fixtures';

const SALES_RULE_ADDRESS = 'Magento\\SalesRule\\Model\\Rule\\Condition\\Address';
const ESR = 'WillSco\\EasyShippingRulesM2\\Model\\Rule\\Condition';

/** A fresh admin session for "e2e-tester", whose role only allows the Rule Tester. */
async function loginAsTesterOnly(browser: Browser): Promise<Page> {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();
    await page.goto(adminUrl(''));
    await page.locator('#username').fill('e2e-tester');
    await page.locator('#login').fill('E2eTests123');
    await page.locator('.action-login').click();
    await waitForAdmin(page);
    return page;
}

async function formKey(page: Page): Promise<string> {
    return page.evaluate(() => (window as unknown as { FORM_KEY: string }).FORM_KEY);
}

/** Fill the cart part of the Rule Tester form. */
async function fillTesterCart(page: Page): Promise<void> {
    await page.locator('#esr-items').fill(`${testData().sku}, 1`);
    await page.locator('#esr-country').selectOption('BR');
    await page.locator('#esr-postcode').fill('01310-100');
}

test.describe('Security', () => {
    test('only users who manage customers can simulate a customer by email', async ({ page, browser }) => {
        const email = `nobody-${uid()}@example.com`;

        await openAdmin(page, 'easyshippingrules/tester/');
        await fillTesterCart(page);
        await page.locator('#esr-email').fill(email);
        await page.locator('.esr-tester button[type="submit"]').click();
        await waitForAdmin(page);
        await expectError(page, `No customer with email "${email}"`);

        const tester = await loginAsTesterOnly(browser);
        await tester.goto(adminUrl('easyshippingrules/tester/'));
        await waitForAdmin(tester);
        await expect(tester.locator('.page-title')).toHaveText('Rule Tester');
        await expect(tester.locator('#esr-email')).toHaveCount(0);

        // Posting the email anyway does not look the customer up: the cart is simulated as a guest.
        await fillTesterCart(tester);
        await tester.locator('.esr-tester').evaluate((form, value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'customer_email';
            input.value = value;
            form.appendChild(input);
        }, email);
        await tester.locator('.esr-tester button[type="submit"]').click();
        await waitForAdmin(tester);
        await expect(tester.locator('.message-error')).toHaveCount(0);
        await expect(tester.locator('.admin__page-section-title', { hasText: 'Shipping Rates' })).toBeVisible();

        // And the rule screens stay closed to this role.
        await tester.goto(adminUrl('easyshippingrules/rule/'));
        await waitForAdmin(tester);
        await expect(tester.locator('body')).toContainText('Sorry, you need permissions to view this content.');
        await tester.context().close();
    });

    test('the condition editor only creates shipping rule conditions', async ({ page }) => {
        await openAdmin(page, 'easyshippingrules/rule/new/');
        const form = {
            form_key: await formKey(page),
            id: '1--1',
            form_namespace: 'easyshippingrules_rule_form',
        };
        const url = adminUrl('easyshippingrules/rule/newConditionHtml/form/rule_conditions_fieldset_');

        const allowed = await page.request.post(url, {
            form: { ...form, type: `${ESR}\\Address|postcode` },
        });
        expect(await allowed.text()).toContain('rule[conditions][1--1]');

        const refused = await page.request.post(url, {
            form: { ...form, type: SALES_RULE_ADDRESS },
        });
        expect(await refused.text()).toBe('');
    });

    test('a rule with a condition from outside the module is not saved', async ({ page }) => {
        const name = `Foreign ${uid()}`;
        await openAdmin(page, 'easyshippingrules/rule/new/');
        await page.request.post(adminUrl('easyshippingrules/rule/save/'), {
            form: {
                form_key: await formKey(page),
                name,
                is_active: '1',
                'rule[conditions][1][type]': `${ESR}\\Combine`,
                'rule[conditions][1][aggregator]': 'all',
                'rule[conditions][1][value]': '1',
                'rule[conditions][1--1][type]': SALES_RULE_ADDRESS,
                'rule[conditions][1--1][attribute]': 'postcode',
                'rule[conditions][1--1][operator]': '==',
                'rule[conditions][1--1][value]': '01310100',
            },
            maxRedirects: 0,
        });

        await openAdmin(page, 'easyshippingrules/rule/new/');
        await expectError(page, 'The conditions contain a type that shipping rules do not support.');
        // The rejected condition is not shown again in the editor.
        await expect(page.locator('[name="rule[conditions][1--1][value]"]')).toHaveCount(0);

        await openGrid(page, 'rule');
        await filterGrid(page, { name });
        await expect(gridRows(page)).toHaveCount(0);
    });
});
