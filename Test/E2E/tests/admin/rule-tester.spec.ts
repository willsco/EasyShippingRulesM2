import { expect, test } from '@playwright/test';
import { openAdmin, uid, waitForAdmin } from '../../helpers/admin';
import { createCarrier, createCustomMethod, createRule } from '../../helpers/fixtures';
import { Condition, ConditionType } from '../../helpers/rules';
import { expectApplied, runTester, testerRow } from '../../helpers/tester';

test.describe('Rule Tester', () => {
    test('explains which methods apply to a cart', async ({ page }) => {
        const id = uid();
        await createCarrier(page, `Tester ${id}`);
        await createRule(page, `SP ${id}`, [
            { type: ConditionType.address('postcode'), operator: 'is between', value: '01000-000-05999-999' },
        ]);
        await createCustomMethod(page, {
            name: `Tester Express ${id}`,
            carrier: `Tester ${id}`,
            rules: [`SP ${id}`],
            amount: '9.9',
        });

        await runTester(page, '01310-100');
        const rates = page.locator('section').filter({ hasText: 'Shipping Rates' }).locator('table');
        const added = rates.locator('tr').filter({ hasText: `Tester Express ${id}` });
        await expect(added).toContainText('Added');
        await expect(added).toContainText('9.90');
        await expect(rates.locator('tr').filter({ hasText: 'Flat Rate' })).toContainText('10.00');

        const rules = page.locator('section').filter({ hasText: /^\s*Rules/ }).locator('table');
        await expect(rules.locator('tr').filter({ hasText: `SP ${id}` })).toContainText('Valid');

        const methods = page.locator('section').filter({ hasText: 'Custom Methods' }).locator('table');
        const method = methods.locator('tr').filter({ hasText: `Tester Express ${id}` });
        await expect(method).toContainText('Yes');
        await expect(method).toContainText(`"SP ${id}"`);

        await runTester(page, '20040-020');
        const missing = page.locator('section').filter({ hasText: 'Custom Methods' }).locator('tr')
            .filter({ hasText: `Tester Express ${id}` });
        await expect(missing).toContainText('No');
        await expect(missing).toContainText('None of its rules');
    });

    test('simulates enabling and disabling rules without saving', async ({ page }) => {
        const id = uid();
        const range: Condition = { type: ConditionType.address('postcode'), operator: 'is between', value: '01000-000-05999-999' };
        await createCarrier(page, `Draft ${id}`);
        const draftId = await createRule(page, `Draft SP ${id}`, [range], '', false);
        const liveId = await createRule(page, `Live SP ${id}`, [range]);
        const draft = `Draft Express ${id}`;
        const live = `Live Express ${id}`;
        await createCustomMethod(page, { name: draft, carrier: `Draft ${id}`, rules: [`Draft SP ${id}`], amount: '12' });
        await createCustomMethod(page, { name: live, carrier: `Draft ${id}`, rules: [`Live SP ${id}`], amount: '8' });

        // Every rule is listed; the enabled ones start selected.
        await runTester(page, '01310-100');
        const rules = page.locator('#esr-rules');
        await expect(rules.locator(`option[value="${draftId}"]`)).toHaveText(`#${draftId} Draft SP ${id} (disabled)`);
        await expect(rules.locator(`option[value="${liveId}"]`)).toHaveText(`#${liveId} Live SP ${id}`);
        await expect(rules.locator(`option[value="${draftId}"]`)).toHaveJSProperty('selected', false);
        await expect(rules.locator(`option[value="${liveId}"]`)).toHaveJSProperty('selected', true);
        await expect(testerRow(page, 'Rules', `Draft SP ${id}`)).toContainText('Disabled');
        await expectApplied(page, 'Custom Methods', draft, false);
        await expectApplied(page, 'Custom Methods', live, true);

        await runTester(page, '01310-100', { enable: [draftId], disable: [liveId] });
        await expect(rules.locator(`option[value="${draftId}"]`)).toHaveJSProperty('selected', true);
        await expect(rules.locator(`option[value="${liveId}"]`)).toHaveJSProperty('selected', false);
        const draftRule = testerRow(page, 'Rules', `Draft SP ${id}`);
        await expect(draftRule).toContainText('Valid');
        await expect(draftRule).toContainText('Disabled, simulated as enabled.');
        const liveRule = testerRow(page, 'Rules', `Live SP ${id}`);
        await expect(liveRule).toContainText('Disabled');
        await expect(liveRule).toContainText('Enabled, simulated as disabled.');
        await expectApplied(page, 'Custom Methods', draft, true);
        await expectApplied(page, 'Custom Methods', live, false);
        await expect(testerRow(page, 'Shipping Rates', draft)).toContainText('12.00');
        await expect(testerRow(page, 'Shipping Rates', live)).toHaveCount(0);

        // Outside the range the simulated rule is evaluated like any other rule.
        await runTester(page, '20040-020', { enable: [draftId] });
        await expect(testerRow(page, 'Rules', `Draft SP ${id}`)).toContainText('Not valid');
        await expectApplied(page, 'Custom Methods', draft, false);

        // Nothing was saved: a fresh tester starts from the stored status again.
        await runTester(page, '01310-100');
        await expect(testerRow(page, 'Rules', `Draft SP ${id}`)).toContainText('Disabled');
        await expectApplied(page, 'Custom Methods', draft, false);
        await expectApplied(page, 'Custom Methods', live, true);
    });

    test('reports products that cannot be added', async ({ page }) => {
        await openAdmin(page, 'easyshippingrules/tester/');
        await page.locator('#esr-items').fill(`does-not-exist-${uid()}, 1`);
        await page.locator('#esr-country').selectOption('BR');
        await page.locator('.esr-tester button[type="submit"]').click();
        await waitForAdmin(page);
        await expect(page.locator('.message-error')).toContainText('Add at least one product');
        await expect(page.locator('#esr-items')).toHaveValue(/does-not-exist/);
    });
});
