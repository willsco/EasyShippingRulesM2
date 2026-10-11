import { expect, test } from '@playwright/test';
import {
    acceptModal,
    clickFormButton,
    expectFieldError,
    expectSuccess,
    fillField,
    filterGrid,
    gridRow,
    massAction,
    openAdmin,
    openGrid,
    rowAction,
    saveForm,
    uid,
    waitForAdmin,
} from '../../helpers/admin';
import { createRule } from '../../helpers/fixtures';
import { addCondition, conditionsText, ConditionType, removeCondition } from '../../helpers/rules';

test.describe('Rules', () => {
    test('builds conditions in the editor and keeps them after saving', async ({ page }) => {
        const name = `Rule ${uid()}`;
        await openAdmin(page, 'easyshippingrules/rule/new/');
        await fillField(page, 'name', name);
        await fillField(page, 'description', 'Created by the E2E tests');
        await fillField(page, 'amount', '12.5');

        await addCondition(page, {
            type: ConditionType.address('postcode'),
            operator: 'is between',
            value: '01000-000-05999-999',
        });
        await addCondition(page, {
            type: ConditionType.address('base_subtotal'),
            operator: 'equals or greater than',
            value: '100',
        });
        await addCondition(page, { type: ConditionType.customerGroup, options: ['NOT LOGGED IN'] });
        await addCondition(page, {
            type: ConditionType.productFound,
            children: [{ type: ConditionType.product('quote_item_price'), operator: 'equals or greater than', value: '50' }],
        });

        await saveForm(page, true);
        await expectSuccess(page, 'The rule has been saved.');
        await expect(page.locator('.page-title')).toHaveText(`Edit Rule "${name}"`);

        const text = await conditionsText(page);
        expect(text).toContain('Shipping Postcode is between 01000-000-05999-999');
        expect(text).toContain('Subtotal equals or greater than 100');
        expect(text).toContain('Customer Group is one of NOT LOGGED IN');
        expect(text).toContain('Price in cart equals or greater than 50');
    });

    test('removes a condition', async ({ page }) => {
        const name = `Rule ${uid()}`;
        await createRule(page, name, [
            { type: ConditionType.address('postcode'), operator: 'is between', value: '01000000-05999999' },
            { type: ConditionType.address('weight'), operator: 'less than', value: '5' },
        ]);
        await rowAction(page, gridRow(page, name), 'Edit');
        await waitForAdmin(page);

        await removeCondition(page, '1--2');
        await saveForm(page, true);
        await expectSuccess(page, 'The rule has been saved.');

        const text = await conditionsText(page);
        expect(text).toContain('Shipping Postcode is between 01000000-05999999');
        expect(text).not.toContain('Total Weight');
    });

    test('references another rule through the chooser grid', async ({ page }) => {
        const base = `Base ${uid()}`;
        const baseId = await createRule(page, base, [
            { type: ConditionType.address('country_id'), operator: 'is', options: ['Brazil'] },
        ]);

        const name = `Uses base ${uid()}`;
        await openAdmin(page, 'easyshippingrules/rule/new/');
        await fillField(page, 'name', name);
        await addCondition(page, { type: ConditionType.shippingRule, operator: 'is valid', chooseRules: [base] });
        await saveForm(page, true);
        await expectSuccess(page, 'The rule has been saved.');

        expect(await conditionsText(page)).toContain(`Shipping Rule is valid ${baseId}`);
    });

    test('requires a rule name', async ({ page }) => {
        await openAdmin(page, 'easyshippingrules/rule/new/');
        await clickFormButton(page, 'save');
        await expectFieldError(page, 'name');
    });

    test('disables and deletes rules from the grid', async ({ page }) => {
        const name = `Rule ${uid()}`;
        await createRule(page, name);

        await massAction(page, [gridRow(page, name)], 'Change Status', 'Disabled');
        await waitForAdmin(page);
        await expectSuccess(page, 'A total of 1 record(s) have been updated.');
        await filterGrid(page, { name });
        await expect(gridRow(page, name)).toContainText('Disabled');

        await rowAction(page, gridRow(page, name), 'Delete');
        await acceptModal(page);
        await waitForAdmin(page);
        await expectSuccess(page, 'The rule has been deleted.');
        await openGrid(page, 'rule');
        await filterGrid(page, { name });
        await expect(gridRow(page, name)).toHaveCount(0);
    });
});
