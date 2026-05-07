

import { test, expect } from "../setup"
test("admin able to generate barcode for generated products", async ({ adminPage }) => {
    await adminPage.goto('admin/pos/products');
    await adminPage.locator('label[for="mass_action_select_all_records"]').click()
    await adminPage.getByRole('button', { name: ' Select Action ' }).click();
    await adminPage.getByRole("link", { name: " Generate Barcode" }).click()
    await adminPage.getByRole("button", { name: "Agree", exact: true }).click()
    await expect(adminPage.getByText("Selected Products barcode generated successfully!").nth(0)).toBeVisible()

});
