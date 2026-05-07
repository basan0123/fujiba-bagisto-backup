import { test, expect } from "../setup"
import fs from "fs";

import {
  generateEmail, generateFirstName,
  generateLastName
} from "../utils/faker";


test("pos agent creation", async ({ adminPage }) => {
  await adminPage.goto('admin/pos/users')
  await adminPage.getByRole('link', { name: ' Create Agent ' }).click()
  await adminPage.waitForLoadState('networkidle');
  const userName = generateFirstName();

  // Wait until the Username field is visible
  await adminPage.waitForSelector('[placeholder="Username"]', { state: 'visible' });
  await adminPage.getByPlaceholder("Username").fill(userName);

  await adminPage.waitForSelector('[placeholder="First Name"]', { state: 'visible' });
  await adminPage.getByPlaceholder("First Name").fill(generateFirstName());

  await adminPage.waitForSelector('[placeholder="Last Name"]', { state: 'visible' });
  await adminPage.getByPlaceholder("Last Name").fill(generateLastName());

  await adminPage.waitForSelector('[placeholder="Email"]', { state: 'visible' });
  await adminPage.getByPlaceholder("Email").fill(generateEmail());

  await adminPage.waitForSelector('label[for="status"]', { state: 'visible' });
  await adminPage.locator('label[for="status"]').click();

  const { outletname: savedOutletName } = JSON.parse(
    fs.readFileSync("outlet.json", "utf-8")
  );

  await adminPage.getByPlaceholder('Outlet').selectOption({ label: savedOutletName });
  await adminPage.getByPlaceholder("Password").nth(0).fill("admin123")
  await adminPage.getByPlaceholder("Confirm Password").fill("admin123")

  const posdata = {
    username: userName,
    password: "admin123",
  }

  fs.writeFileSync('pos-credentials.json', JSON.stringify(posdata, null, 2), 'utf-8');

  await adminPage.getByRole('button', { name: ' Save Agent ' }).click()
  await adminPage.waitForLoadState('networkidle');

  const check = adminPage.locator('//p[span[contains(@class, "icon-toast-done")]]')

  if (await check.isVisible()) {
    const text = await check.innerText()
    console.log("Success message:", text);
  }

  await expect(adminPage.locator('text=Agent created successfully!').nth(0)).toBeVisible()

})
