import { test, expect } from "@playwright/test"



export async function configurationMarketplace(adminPage) {

    await adminPage.goto("admin")
    await adminPage.getByRole("link", { name: "  Configure  " }).click()
    await adminPage.getByRole('link', { name: 'Marketplace Manage Marketplace' }).click()

    // await adminPage.click('(//p[contains(.," Manage Marketplace ")])[2]');

    await adminPage.waitForSelector('//p[@class="text-xl font-bold text-gray-800 dark:text-white"]');

    /**
     * Create a variable to store the status value
     */
    const status = await adminPage.locator('label > div').first();

    /**
     * Check if status is Enable/Disable
     */
    if (!(await status.isChecked())) {
        /**
         * If status is disabled So that first Enable the Marketplace status 
         */
        await status.click();

    }

    await adminPage.getByRole("button", { name: " Save Configuration " }).click()
    await expect(adminPage.getByText("Configuration saved successfully").nth(0)).toBeVisible()

}

export async function configurationMarketplace_change(adminPage) {

    await adminPage.goto("admin")
    await adminPage.getByRole("link", { name: "  Configure  " }).click()
    await adminPage.getByRole('link', { name: 'Marketplace Manage Marketplace' }).click()

    // await adminPage.click('(//p[contains(.," Manage Marketplace ")])[2]');

    await adminPage.waitForSelector('//p[@class="text-xl font-bold text-gray-800 dark:text-white"]');

    /**
     * Create a variable to store the status value
     */
    const status = await adminPage.locator('label > div').first();

    /**
     * Check if status is Enable/Disable
     */
    if (await status.isChecked()) {
        /**
         * If status is disabled So that first Enable the Marketplace status 
         */
        await status.click();

    }

    await adminPage.getByRole("button", { name: " Save Configuration " }).click()
    await expect(adminPage.getByText("Configuration saved successfully")).toBeVisible()
    await expect(await adminPage.getByRole("link", { name: " Marketplace " })).not.toBeVisible()

}

export async function configurationtimeslot(adminPage) {
    await adminPage.goto("admin/configuration/marketplace/mp-time-delivery-slot")

    const slot = adminPage.locator('label.relative.inline-flex.cursor-pointer.items-center').nth(0)

    if (!(await slot.isChecked())) {
        await slot.click()
    }

    const select = await adminPage.locator('[name="marketplace[mp-time-delivery-slot][settings][allowed-days][]"]');

    // Get all option values inside the select
    const allValues = await select.locator('option').evaluateAll(options => options.map(option => option.value));

    // Select all options by passing all values
    await select.selectOption(allValues);

    await adminPage.locator("[name='marketplace[mp-time-delivery-slot][settings][total-days]']").fill("7")

    await adminPage.locator("[name='marketplace[mp-time-delivery-slot][settings][time-slot-error-message]']").fill("sorry guys as this is wrote by playwright so i am not be able to do that")

    const slot2 = adminPage.locator("label.relative.inline-flex.cursor-pointer.items-center").nth(1)

    if (!(await slot2.isChecked()))
        await slot2.click()

    await adminPage.getByRole("button", { name: " Save Configuration " }).click()
    await expect(adminPage.getByText("Configuration saved successfully")).toBeVisible()

}

export async function configurationtimeslot_change(adminPage) {
    await adminPage.goto("admin/configuration/marketplace/mp-time-delivery-slot")

    const slot = adminPage.locator('label.relative.inline-flex.cursor-pointer.items-center').nth(0)

    if (await slot.isChecked()) {
        await slot.click()
    }

    const slot2 = adminPage.locator("label.relative.inline-flex.cursor-pointer.items-center").nth(1)

    if (await slot2.isChecked())
        await slot2.click()

    await adminPage.getByRole("button", { name: " Save Configuration " }).click()
    await expect(adminPage.getByText("Configuration saved successfully")).toBeVisible()

}



import { getImageFile } from "../utils/faker";

import { fileURLToPath } from "url";
import path from "path";
import {
    generateSKU,
    generateName,
    generateDescription,
    generateLocation,
    generateRandomDateTime,
    generateHostname,
    getRandomAverageProductPrice,
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
        price: getRandomAverageProductPrice(),
        inventories: getRandomAverageProductPrice(),
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


    // let i = 0
    // while (i < 3) {
    //    let path = getImageFile()

        /**
         * Image Section is added here.
         */                                                                           // this is for image we will add image later okay? 
        // Will add images later.

    //    await adminPage.setInputFiles('input[type="file"][accept="image/*"]', path);

    //    i += 1

//    }

    /**
     * Price Section.
     */
    await adminPage.locator("#price").fill((product.price).toString());

    /**
     * Shipping Section.
     */
    await adminPage.locator("#weight").fill(product.weight);


    const selectors = [
        '.peer.h-5', // first one
        'div:nth-child(3) > .relative > .peer.h-5',
        'div:nth-child(4) > .relative > .peer.h-5',
        'div:nth-child(5) > .relative > .peer.h-5',
        'div:nth-child(6) > .relative > .peer.h-5'
    ];

    for (const selector of selectors) {
        const checkbox = adminPage.locator(selector).first();
        if (!(await checkbox.isChecked())) {
            await checkbox.click();
        }
    }

    /**
     * Inventories Section.
     */
    await adminPage.locator('input[name="inventories\\[1\\]"]').click();
    await adminPage.locator('input[name="inventories\\[1\\]"]').fill(
        Math.floor(product.inventories).toString()
    );

    /**
     * Saving the product.
     */
    /**
     * Saving the product.
     */
    await adminPage.getByRole("button", { name: "Save Product" }).click();

    /**
     * Expecting for the product to be    // let i = 0
    // while (i < 3) {
    //     let path = getImageFile()

    //     /**
    //      * Image Section is added here.
    //      */
    //     // Will add images later.

    //     await adminPage.setInputFiles('input[type="file"][accept="image/*"]', path);

    //     i += 1

    // } saved.
     
    await expect(adminPage.locator("#app")).toContainText(
        "Product updated successfully"
    );

    /**
     * Checking the product in the list.
     */
    await adminPage.goto("admin/catalog/products");
    await expect(adminPage.getByText(`${name}`).nth(0)).toBeVisible();


    return name;
}

import { generateFirstName, generateLastName } from "../utils/faker";

export async function addAddressorder(adminPage) {

    await adminPage.getByPlaceholder("First Name").isVisible()
    await adminPage.getByPlaceholder("Company Name").fill(generateName());
    await adminPage.getByPlaceholder("Company Name").press("Tab");

    await adminPage.getByPlaceholder("First Name").fill(generateFirstName());
    await adminPage.getByPlaceholder("First Name").press("Tab");

    await adminPage.getByPlaceholder("Last Name").fill(generateLastName());
    await adminPage.getByPlaceholder("Last Name").press("Tab");

    await adminPage
        .getByRole('textbox', { name: 'email@example.com' })
        .fill("test@example.com");
    await adminPage.getByRole('textbox', { name: 'email@example.com' }).press("Tab");

    await adminPage.getByPlaceholder("Street Address").fill("Demo");
    await adminPage.getByPlaceholder("Street Address").press("Tab");

    await adminPage.getByPlaceholder("Country").selectOption("IN");

    await adminPage.getByPlaceholder("City").fill("Delhi");
    await adminPage.getByPlaceholder("City").press("Tab");

    await adminPage.getByPlaceholder("State").selectOption("DL");

    await adminPage.getByPlaceholder("Zip/Postcode").fill("123456");
    await adminPage.getByPlaceholder("Zip/Postcode").press("Tab");

    await adminPage.getByPlaceholder("Phone").fill("9876543210");
    await adminPage.getByPlaceholder("Phone").press("Tab");
}


export function getRandomValue(): number {
    const values = [12, 15];
    const randomIndex = Math.floor(Math.random() * values.length);
    return values[randomIndex];
}

export const weekdays: string[] = [
    "monday",
    "tuesday",
    "wednesday",
    "thursday",
    "friday",
    "saturday",
    "sunday"
];


export async function getOrderId(adminPage) {
    const text = await adminPage.locator('p.text-xl').nth(0).textContent();
    if (!text) return null;

    const match = text.match(/#(\d+)/);
    return match ? parseInt(match[1], 10) : null;
}

// by this admin can create time slot for its product 

// this is for timeslot not not for this project
export async function admin_timeslot(adminPage) {

    await adminPage.goto("admin/marketplace/default-delivery-time-slot")

    let i = 0

    while (i < 7) {

        if ((0 <= i) && (i <= 6))
            await adminPage.getByRole("button", { name: " + Add Time Slot " }).click()

        await adminPage.locator('select[name="delivery_day[]"]').nth(i).selectOption(`${weekdays[i]}`);

        await adminPage.locator('input[name="start_time[]"]').nth(i).click()


        await adminPage.getByRole('spinbutton', { name: 'Hour' }).fill("12")

        await adminPage.waitForTimeout(500)

        await adminPage.locator('input[name="end_time[]"]').nth(i).click()
        await adminPage.getByRole('spinbutton', { name: 'Hour' }).fill("22")
        await adminPage.keyboard.press("Enter")
        await adminPage.waitForTimeout(500)

        await adminPage.getByPlaceholder("Quota").nth(i).fill("10")

        await adminPage.locator('select[name="status[]"]').nth(i).selectOption("1")

        await adminPage.waitForTimeout(500)

        i += 1
    }

    await adminPage.locator("input[name='minimum_time_required']").fill("2")

    await adminPage.getByRole("button", { name: " Save Config " }).click()

    await expect(adminPage.getByText("Time Slot is saved successfully")).toBeVisible()

}

export function getRandomBankName() {
  const banks = [
    "State Bank of India",
    "HDFC Bank",
    "ICICI Bank",
    "Axis Bank",
    "Punjab National Bank",
    "Kotak Mahindra Bank",
    "Bank of Baroda",
    "Canara Bank",
    "Union Bank of India",
    "IDFC First Bank",
    "Yes Bank",
    "IndusInd Bank"
  ];

  return banks[Math.floor(Math.random() * banks.length)];
}

export function getRandomAddress() {
  const addresses = [
    "221B Baker Street, London",
    "742 Evergreen Terrace, Springfield",
    "10 Downing Street, London",
    "1600 Pennsylvania Ave NW, Washington, DC",
    "31 MG Road, Bengaluru, Karnataka",
    "55 Park Street, Kolkata, West Bengal",
    "88 Nehru Nagar, Chennai, Tamil Nadu",
    "14 Marine Drive, Mumbai, Maharashtra",
    "92 Sector 18, Noida, Uttar Pradesh",
    "44 Camp Road, Pune, Maharashtra"
  ];

  return addresses[Math.floor(Math.random() * addresses.length)];
}

export function getRandomNumber(digits) {
  if (digits <= 0) return "0";

  const min = Math.pow(10, digits - 1);   // smallest number with given digits
  const max = Math.pow(10, digits) - 1;   // largest number with given digits

  return String(Math.floor(Math.random() * (max - min + 1)) + min);
}






