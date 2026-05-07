import { test, expect } from "../setup"
import { addAddressorder, createSimpleProduct } from "../utils/myfunction"
import fs from "fs";
import { loginAsCustomer } from "../utils/customer";

import {
    generateAttributeCode, generateEmail, generateFirstName, generateGSTNumber,
    generateHostname, generateLastName, generateOutletName, generatePhoneNumber
} from "../utils/faker";

test('customer able to place order from pos', async ({ adminPage }) => {
    await adminPage.goto('pos');
    await adminPage.waitForLoadState('networkidle');
    const posData = JSON.parse(fs.readFileSync('pos-credentials.json', 'utf-8'));

    const usernameField = adminPage.locator('#username');
    await usernameField.waitFor({ state: 'visible' });

    if (await usernameField.isVisible()) {
        const posData = JSON.parse(fs.readFileSync('pos-credentials.json', 'utf-8'));
        await adminPage.fill('#username', posData.username);
        await adminPage.waitForSelector('#password');
        await adminPage.fill('#password', posData.password);

        await adminPage.getByRole('button', { name: 'Login' }).click();
        await expect(
            adminPage.getByText("You have successfully logged in.", { exact: true })
        ).toBeVisible({ timeout: 5000 });
    }
    await adminPage.goto('pos/settings/sync');
    await adminPage.getByRole('button', { name: 'Select All' }).nth(0).click()
    await adminPage.locator('button[aria-label="Start Syncing"]').click()
    await adminPage.waitForFunction(() => {
        const elements = Array.from(document.querySelectorAll('div.grid span.text-green-600'));

        return elements.length === 3 && elements.every(el => {
            const text = el.textContent?.trim().toLowerCase();
            return text === 'completed';
        });
    }, null, { timeout: 10000 });

    await adminPage.goto("pos/customers")
    const customerName = JSON.parse(fs.readFileSync('customer.json', 'utf-8'));

    await adminPage.locator('div.grid > p.text-base', { hasText: customerName }).nth(0).click();

    await (await adminPage.getByRole("button", { name: "Change Customer" }).isVisible()
        ? adminPage.getByRole("button", { name: "Change Customer" })
        : adminPage.getByRole("button", { name: "Select Customer" })
    ).click();
    await adminPage.getByRole('button', { name: "Agree", exact: true }).click();
    await expect.soft(adminPage.getByText("Customer changed successfully.").nth(0)).toBeVisible()
    await adminPage.goto("pos/home")

    const productData = JSON.parse(fs.readFileSync('products.json', 'utf-8'));

    let i = 1
    while (i < 3) {
        let no = Math.floor(Math.random() * 5) + 1
        const productName = productData[`product${no}`];
        await adminPage.locator('div.box-shadow', { hasText: productName }).click();
        await expect.soft(adminPage.getByText("Product added to cart successfully!").nth(0)).toBeVisible()
        i++;
    }

    const cross = adminPage.locator('.phpdebugbar-close-btn').nth(0)
    if (await cross.isVisible()) {
        await cross.click()
    }

    await adminPage.locator('a.secondary-button.w-full').click()



    const amountText = await adminPage
        .locator('div.flex.justify-between > p.text-xl.font-medium')
        .nth(1)
        .textContent();

    const cleanedAmount = amountText?.replace(/[,$]/g, '').trim();
    const amount = parseFloat(cleanedAmount || '0');


    await adminPage.locator('input[name="cash_total"]').fill(amount.toFixed(2))
    await adminPage.locator('button[label="Confirm Payment"]').click()
    await expect.soft(adminPage.getByText("Order placed successfully.").nth(0)).toBeVisible()
    await adminPage.goto("pos/cashier/today-sale")

    const locator = adminPage.locator('div.grid > div.text-gray-900').nth(2);
    const actualText = (await locator.textContent())?.replace(/,/g, '').trim();

    expect.soft(actualText).toBe(`$${amount.toFixed(2)}`);

    await adminPage.goto("pos/orders")
    const locator1 = adminPage.locator('div.grid > div.text-gray-900').nth(2);
    const actualText1 = (await locator1.textContent())?.replace(/,/g, '').trim();

    expect.soft(actualText1).toBe('$' + amount.toFixed(2));
    await adminPage.goto("admin/pos/orders")
    const row = adminPage.locator('div.table-responsive > div.row').nth(1);
    await expect.soft(row.locator('p').nth(4)).toHaveText(`${amount.toFixed(2)}00`);

});
