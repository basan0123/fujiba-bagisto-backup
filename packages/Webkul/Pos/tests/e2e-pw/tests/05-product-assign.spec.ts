import { test, expect } from "../setup"
import { addAddressorder, createSimpleProduct } from "../utils/myfunction"
import fs from "fs";
import { loginAsCustomer } from "../utils/customer";

import {
    generateAttributeCode, generateEmail, generateFirstName, generateGSTNumber,
    generateHostname, generateLastName, generateOutletName, generatePhoneNumber
} from "../utils/faker";

test("assigned product visible in pos", async ({ adminPage }) => {


    const products: { [key: string]: string } = {};

    let i = 0;
    while (i < 5) {
        let name = await createSimpleProduct(adminPage); // assuming this returns product name
        products[`product${i + 1}`] = name;
        i++;
    }

    fs.writeFileSync('products.json', JSON.stringify(products, null, 2), 'utf-8');

    // Now assign these products to the outlet
    await adminPage.goto('admin/pos/outlets');
    await adminPage.locator("span.pos-listing-icon").click()
    await adminPage.locator('label[for="mass_action_select_all_records"]').click()
    await adminPage.locator("button > span").nth(0).click();
    await adminPage.getByRole("link", { name: "Update Assign" }).hover();
    await adminPage.locator("li>a").nth(1).click();
    await adminPage.getByRole("button", { name: "Agree", exact: true }).nth(0).click();
    await expect.soft(adminPage.getByText("Product assign updated successfully!").nth(0)).toBeVisible()

    //  Now sync and verify prodcut in pos
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


        if (await adminPage.locator('div.absolute').isVisible()) {
            await adminPage.getByPlaceholder("100").fill("100");
            await adminPage.getByPlaceholder("Enter counter closure remarks.").fill("this is opening balance");
            await adminPage.locator('button[aria-label="Open Drawer"]').click();
            await expect.soft(adminPage.getByText("Drawer opened successfully!").first()).toBeVisible({ timeout: 3000 });
        }
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


    await adminPage.goto("pos/home")

    let product = JSON.parse(fs.readFileSync('products.json', 'utf-8'));

    for (const key in product) {
        const productName = product[key];
        await expect.soft(adminPage.locator(`text=${productName}`)).toBeVisible();
    }
})