import { expect, Page, test } from '@playwright/test';
import {
    expectError,
    expectSuccess,
    field,
    fillField,
    filterGrid,
    gridRows,
    openAdmin,
    openGrid,
    saveForm,
    selectField,
    uid,
} from '../../helpers/admin';
import { createCarrier, createCustomMethod, createRule } from '../../helpers/fixtures';
import { addCondition, Condition, conditionsText, ConditionType, removeCondition } from '../../helpers/rules';
import { expectApplied, runTester, testerRow } from '../../helpers/tester';

const SP_CAPITAL = '01000-000-05999-999';

function postcode(operator: 'is between' | 'is not between', value: string): Condition {
    return { type: ConditionType.address('postcode'), operator, value };
}

/** Carrier + one rule + one custom method that only depends on that rule. */
async function methodForRange(page: Page, name: string, condition: Condition): Promise<void> {
    await createCarrier(page, `Carrier ${name}`);
    await createRule(page, `Rule ${name}`, [condition]);
    await createCustomMethod(page, { name, carrier: `Carrier ${name}`, rules: [`Rule ${name}`], amount: '9.9' });
}

test.describe('Postcode ranges', () => {
    test('include both limits and nothing outside them', async ({ page }) => {
        const method = `Capital ${uid()}`;
        await methodForRange(page, method, postcode('is between', SP_CAPITAL));

        const cases: [string, boolean][] = [
            ['01000-000', true],
            ['05999-999', true],
            ['00999-999', false],
            ['06000-000', false],
        ];
        for (const [code, applied] of cases) {
            await test.step(`${code} ${applied ? 'is' : 'is not'} in the range`, async () => {
                await runTester(page, code);
                await expectApplied(page, 'Custom Methods', method, applied);
            });
        }
    });

    test('"is not between" offers the method only outside the range', async ({ page }) => {
        const method = `Outside SP ${uid()}`;
        await methodForRange(page, method, postcode('is not between', '01000-000-19999-999'));

        await runTester(page, '20040-020');
        await expectApplied(page, 'Custom Methods', method, true);
        await runTester(page, '13010-000');
        await expectApplied(page, 'Custom Methods', method, false);
    });

    test('postcodes match with or without the hyphen', async ({ page }) => {
        const id = uid();
        const hyphenated = `Hyphenated ${id}`;
        const plain = `Plain ${id}`;
        await methodForRange(page, hyphenated, postcode('is between', SP_CAPITAL));
        await methodForRange(page, plain, postcode('is between', '01000000-05999999'));

        for (const code of ['01310-100', '01310100']) {
            await test.step(`customer types ${code}`, async () => {
                await runTester(page, code);
                await expectApplied(page, 'Custom Methods', hyphenated, true);
                await expectApplied(page, 'Custom Methods', plain, true);
            });
        }
    });

    test('each range gets the price of its own rule', async ({ page }) => {
        const id = uid();
        const method = `Delivery ${id}`;
        await createCarrier(page, `Carrier ${id}`);
        await createRule(page, `Capital ${id}`, [postcode('is between', SP_CAPITAL)], '9.9');
        await createRule(page, `Countryside ${id}`, [postcode('is between', '13000-000-19999-999')], '19.9');
        // No price on the method: it uses the price of the first valid rule.
        await createCustomMethod(page, {
            name: method,
            carrier: `Carrier ${id}`,
            rules: [`Capital ${id}`, `Countryside ${id}`],
        });

        await runTester(page, '01310-100');
        await expect(testerRow(page, 'Shipping Rates', method)).toContainText('9.90');
        await expect(testerRow(page, 'Rules', `Capital ${id}`)).toContainText('Valid');
        await expect(testerRow(page, 'Rules', `Countryside ${id}`)).toContainText('Not valid');

        await runTester(page, '13010-000');
        await expect(testerRow(page, 'Shipping Rates', method)).toContainText('19.90');

        await runTester(page, '20040-020');
        await expect(testerRow(page, 'Shipping Rates', method)).toHaveCount(0);
        await expectApplied(page, 'Custom Methods', method, false);
    });

    test('an existing method changes Flat Rate only inside the range', async ({ page }) => {
        // No other test ships to Acre (699xx-xxx), so hiding Flat Rate there affects no other test.
        const id = uid();
        const name = `Hide Acre ${id}`;
        await createRule(page, `Acre ${id}`, [postcode('is between', '69900-000-69999-999')]);

        await openAdmin(page, 'easyshippingrules/existingmethod/new/');
        await fillField(page, 'name', name);
        await selectField(page, 'store_ids', 'All Store Views');
        await selectField(page, 'rule_ids', `Acre ${id}`);
        await selectField(page, 'shipping_methods', 'Flat Rate - [flatrate] Fixed');
        await selectField(page, 'action_type', 'Hide');
        await saveForm(page);
        await expectSuccess(page, 'The existing method has been saved.');

        await runTester(page, '69900-100');
        await expectApplied(page, 'Existing Methods', name, true);
        await expect(testerRow(page, 'Shipping Rates', 'Flat Rate')).toContainText('Hidden');

        await runTester(page, '69800-000');
        await expectApplied(page, 'Existing Methods', name, false);
        await expect(testerRow(page, 'Shipping Rates', 'Flat Rate')).toContainText('10.00');
    });

    test('a rule with an invalid range is not saved and keeps what was typed', async ({ page }) => {
        const name = `Invalid ${uid()}`;
        await openAdmin(page, 'easyshippingrules/rule/new/');
        await fillField(page, 'name', name);

        // A single postcode is not a range.
        await addCondition(page, postcode('is between', '01000-000'));
        await saveForm(page);
        await expectError(page, '"01000-000" is not a valid range');
        await expect(field(page, 'name').locator('input')).toHaveValue(name);
        expect(await conditionsText(page)).toContain('Shipping Postcode is between 01000-000');

        // Bounds in the wrong order.
        await removeCondition(page, '1--1');
        await addCondition(page, postcode('is between', '05999-999-01000-000'));
        await saveForm(page);
        await expectError(page, '"05999-999-01000-000" is not a valid range');

        await removeCondition(page, '1--1');
        await addCondition(page, postcode('is between', SP_CAPITAL));
        await saveForm(page);
        await expectSuccess(page, 'The rule has been saved.');

        await openGrid(page, 'rule');
        await filterGrid(page, { name });
        await expect(gridRows(page)).toHaveCount(1);
    });
});
