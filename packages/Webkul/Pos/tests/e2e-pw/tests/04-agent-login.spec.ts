import { test, expect } from "../setup"
import fs from "fs";

test("pos agent able to login with that credentials", async ({ adminPage }) => {
  await adminPage.goto('pos');
  await adminPage.waitForLoadState('networkidle');
  const posData = JSON.parse(fs.readFileSync('pos-credentials.json', 'utf-8'));
  await adminPage.waitForSelector('#username');
  await adminPage.fill('#username', posData.username);
  await adminPage.waitForSelector('#password');
  await adminPage.fill('#password', posData.password);

  await adminPage.getByRole('button', { name: 'Login' }).click();
  await expect(
    adminPage.getByText("You have successfully logged in.", { exact: true })
  ).toBeVisible({ timeout: 5000 });

  const drawer = adminPage.locator('div.absolute');

  await drawer.waitFor({ state: 'visible', timeout: 10000 });

  if (await drawer.isVisible()) {
    await adminPage.getByPlaceholder("100").fill("100");
    await adminPage.getByPlaceholder("Enter counter closure remarks.").fill("this is opening balance");
    await adminPage.locator('button[aria-label="Open Drawer"]').click();

    await expect.soft(
      adminPage.getByText("Drawer opened successfully!").first()
    ).toBeVisible({ timeout: 3000 });
  }
})
