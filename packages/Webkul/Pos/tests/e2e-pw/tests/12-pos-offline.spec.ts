import { test, expect } from "./../setup"
import fs from 'fs'

test("It gives alert message in offline mode", async ({ adminPage, browser }) => {

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
    const context = adminPage.context();
    await context.setOffline(true);


    await expect(adminPage.locator("i.text-red-500")).toBeVisible();

    await expect(adminPage.getByText('Warning: You are offline. Limited functionality available').nth(0)).toBeVisible()

    await adminPage.getByRole('link',{name:"Cashier"}).click()
    await expect(adminPage.getByText("Warning, You are in offline mode")).toBeVisible()

    await adminPage.getByRole('link',{name:"products"}).click()
    await expect(adminPage.getByText("Warning, You are in offline mode")).toBeVisible()

    await adminPage.getByRole('link',{name:"reports"}).click()
    await expect(adminPage.getByText("Warning, You are in offline mode")).toBeVisible()


})