import { expect, Page, test } from '@playwright/test';
import { expectError, expectSuccess, filterGrid, gridRow, openAdmin, openGrid, uid, waitForAdmin } from '../../helpers/admin';
import { runTester } from '../../helpers/tester';

/** Upload a file and stop at the preview (or at the errors, for an invalid file). */
async function previewCsv(page: Page, csv: string): Promise<void> {
    await openAdmin(page, 'easyshippingrules/ratetable/');
    await page.locator('#esr-file').setInputFiles({
        name: 'rates.csv',
        mimeType: 'text/csv',
        buffer: Buffer.from(csv, 'utf-8'),
    });
    await page.getByRole('button', { name: 'Preview Import' }).click();
    await waitForAdmin(page);
}

async function importCsv(page: Page, csv: string): Promise<void> {
    await previewCsv(page, csv);
    await page.getByRole('button', { name: 'Confirm Import' }).click();
    await waitForAdmin(page);
}

test.describe('Rate Tables', () => {
    test('imports a spreadsheet, prices the cart with it and exports it back', async ({ page }) => {
        const id = uid();
        const carrier = `Table Carrier ${id}`;
        const method = `Table Express ${id}`;
        await previewCsv(page, [
            'Transportadora;Método;País;CEP de;CEP até;Peso até;Preço;Prazo',
            `${carrier};${method};BR;01000;05999;30;12,50;2 a 4`,
            `${carrier};${method};BR;20000;28999;30;19,90;`,
        ].join('\r\n'));
        const preview = page.locator('.esr-rate-table-preview');
        await expect(preview).toContainText('Nothing has been saved yet');
        await expect(preview.locator('tr').filter({ hasText: method })).toContainText('New method');
        await expect(preview).toContainText(`New carriers: ${carrier}`);
        await page.getByRole('button', { name: 'Confirm Import' }).click();
        await waitForAdmin(page);
        await expectSuccess(page, 'Imported 2 rate line(s) into 1 method(s).');
        await expect(page.locator('.message-notice').filter({ hasText: method })).toBeVisible();

        await openGrid(page, 'custommethod');
        await filterGrid(page, { name: method });
        await expect(gridRow(page, method)).toContainText('Rate table');
        await expect(gridRow(page, method)).toContainText(carrier);

        await runTester(page, '01310-100');
        const rates = page.locator('section').filter({ hasText: 'Shipping Rates' }).locator('table');
        const rate = rates.locator('tr').filter({ hasText: method });
        await expect(rate).toContainText('12.50');
        // In the store view language: "(2 to 4 business days)", "(2 a 4 dias úteis)"...
        await expect(rate).toContainText(new RegExp(`${method} \\(2 \\S+ 4 `));

        await runTester(page, '30140-071');
        const methods = page.locator('section').filter({ hasText: 'Custom Methods' }).locator('table');
        await expect(methods.locator('tr').filter({ hasText: method })).toContainText('No line of its rate table');

        await openAdmin(page, 'easyshippingrules/ratetable/');
        await page.locator('#esr-format').selectOption('excel_comma');
        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.getByRole('button', { name: 'Export CSV' }).click(),
        ]);
        const contents = await (await download.createReadStream()).toArray();
        const csv = Buffer.concat(contents).toString('utf-8');
        expect(csv).toContain('carrier;method;country;postcode_from');
        // Values with spaces are quoted, as CSV allows.
        expect(csv).toContain(`"${carrier}";"${method}";BR;01000;05999;;30;;;12,5;2-4`);
    });

    test('changes nothing when the preview is canceled', async ({ page }) => {
        const id = uid();
        const method = `Canceled ${id}`;
        await previewCsv(page, ['carrier,method,price', `Carrier ${id},${method},9`].join('\n'));
        await page.getByRole('button', { name: 'Cancel' }).click();
        await waitForAdmin(page);
        await expect(page.locator('.message-notice').filter({ hasText: 'The import was canceled' })).toBeVisible();

        await openGrid(page, 'custommethod');
        await filterGrid(page, { name: method });
        await expect(gridRow(page, method)).toHaveCount(0);
    });

    test('lists the errors of an invalid file and imports nothing', async ({ page }) => {
        const method = `Broken ${uid()}`;
        await previewCsv(page, ['carrier,method,price', `Carrier,${method},abc`, `,${method},1`].join('\n'));

        await expectError(page, 'Nothing was imported');
        await expectError(page, 'Line 2');
        await expectError(page, 'Line 3');

        await openGrid(page, 'custommethod');
        await filterGrid(page, { name: method });
        await expect(gridRow(page, method)).toHaveCount(0);
    });
});
