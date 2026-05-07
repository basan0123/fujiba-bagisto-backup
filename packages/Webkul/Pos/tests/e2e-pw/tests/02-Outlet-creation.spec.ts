import { test, expect } from "../setup"
import { addAddressorder, createSimpleProduct } from "../utils/myfunction"
import fs from "fs";

import {
  generateAttributeCode, generateEmail, generateFirstName, generateGSTNumber,
  generateHostname, generateLastName, generateOutletName, generatePhoneNumber
} from "../utils/faker";



test("pos outlet creation", async ({ adminPage }) => {

  await adminPage.goto('admin/pos/outlets');
  await adminPage.getByRole('link', { name: ' Create Outlet ' }).click()
  await adminPage.locator("label[for='status']").click()
  let outletname = generateOutletName()
  fs.writeFileSync('outlet.json', JSON.stringify({ outletname }, null, 2), 'utf-8');

  await adminPage.getByPlaceholder("Outlet Name").fill(outletname)
  await adminPage.getByPlaceholder("Email").fill(generateEmail())
  await adminPage.getByPlaceholder("Phone").fill(generatePhoneNumber())
  await adminPage.getByPlaceholder("Website").fill(generateHostname())
  await adminPage.getByPlaceholder("Customer Care Number").fill(generatePhoneNumber())
  await adminPage.getByPlaceholder("GST Number").fill(generateGSTNumber())
  const selectLocator = adminPage.locator('select[name="receipt_id"]');
  const options = await selectLocator.locator('option').all();

  for (const option of options) {
    const text = (await option.textContent())?.trim().toLowerCase();
    if (text === 'preview receipt') {
      const value = await option.getAttribute('value');
      if (value) {
        await selectLocator.selectOption({ value });
      }
      break;
    }
  }

  await adminPage.getByPlaceholder("Address").fill("America")
  await adminPage.locator('select[name="country"]').selectOption('US')
  await adminPage.locator('select[name="state"]').selectOption('CA')
  await adminPage.getByPlaceholder("City").fill("California")
  await adminPage.getByPlaceholder("Postcode").fill("94046")
  await adminPage.getByPlaceholder("Select Inventory Source").selectOption('1')
  await adminPage.locator('input[name="low_stock_qty"]').fill("10")
  await adminPage.getByRole('button', { name: ' Save Outlet ' }).click()
  await expect(adminPage.getByText("Outlet created successfully").nth(0)).toBeVisible()

})