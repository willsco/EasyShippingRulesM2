import { APIRequestContext, expect, Page } from '@playwright/test';
import { readFileSync } from 'fs';
import {
    expectSuccess,
    fillField,
    gridRow,
    openAdmin,
    openGrid,
    rowId,
    saveForm,
    selectField,
    setToggle,
    filterGrid,
} from './admin';
import { addCondition, Condition } from './rules';

export const DATA_FILE = '.auth/data.json';

export interface TestData {
    productId: number;
    sku: string;
}

export function testData(): TestData {
    return JSON.parse(readFileSync(DATA_FILE, 'utf-8')) as TestData;
}

/** Admin REST token. */
export async function adminToken(request: APIRequestContext): Promise<string> {
    const response = await request.post('/index.php/rest/V1/integration/admin/token', {
        data: { username: 'e2e', password: 'E2eTests123' },
    });
    expect(response.ok()).toBeTruthy();
    return (await response.json()) as string;
}

/** Simple product, $100, 1 kg, in stock, on the main website. */
export async function createProduct(request: APIRequestContext, sku: string): Promise<number> {
    const token = await adminToken(request);
    const response = await request.post('/index.php/rest/all/V1/products', {
        headers: { Authorization: `Bearer ${token}` },
        data: {
            product: {
                sku,
                name: `E2E Product ${sku}`,
                attribute_set_id: 4,
                price: 100,
                status: 1,
                visibility: 4,
                type_id: 'simple',
                weight: 1,
                extension_attributes: { website_ids: [1], stock_item: { qty: 1000, is_in_stock: true } },
            },
        },
    });
    expect(response.ok(), await response.text()).toBeTruthy();
    return (await response.json()).id as number;
}

// The helpers below build data through the admin screens; each screen also has its own spec.

export async function createCarrier(page: Page, name: string, position = 0): Promise<string> {
    await openAdmin(page, 'easyshippingrules/carrier/new/');
    await fillField(page, 'name', name);
    await fillField(page, 'sort_order', String(position));
    await saveForm(page);
    await expectSuccess(page, 'The carrier has been saved.');
    await filterGrid(page, { name });
    return rowId(gridRow(page, name));
}

export async function createRule(
    page: Page,
    name: string,
    conditions: Condition[] = [],
    amount = '',
    active = true
): Promise<string> {
    await openAdmin(page, 'easyshippingrules/rule/new/');
    await fillField(page, 'name', name);
    if (!active) {
        await setToggle(page, 'is_active', false);
    }
    if (amount !== '') {
        await fillField(page, 'amount', amount);
    }
    for (const condition of conditions) {
        await addCondition(page, condition);
    }
    await saveForm(page);
    await expectSuccess(page, 'The rule has been saved.');
    await filterGrid(page, { name });
    return rowId(gridRow(page, name));
}

export async function createCustomMethod(
    page: Page,
    options: { name: string; carrier: string | string[]; rules: string[]; calculation?: string; amount?: string }
): Promise<string> {
    await openAdmin(page, 'easyshippingrules/custommethod/new/');
    await fillField(page, 'name', options.name);
    await selectField(page, 'carrier_ids', options.carrier);
    await selectField(page, 'store_ids', 'All Store Views');
    await selectField(page, 'rule_ids', options.rules);
    if (options.calculation) {
        await selectField(page, 'calculation_type', options.calculation);
    }
    await fillField(page, 'amount', options.amount ?? '');
    await saveForm(page);
    await expectSuccess(page, 'The custom method has been saved.');
    await openGrid(page, 'custommethod');
    await filterGrid(page, { name: options.name });
    return rowId(gridRow(page, options.name));
}
