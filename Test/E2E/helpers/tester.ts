import { expect, Locator, Page } from '@playwright/test';
import { openAdmin, waitForAdmin } from './admin';
import { testData } from './fixtures';

/** Rule IDs to add to or remove from the rules active in the simulation. */
export interface SimulatedRules {
    enable?: string[];
    disable?: string[];
}

/**
 * Run the Rule Tester for two units of the test product shipped to a São Paulo postcode,
 * optionally changing which rules are active in the simulation.
 */
export async function runTester(page: Page, postcode: string, rules: SimulatedRules = {}): Promise<void> {
    await openAdmin(page, 'easyshippingrules/tester/');
    await expect(page.locator('.page-title')).toHaveText('Rule Tester');
    await page.locator('#esr-items').fill(`${testData().sku}, 2`);
    await page.locator('#esr-group').selectOption({ label: 'NOT LOGGED IN' });
    await page.locator('#esr-country').selectOption('BR');
    await page.locator('#esr-region').fill('SP');
    await page.locator('#esr-postcode').fill(postcode);
    if (rules.enable?.length || rules.disable?.length) {
        const select = page.locator('#esr-rules');
        const selected = await select.evaluate((el: HTMLSelectElement) => Array.from(el.selectedOptions, (o) => o.value));
        await select.selectOption([
            ...selected.filter((value) => !rules.disable?.includes(value)),
            ...(rules.enable ?? []),
        ]);
    }
    await page.locator('.esr-tester button[type="submit"]').click();
    await waitForAdmin(page);
}

type Section = 'Shipping Rates' | 'Rules' | 'Custom Methods' | 'Existing Methods';

/** Table of a result section. */
export function testerTable(page: Page, section: Section): Locator {
    return page.locator('section').filter({ has: page.locator('.admin__page-section-title', { hasText: section }) })
        .locator('table');
}

/** Result row of a section that mentions the given text (rate, rule or method name). */
export function testerRow(page: Page, section: Section, text: string): Locator {
    return testerTable(page, section).locator('tbody tr').filter({ hasText: text });
}

/** Assert whether a custom or existing method was applied (the "Applied" column). */
export async function expectApplied(
    page: Page,
    section: 'Custom Methods' | 'Existing Methods',
    name: string,
    applied: boolean
): Promise<void> {
    await expect(testerRow(page, section, name).locator('td').nth(2)).toHaveText(applied ? 'Yes' : 'No');
}
