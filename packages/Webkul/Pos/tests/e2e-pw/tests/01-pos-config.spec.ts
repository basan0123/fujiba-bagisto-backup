
import { test, expect } from "../setup"
test('bagisto pos config', async ({ adminPage }) => {
  await adminPage.goto('admin/configuration/pos/settings');
  const checkbox = adminPage.locator("label.relative").nth(0);
  if (!(await checkbox.isChecked())) {
    await checkbox.check();
  }
  await adminPage.getByRole('button', { name: ' Save Configuration ' }).click()
  await expect(adminPage.getByText("Configuration saved successfully").nth(0)).toBeVisible()

})
