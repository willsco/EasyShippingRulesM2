import { expect, Locator, Page } from '@playwright/test';

/** Admin URL for a route such as "easyshippingrules/carrier/new". */
export function adminUrl(route: string): string {
    return `/index.php/admin/${route.replace(/^\/+/, '')}`;
}

/** Unique suffix so tests never depend on data left by other runs. */
export function uid(): string {
    return `${Date.now().toString(36)}${Math.floor(Math.random() * 1296).toString(36)}`;
}

/** Wait until Magento's page loader and UI component spinners are gone. */
export async function waitForAdmin(page: Page): Promise<void> {
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('body')).not.toHaveClass(/_has-loader/, { timeout: 120_000 });
    await expect(page.locator('[data-role="spinner"]:visible')).toHaveCount(0, { timeout: 120_000 });
    await expect(page.locator('.admin__data-grid-loading-mask:visible')).toHaveCount(0, { timeout: 120_000 });
    await expect(page.locator('.loading-mask:visible')).toHaveCount(0, { timeout: 120_000 });
}

export async function openAdmin(page: Page, route: string): Promise<void> {
    await page.goto(adminUrl(route));
    await waitForAdmin(page);
}

export async function expectSuccess(page: Page, text: string | RegExp): Promise<void> {
    await expect(page.locator('.message-success').filter({ hasText: text }).first()).toBeVisible();
}

export async function expectError(page: Page, text: string | RegExp): Promise<void> {
    await expect(page.locator('.message-error').filter({ hasText: text }).first()).toBeVisible();
}

/** Confirm the open Magento modal ("OK"). */
export async function acceptModal(page: Page): Promise<void> {
    const modal = page.locator('.modal-popup._show, .modal-inner-wrap:visible').last();
    await modal.locator('button.action-accept, button.action-primary').first().click();
}

// ---------------------------------------------------------------- forms

/** Field wrapper of a UI form field, by its name (data-index). */
export function field(page: Page, name: string): Locator {
    return page.locator(`.admin__field[data-index="${name}"]`).first();
}

export async function fillField(page: Page, name: string, value: string): Promise<void> {
    await field(page, name).locator('input, textarea').first().fill(value);
}

export async function selectField(page: Page, name: string, labels: string | string[]): Promise<void> {
    const select = field(page, name).locator('select').first();
    const wanted = Array.isArray(labels) ? labels : [labels];
    const values: string[] = [];
    for (const label of wanted) {
        const option = select.locator('option').filter({ hasText: label }).first();
        values.push((await option.getAttribute('value')) ?? '');
    }
    await select.selectOption(values);
}

export async function setToggle(page: Page, name: string, on: boolean): Promise<void> {
    const checkbox = field(page, name).locator('input[type="checkbox"]');
    if ((await checkbox.isChecked()) !== on) {
        await field(page, name).locator('label.admin__actions-switch-label').click();
    }
    await expect(checkbox).toBeChecked({ checked: on });
}

export async function clickFormButton(page: Page, id: 'save' | 'save_and_continue' | 'back' | 'delete'): Promise<void> {
    await page.locator(`.page-actions-buttons button#${id}`).click();
}

/** Save a UI form and wait for the redirect it triggers. */
export async function saveForm(page: Page, andContinue = false): Promise<void> {
    await clickFormButton(page, andContinue ? 'save_and_continue' : 'save');
    await page.waitForLoadState('domcontentloaded');
    await waitForAdmin(page);
}

export async function expectFieldError(page: Page, name: string): Promise<void> {
    await expect(field(page, name).locator('.admin__field-error')).toBeVisible();
}

// ---------------------------------------------------------------- grids

/** Clear grid filters (they are stored in the admin user's bookmarks). */
export async function resetGridFilters(page: Page): Promise<void> {
    const clear = page.locator('button[data-action="grid-filter-reset"]:visible').first();
    if (await clear.isVisible()) {
        await clear.click();
        await waitForAdmin(page);
    }
}

export async function filterGrid(page: Page, filters: Record<string, string>): Promise<void> {
    await resetGridFilters(page);
    await page.locator('button[data-action="grid-filter-expand"]').first().click();
    const panel = page.locator('.admin__data-grid-filters');
    for (const [name, value] of Object.entries(filters)) {
        const input = panel.locator(`[name="${name}"]`).first();
        if ((await input.evaluate((el) => el.tagName)) === 'SELECT') {
            await input.selectOption({ label: value });
        } else {
            await input.fill(value);
        }
    }
    // The buttons sit in the panel footer, outside the fieldset that holds the inputs.
    await page.locator('button[data-action="grid-filter-apply"]:visible').click();
    await waitForAdmin(page);
}

export function gridRows(page: Page): Locator {
    return page.locator('.admin__data-grid-wrap tbody tr.data-row');
}

export function gridRow(page: Page, text: string): Locator {
    return gridRows(page).filter({ hasText: text });
}

/** Use the "Select" menu of the actions column. */
export async function rowAction(page: Page, row: Locator, action: 'Edit' | 'Delete'): Promise<void> {
    await row.locator('.action-select-wrap button.action-select').click();
    await row.locator('.action-menu-items a, .action-menu a').filter({ hasText: action }).click();
}

/** Select rows and run a mass action, optionally choosing a sub-action. */
export async function massAction(page: Page, rows: Locator[], action: string, subAction?: string): Promise<void> {
    for (const row of rows) {
        await row.locator('td.data-grid-checkbox-cell input[type="checkbox"]').check();
    }
    const menu = page.locator('.admin__data-grid-header .action-select-wrap').filter({ hasText: 'Actions' }).first();
    await menu.locator('button.action-select').click();
    const item = menu.locator('.action-menu-item').filter({ hasText: action }).first();
    await item.click();
    if (subAction) {
        await menu.locator('.action-submenu .action-menu-item').filter({ hasText: subAction }).first().click();
    }
}

/** ID shown in the first data column of a row. */
export async function rowId(row: Locator): Promise<string> {
    return ((await row.locator('td').nth(1).innerText()) || '').trim();
}

export async function openGrid(page: Page, entity: 'carrier' | 'custommethod' | 'existingmethod' | 'rule'): Promise<void> {
    await openAdmin(page, `easyshippingrules/${entity}/`);
    await resetGridFilters(page);
}
