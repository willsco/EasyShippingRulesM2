import { expect, test } from '@playwright/test';
import {
    acceptModal,
    clickFormButton,
    expectError,
    expectFieldError,
    expectSuccess,
    field,
    fillField,
    filterGrid,
    gridRow,
    massAction,
    openAdmin,
    openGrid,
    rowAction,
    saveForm,
    setToggle,
    uid,
    waitForAdmin,
} from '../../helpers/admin';
import { createCarrier } from '../../helpers/fixtures';

test.describe('Carriers', () => {
    test('creates a carrier, then edits it with "Save and Continue Edit"', async ({ page }) => {
        const name = `Carrier ${uid()}`;
        await openGrid(page, 'carrier');
        await page.locator('#add').click();
        await waitForAdmin(page);
        await expect(page.locator('.page-title')).toHaveText('New Carrier');

        await fillField(page, 'name', name);
        await fillField(page, 'sort_order', '7');
        await saveForm(page, true);
        await expectSuccess(page, 'The carrier has been saved.');
        await expect(page).toHaveURL(/carrier\/edit\/id\/\d+/);
        await expect(page.locator('.page-title')).toHaveText(`Edit Carrier "${name}"`);
        await expect(field(page, 'sort_order').locator('input')).toHaveValue('7');

        await setToggle(page, 'is_active', false);
        await fillField(page, 'name', `${name} edited`);
        await saveForm(page);
        await expectSuccess(page, 'The carrier has been saved.');

        await filterGrid(page, { name: `${name} edited` });
        const row = gridRow(page, `${name} edited`);
        await expect(row).toContainText('Disabled');
        await expect(row).toContainText('7');
    });

    test('requires a name', async ({ page }) => {
        await openAdmin(page, 'easyshippingrules/carrier/new/');
        await clickFormButton(page, 'save');
        await expectFieldError(page, 'name');
        await expect(page).toHaveURL(/carrier\/new/);
    });

    test('rejects a duplicate name and keeps the typed data', async ({ page }) => {
        const name = `Carrier ${uid()}`;
        await createCarrier(page, name);

        await openAdmin(page, 'easyshippingrules/carrier/new/');
        await fillField(page, 'name', name);
        await fillField(page, 'sort_order', '3');
        await saveForm(page);

        await expectError(page, `A carrier named "${name}" already exists.`);
        await expect(field(page, 'name').locator('input')).toHaveValue(name);
        await expect(field(page, 'sort_order').locator('input')).toHaveValue('3');
    });

    test('edits a row inline in the grid', async ({ page }) => {
        const name = `Carrier ${uid()}`;
        await createCarrier(page, name, 1);

        const row = gridRow(page, name);
        await row.locator('td').nth(2).click();
        // The row turns into inputs; the grid also keeps a hidden editor template.
        await page.locator('.admin__data-grid-wrap input[name="sort_order"]:visible').fill('42');
        await page.locator('.data-grid-editable-row-actions button:visible').filter({ hasText: 'Save' }).click();
        await waitForAdmin(page);

        await expect(gridRow(page, name)).toContainText('42');
    });

    test('disables carriers with a mass action', async ({ page }) => {
        const name = `Carrier ${uid()}`;
        await createCarrier(page, name);

        await massAction(page, [gridRow(page, name)], 'Change Status', 'Disabled');
        await waitForAdmin(page);
        await expectSuccess(page, 'A total of 1 record(s) have been updated.');
        await filterGrid(page, { name });
        await expect(gridRow(page, name)).toContainText('Disabled');
    });

    test('deletes a carrier from the grid', async ({ page }) => {
        const name = `Carrier ${uid()}`;
        await createCarrier(page, name);

        await rowAction(page, gridRow(page, name), 'Delete');
        await acceptModal(page);
        await waitForAdmin(page);
        await expectSuccess(page, 'The carrier has been deleted.');
        await filterGrid(page, { name });
        await expect(gridRow(page, name)).toHaveCount(0);
    });

    test('deletes a carrier from its form', async ({ page }) => {
        const name = `Carrier ${uid()}`;
        await createCarrier(page, name);

        await rowAction(page, gridRow(page, name), 'Edit');
        await waitForAdmin(page);
        await clickFormButton(page, 'delete');
        await acceptModal(page);
        await waitForAdmin(page);
        await expectSuccess(page, 'The carrier has been deleted.');
    });

    test('deletes carriers with a mass action', async ({ page }) => {
        const prefix = `Bulk ${uid()}`;
        await createCarrier(page, `${prefix} A`);
        await createCarrier(page, `${prefix} B`);

        await filterGrid(page, { name: prefix });
        await massAction(page, [gridRow(page, `${prefix} A`), gridRow(page, `${prefix} B`)], 'Delete');
        await acceptModal(page);
        await waitForAdmin(page);
        await expectSuccess(page, 'A total of 2 record(s) have been deleted.');
    });
});
