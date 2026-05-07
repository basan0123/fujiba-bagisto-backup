import { test, expect } from "../setup"
import fs from 'fs'
import { getRandomBankName, getRandomAddress } from "../utils/myfunction"
import { generateEmail, generatePhoneNumber } from "../utils/faker"

// This is for bank section of pos in online mode 

test("Payment done by using bank", async ({ adminPage, browser }) => {
    await adminPage.goto('admin/pos/banks')
    await adminPage.getByRole('link', { name: " Create Bank ", exact: true }).click()
    const bankname = getRandomBankName()
    await adminPage.getByPlaceholder('Name').fill(bankname)
    await adminPage.getByPlaceholder('Address').fill(getRandomAddress())
    await adminPage.getByPlaceholder('Email').fill(generateEmail())
    await adminPage.getByPlaceholder('Phone').fill(generatePhoneNumber())
    await adminPage.locator('label[for="status"]').click()
    await adminPage.locator('select[name="agent_id"]').click()
    await adminPage.locator('select[name="agent_id"]').selectOption({value:"1"})
    await adminPage.getByRole('button', { name: " Save Bank " }).click()
    await expect(adminPage.getByText('Bank created successfully!').nth(0)).toBeVisible()

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

    await adminPage.getByRole("link", { name: "Home" }).click()

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

    await adminPage.locator("div.cursor-pointer:has-text('Card')").click()

    await expect(adminPage.locator('#bank_name').selectOption({ label: `${bankname}` })).toBeTruthy()

    fs.writeFileSync(
        'bank-name.json',
        JSON.stringify({ bankname }, null, 2),
        'utf-8'
    );

})
