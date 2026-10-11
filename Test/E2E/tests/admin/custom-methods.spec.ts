import { expect, test } from '@playwright/test';
import {
    acceptModal,
    clickFormButton,
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
    selectField,
    uid,
    waitForAdmin,
} from '../../helpers/admin';
import { createCarrier, createCustomMethod, createRule } from '../../helpers/fixtures';

test.describe('Custom Methods', () => {
    test('creates a method and shows it in the grid', async ({ page }) => {
        const id = uid();
        await createCarrier(page, `Carrier ${id}`);
        await createRule(page, `Rule ${id}`);

        await openGrid(page, 'custommethod');
        await page.locator('#add').click();
        await waitForAdmin(page);
        await fillField(page, 'name', `Express ${id}`);
        await selectField(page, 'carrier_id', `Carrier ${id}`);
        await selectField(page, 'store_ids', 'All Store Views');
        await selectField(page, 'rule_ids', `Rule ${id}`);
        await selectField(page, 'calculation_type', 'Fixed (per item qty)');
        await fillField(page, 'amount', '3.5');
        await saveForm(page);
        await expectSuccess(page, 'The custom method has been saved.');

        await filterGrid(page, { name: `Express ${id}` });
        const row = gridRow(page, `Express ${id}`);
        await expect(row).toContainText(`Carrier ${id}`);
        await expect(row).toContainText('Fixed (per item qty)');
        await expect(row).toContainText('3.5');
        await expect(row).toContainText('Enabled');
    });

    test('validates carrier, store view and rules', async ({ page }) => {
        await openAdmin(page, 'easyshippingrules/custommethod/new/');
        await fillField(page, 'name', `Express ${uid()}`);
        await field(page, 'store_ids').locator('select').selectOption([]);
        await clickFormButton(page, 'save');
        await expectFieldError(page, 'carrier_id');
        await expectFieldError(page, 'store_ids');
        await expectFieldError(page, 'rule_ids');
    });

    test('keeps rules and store views when edited', async ({ page }) => {
        const id = uid();
        await createCarrier(page, `Carrier ${id}`);
        await createRule(page, `Rule A ${id}`);
        await createRule(page, `Rule B ${id}`);
        await createCustomMethod(page, { name: `Express ${id}`, carrier: `Carrier ${id}`, rules: [`Rule A ${id}`, `Rule B ${id}`] });

        await rowAction(page, gridRow(page, `Express ${id}`), 'Edit');
        await waitForAdmin(page);
        const selected = await field(page, 'rule_ids').locator('select option:checked').allInnerTexts();
        expect(selected.map((label) => label.trim()).sort()).toEqual([`Rule A ${id}`, `Rule B ${id}`]);
        await expect(field(page, 'store_ids').locator('select option:checked')).toHaveText(['All Store Views']);

        await fillField(page, 'amount', '');
        await saveForm(page, true);
        await expectSuccess(page, 'The custom method has been saved.');
        await expect(field(page, 'amount').locator('input')).toHaveValue('');
    });

    test('changes carrier and calculation type with mass actions', async ({ page }) => {
        const id = uid();
        await createCarrier(page, `First ${id}`);
        await createCarrier(page, `Second ${id}`);
        await createRule(page, `Rule ${id}`);
        await createCustomMethod(page, { name: `Express ${id}`, carrier: `First ${id}`, rules: [`Rule ${id}`] });

        await massAction(page, [gridRow(page, `Express ${id}`)], 'Change Carrier', `Second ${id}`);
        await waitForAdmin(page);
        await expectSuccess(page, 'A total of 1 record(s) have been updated.');
        await filterGrid(page, { name: `Express ${id}` });
        await expect(gridRow(page, `Express ${id}`)).toContainText(`Second ${id}`);

        await massAction(page, [gridRow(page, `Express ${id}`)], 'Change Calculation Type', 'Percentage of subtotal');
        await waitForAdmin(page);
        await filterGrid(page, { name: `Express ${id}` });
        await expect(gridRow(page, `Express ${id}`)).toContainText('Percentage of subtotal');
    });

    test('deletes a method', async ({ page }) => {
        const id = uid();
        await createCarrier(page, `Carrier ${id}`);
        await createRule(page, `Rule ${id}`);
        await createCustomMethod(page, { name: `Express ${id}`, carrier: `Carrier ${id}`, rules: [`Rule ${id}`] });

        await rowAction(page, gridRow(page, `Express ${id}`), 'Delete');
        await acceptModal(page);
        await waitForAdmin(page);
        await expectSuccess(page, 'The custom method has been deleted.');
    });
});
