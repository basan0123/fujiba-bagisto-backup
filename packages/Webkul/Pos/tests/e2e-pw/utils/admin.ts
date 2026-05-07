import { test, expect } from "@playwright/test"

export async function loginAsAdmin(page) {
    /**
     * Admin credentials.
     */
    const adminCredentials = {
        email: "admin@example.com",
        password: "admin123",
    };

    /**
     * Authenticate the admin user.
     */

    const email = await page.locator('input[name="email"]')


    await page.goto("admin");
    if (await email.isVisible({ timeout: 5000 })) {
        await page.fill('input[name="email"]', adminCredentials.email);
        await page.fill('input[name="password"]', adminCredentials.password);
        await page.press('input[name="password"]', "Enter");




    }
    else {

        /**
         * Wait for the dashboard to load.
         */
        await page.waitForURL("**/admin/dashboard");

        return adminCredentials;


    }

}


import { fileURLToPath } from "url";
import path from "path";
import {
    generateSKU,
    generateName,
    generateDescription,
    generateLocation,
    generateRandomDateTime,
    generateHostname,
} from "../utils/faker";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export async function createSimpleProduct(adminPage) {
    /**
     * Main product data which we will use to create the product.
     */
    const product = {
        name: generateName(),
        sku: generateSKU(),
        productNumber: generateSKU(),
        shortDescription: generateDescription(),
        description: generateDescription(),
        price: "199",
        weight: "25",
    };

    /**
     * Reaching to the create product page.
     */
    await adminPage.goto("admin/catalog/products");
    await adminPage.waitForSelector(
        'button.primary-button:has-text("Create Product")'
    );
    await adminPage.getByRole("button", { name: "Create Product" }).click();

    /**
     * Opening create product form in modal.
     */
    await adminPage.locator('select[name="type"]').selectOption("simple");
    await adminPage
        .locator('select[name="attribute_family_id"]')
        .selectOption("1");
    await adminPage.locator('input[name="sku"]').fill(generateSKU());
    await adminPage.getByRole("button", { name: "Save Product" }).click();

    /**
     * After creating the product, the page is redirected to the edit product page, where
     * all the details need to be filled in.
     */
    await adminPage.waitForSelector(
        'button.primary-button:has-text("Save Product")'
    );

    /**
     * Waiting for the main form to be visible.
     */
    await adminPage.waitForSelector('form[enctype="multipart/form-data"]');

    /**
     * General Section.
     */
    await adminPage.locator("#product_number").fill(product.productNumber);
    await adminPage.locator("#name").fill(product.name);
    const name = await adminPage.locator('input[name="name"]').inputValue();
    console.log(name);

    /**
     * Description Section.
     */
    await adminPage.fillInTinymce(
        "#short_description_ifr",
        product.shortDescription
    );
    await adminPage.fillInTinymce("#description_ifr", product.description);

    /**
     * Meta Description Section.ntials
     */
    await adminPage.locator("#meta_title").fill(product.name);
    await adminPage.locator("#meta_keywords").fill(product.name);
    await adminPage.locator("#meta_description").fill(product.shortDescription);

    /**
     * Image Section.
     */
    // Will add images later.

    /**
     * Price Section.
     */
    await adminPage.locator("#price").fill(product.price);

    /**
     * Shipping Section.
     */
    await adminPage.locator("#weight").fill(product.weight);


    await adminPage.locator('.peer.h-5').first().click();
    await adminPage.locator('div:nth-child(3) > .relative > .peer.h-5').click();
    await adminPage.locator('div:nth-child(4) > .relative > .peer.h-5').click();   //// this is for checking the necessary fields
    await adminPage.locator('div:nth-child(5) > .relative > .peer.h-5').click();
    await adminPage.locator('div:nth-child(6) > .relative > .peer.h-5').click();

    /**
     * Inventories Section.
     */
    await adminPage.locator('input[name="inventories\\[1\\]"]').click();
    await adminPage.locator('input[name="inventories\\[1\\]"]').fill("5000");



    /**
     * Saving the product.
     */
    /**
     * Saving the product.
     */
    await adminPage.getByRole("button", { name: "Save Product" }).click();

    /**
     * Expecting for the product to be saved.
     */
    await expect(adminPage.locator("#app")).toContainText(
        "Product updated successfully"
    );

    /**
     * Checking the product in the list.
     */
    await adminPage.goto("admin/catalog/products");
    await expect(adminPage.getByText(`${name}`)).toBeVisible();

    return name;
}


export const admincr = {
    email: "admin@example.com",
    password: "admin123",
};


