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
import { createRule } from '../../helpers/fixtures';
import { ConditionType } from '../../helpers/rules';

// Existing methods change every checkout, so their rules only match a postcode no test uses.
const UNUSED_POSTCODE = { type: ConditionType.address('postcode'), operator: 'is', value: '99999-999' };

test.describe('Existing Methods', () => {
    test('creates a surcharge on Flat Rate, edits it and deletes it', async ({ page }) => {
        const id = uid();
        await createRule(page, `Never ${id}`, [UNUSED_POSTCODE]);

        await openGrid(page, 'existingmethod');
        await page.locator('#add').click();
        await waitForAdmin(page);
        await fillField(page, 'name', `Surcharge ${id}`);
        await selectField(page, 'store_ids', 'All Store Views');
        await selectField(page, 'rule_ids', `Never ${id}`);
        await selectField(page, 'shipping_methods', 'Flat Rate - [flatrate] Fixed');
        await selectField(page, 'action_type', 'Surcharge');
        await selectField(page, 'calculation_type', 'Percentage of rate');
        await fillField(page, 'amount', '10');
        await fillField(page, 'method_title', 'Standard');
        await saveForm(page, true);
        await expectSuccess(page, 'The existing method has been saved.');

        await expect(field(page, 'shipping_methods').locator('select option:checked')).toHaveText([
            'Flat Rate - [flatrate] Fixed',
        ]);
        await expect(field(page, 'method_title').locator('input')).toHaveValue('Standard');

        await openGrid(page, 'existingmethod');
        await filterGrid(page, { name: `Surcharge ${id}` });
        const row = gridRow(page, `Surcharge ${id}`);
        await expect(row).toContainText('Surcharge');
        await expect(row).toContainText('Percentage of rate');

        await massAction(page, [row], 'Change Action', 'Hide');
        await waitForAdmin(page);
        await expectSuccess(page, 'A total of 1 record(s) have been updated.');
        await filterGrid(page, { name: `Surcharge ${id}` });
        await expect(gridRow(page, `Surcharge ${id}`)).toContainText('Hide');

        await rowAction(page, gridRow(page, `Surcharge ${id}`), 'Delete');
        await acceptModal(page);
        await waitForAdmin(page);
        await expectSuccess(page, 'The existing method has been deleted.');
    });

    test('requires shipping methods and rules', async ({ page }) => {
        await openAdmin(page, 'easyshippingrules/existingmethod/new/');
        await fillField(page, 'name', `Surcharge ${uid()}`);
        await clickFormButton(page, 'save');
        await expectFieldError(page, 'shipping_methods');
        await expectFieldError(page, 'rule_ids');
    });
});
