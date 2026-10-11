import { expect, test } from '@playwright/test';
import { expectSuccess, openAdmin, uid, waitForAdmin } from '../../helpers/admin';
import { createRule } from '../../helpers/fixtures';

test.describe('Configuration', () => {
    test('saves and clears the Default Rule', async ({ page }) => {
        const name = `Default ${uid()}`;
        const ruleId = await createRule(page, name);

        await openAdmin(page, 'admin/system_config/edit/section/easyshippingrules/');
        await expect(page.locator('.page-title')).toHaveText('Configuration');
        const select = page.locator('#easyshippingrules_general_default_rule');
        const inherit = page.locator('#easyshippingrules_general_default_rule_inherit');
        if (await inherit.isChecked()) {
            await inherit.uncheck();
        }

        try {
            await select.selectOption(ruleId);
            await page.locator('#save').click();
            await waitForAdmin(page);
            await expectSuccess(page, 'You saved the configuration.');
            await expect(page.locator('#easyshippingrules_general_default_rule')).toHaveValue(ruleId);
        } finally {
            // Leave no default rule behind: it would hide custom methods in other tests.
            await page.locator('#easyshippingrules_general_default_rule').selectOption('');
            await page.locator('#save').click();
            await waitForAdmin(page);
        }
        await expect(page.locator('#easyshippingrules_general_default_rule')).toHaveValue('');
    });
});
