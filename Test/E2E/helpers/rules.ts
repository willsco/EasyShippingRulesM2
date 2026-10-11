import { expect, Locator, Page } from '@playwright/test';

const NS = 'WillSco\\EasyShippingRulesM2\\Model\\Rule\\Condition\\';

/** Values of the "add condition" select. */
export const ConditionType = {
    combine: `${NS}Combine`,
    productFound: `${NS}Product\\Found`,
    shippingRule: `${NS}ShippingRule`,
    customerGroup: `${NS}CustomerGroup`,
    address: (attribute: string) => `${NS}Address|${attribute}`,
    customer: (attribute: string) => `${NS}Customer|${attribute}`,
    product: (attribute: string) => `${NS}Product|${attribute}`,
};

export interface Condition {
    /** Value of the "add condition" select, see ConditionType. */
    type: string;
    /** Operator label, e.g. "is between". */
    operator?: string;
    /** Text value. */
    value?: string;
    /** Labels to pick in a select / multiselect value. */
    options?: string[];
    /** Rule names to tick in the "Shipping Rule" chooser grid. */
    chooseRules?: string[];
    /** Conditions added inside this one (combinations). */
    children?: Condition[];
}

export function conditionsTree(page: Page): Locator {
    return page.locator('fieldset[id^="easyshippingrules_rule_formrule_conditions_fieldset_"]').first();
}

/** Children list of a condition, e.g. "1" (root) or "1--2". */
function children(page: Page, parentId: string): Locator {
    return conditionsTree(page).locator(`ul[id="conditions__${parentId}__children"]`);
}

/** Add a condition under the given parent and return its id (e.g. "1--2"). */
export async function addCondition(page: Page, condition: Condition, parentId = '1'): Promise<string> {
    const list = children(page, parentId);
    const items = list.locator(':scope > li');
    const before = await items.count();

    const newChild = list.locator(':scope > li:last-child .rule-param-new-child');
    await newChild.locator('a.label').click();
    await newChild.locator('select').selectOption(condition.type);

    // The new condition replaces the "add" item and a fresh "add" item is appended.
    await expect(items).toHaveCount(before + 1);
    const typeInput = items.nth(before - 1).locator('input[name$="[type]"]').first();
    const name = (await typeInput.getAttribute('name')) ?? '';
    const id = name.replace(/^rule\[conditions\]\[/, '').replace(/\]\[type\]$/, '');
    const item = items.nth(before - 1);

    if (condition.operator) {
        await setParam(item, id, 'operator', async (element) => {
            await element.locator('select').selectOption({ label: condition.operator! });
        });
    }
    if (condition.value !== undefined) {
        await setParam(item, id, 'value', async (element) => {
            await element.locator('input, textarea').first().fill(condition.value!);
        });
    }
    if (condition.options) {
        await setParam(item, id, 'value', async (element) => {
            const select = element.locator('select').first();
            const values: string[] = [];
            for (const label of condition.options!) {
                values.push((await select.locator('option').filter({ hasText: label }).first().getAttribute('value')) ?? '');
            }
            await select.selectOption(values);
        });
    }
    if (condition.chooseRules) {
        await setParam(item, id, 'value', async (element) => {
            await element.locator('a.rule-chooser-trigger').click();
            const chooser = item.locator('.rule-chooser');
            for (const ruleName of condition.chooseRules!) {
                const row = chooser.locator('tbody tr').filter({ hasText: ruleName }).first();
                await row.locator('input[type="checkbox"]').check();
            }
        });
    }
    for (const child of condition.children ?? []) {
        await addCondition(page, child, id);
    }

    return id;
}

/** Open a condition parameter, edit it and close it (explicit "apply" when the condition has one). */
async function setParam(item: Locator, id: string, param: 'operator' | 'value', edit: (element: Locator) => Promise<void>) {
    const span = item
        .locator(`:scope > span.rule-param:has([name="rule[conditions][${id}][${param}]"]), `
            + `:scope > span.rule-param:has([name="rule[conditions][${id}][${param}][]"])`)
        .first();
    await span.locator(':scope > a.label').click();
    const element = span.locator(':scope > span.element');
    await expect(element).toBeVisible();
    await edit(element);

    const apply = element.locator('a.rule-param-apply');
    if (await apply.count()) {
        await apply.click();
    } else {
        await element.locator('input, select, textarea').first().press('Enter').catch(() => undefined);
        await element.locator('input, select, textarea').first().blur();
    }
    await expect(element).toBeHidden();
}

/** Remove a condition by id. */
export async function removeCondition(page: Page, id: string): Promise<void> {
    const item = conditionsTree(page).locator(`li:has(> input[name="rule[conditions][${id}][type]"])`);
    await item.locator(':scope > span.rule-param a.rule-param-remove').click();
    await expect(item).toHaveCount(0);
}

/** Text of the whole conditions tree, as shown to the admin. */
export async function conditionsText(page: Page): Promise<string> {
    return (await conditionsTree(page).innerText()).replace(/\s+/g, ' ');
}
