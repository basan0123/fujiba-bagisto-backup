import { test, expect } from "../setup"
import { addAddressorder, createSimpleProduct } from "../utils/myfunction"
import fs from "fs";


import {
    generateAttributeCode, generateEmail, generateFirstName, generateGSTNumber,
    generateHostname, generateLastName, generateOutletName, generatePhoneNumber
} from "../utils/faker";



test('customer can be created by pos agent ', async ({ adminPage }) => {
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
    await adminPage.goto("pos/customers")

    await adminPage.getByRole('link', { name: 'Add New Customer' }).click()
    const firstName = generateFirstName();
    await adminPage.getByPlaceholder("First Name").fill(firstName)
    const lastName = generateLastName();
    await adminPage.getByPlaceholder("Last Name").fill(lastName)
    await adminPage.getByPlaceholder("Phone Number").fill(generatePhoneNumber())
    await adminPage.getByPlaceholder("Email").fill(generateEmail())
    await adminPage.getByPlaceholder("Address").fill("America")
    await adminPage.locator('select[name="country"]').selectOption('US')
    await adminPage.locator('select[name="state"]').selectOption('CA')
    await adminPage.getByPlaceholder("City").fill("California")
    await adminPage.getByPlaceholder("Pin code").fill("94046")
    await adminPage.locator('button[aria-label="Add Customer"]').click()
    await expect.soft(adminPage.getByText("Customer created successfully!").nth(0)).toBeVisible()

    const customer = firstName + " " + lastName;

    fs.writeFileSync('customer.json', JSON.stringify(customer, null, 2), 'utf-8');

});
