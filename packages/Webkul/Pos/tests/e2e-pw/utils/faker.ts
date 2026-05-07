import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const usedNames = new Set();
const usedEmails = new Set();
const usedNumbers = new Set();
const usedSlugs = new Set();
const usedCurrencies = new Set();
const usedLocales = new Set();

export function generateName() {
    const adjectives = [
        "Cool",
        "Smart",
        "Fast",
        "Sleek",
        "Innovative",
        "Shiny",
        "Bold",
        "Elegant",
        "Epic",
        "Mystic",
        "Brilliant",
        "Luminous",
    ];

    const nouns = [
        "Star",
        "Vision",
        "Echo",
        "Spark",
        "Horizon",
        "Nova",
        "Shadow",
        "Wave",
        "Pulse",
        "Vortex",
        "Zenith",
        "Element",
    ];

    let name = "";

    do {
        const adj = adjectives[Math.floor(Math.random() * adjectives.length)];
        const noun = nouns[Math.floor(Math.random() * nouns.length)];

        name = `${adj} ${noun}`;
    } while (usedNames.has(name));

    usedNames.add(name);

    return name;
}

export function generateFirstName() {
    const firstNames = [
        "James",
        "Emma",
        "Liam",
        "Olivia",
        "Noah",
        "Ava",
        "William",
        "Sophia",
        "Benjamin",
        "Isabella",
        "Lucas",
        "Mia",
    ];

    return firstNames[Math.floor(Math.random() * firstNames.length)];
}

export function generateLastName() {
    const lastNames = [
        "Smith",
        "Johnson",
        "Brown",
        "Williams",
        "Jones",
        "Garcia",
        "Miller",
        "Davis",
        "Rodriguez",
        "Martinez",
        "Hernandez",
        "Lopez",
    ];

    return lastNames[Math.floor(Math.random() * lastNames.length)];
}

export function generateFullName() {
    return `${generateFirstName()} ${generateLastName()}`;
}

export function generateEmail() {
    const adjectives = [
        "Cool",
        "Smart",
        "Fast",
        "Sleek",
        "Innovative",
        "Shiny",
        "Bold",
        "Elegant",
        "Epic",
        "Mystic",
        "Brilliant",
        "Luminous",
    ];

    const nouns = [
        "Star",
        "Vision",
        "Echo",
        "Spark",
        "Horizon",
        "Nova",
        "Shadow",
        "Wave",
        "Pulse",
        "Vortex",
        "Zenith",
        "Element",
    ];

    let email = "";

    do {
        const adj = adjectives[Math.floor(Math.random() * adjectives.length)];
        const noun = nouns[Math.floor(Math.random() * nouns.length)];
        const number = Math.floor(1000 + Math.random() * 9000);

        email = `${adj}${noun}${number}@example.com`.toLowerCase();
    } while (usedEmails.has(email));

    usedEmails.add(email);

    return email;
}

export function generatePhoneNumber() {
    let phoneNumber;

    do {
        phoneNumber = Math.floor(6000000000 + Math.random() * 4000000000);
    } while (usedNumbers.has(phoneNumber));

    usedNumbers.add(phoneNumber);

    return `${phoneNumber}`;
}

export function generateSKU() {
    const letters = Array.from({ length: 3 }, () =>
        String.fromCharCode(65 + Math.floor(Math.random() * 26))
    ).join("");

    const numbers = Math.floor(1000 + Math.random() * 9000);

    return `${letters}${numbers}`;
}

export function generateSlug(delimiter = "-") {
    let slug;

    do {
        const name = generateName();

        const randomStr = Math.random().toString(36).substring(2, 8);

        slug = `${name
            .toLowerCase()
            .replace(/\s+/g, delimiter)}${delimiter}${randomStr}`;
    } while (usedSlugs.has(slug));

    usedSlugs.add(slug);

    return slug;
}

export function generateRandomCode(): string {
    const digits = "0123456789";
    const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    // Pick two random digits
    const digit1 = digits[Math.floor(Math.random() * digits.length)];
    const digit2 = digits[Math.floor(Math.random() * digits.length)];

    // Pick one random letter
    const letter = letters[Math.floor(Math.random() * letters.length)];

    // Combine and shuffle
    const chars = [digit1, digit2, letter];

    // Shuffle the array
    for (let i = chars.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [chars[i], chars[j]] = [chars[j], chars[i]];
    }

    return chars.join('');
}

export function generateDescription(length = 255) {
    const phrases = [
        "An innovative and sleek design.",
        "Built for speed and efficiency.",
        "Experience the future today.",
        "A perfect blend of style and power.",
        "Engineered to perfection.",
        "Designed for those who dream big.",
        "Unleash creativity with this masterpiece.",
        "A game-changer in every way.",
        "Smart, fast, and reliable.",
        "The perfect companion for your journey.",
        "Crafted with precision and excellence.",
        "Innovation that redefines possibilities.",
        "Enhancing your experience like never before.",
        "Where technology meets elegance.",
        "Power, performance, and perfection combined.",
        "Redefining the way you experience the world.",
        "A masterpiece of engineering and design.",
        "Unmatched quality and exceptional performance.",
        "Designed to elevate your lifestyle.",
        "Beyond expectations, beyond limits.",
    ];

    let description = "";

    while (description.length < length) {
        let phrase = phrases[Math.floor(Math.random() * phrases.length)];

        if (description.length + phrase.length <= length) {
            description += (description ? " " : "") + phrase;
        } else {
            description +=
                " " + phrase.substring(0, length - description.length);
            break;
        }
    }

    return description.trim();
}

export function generateAttributeCode(length: number): string {
    const letters: string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const allCharacters: string = letters + '0123456789';

    if (length < 1) {
        throw new Error('Length must be at least 1');
    }

    let code: string = '';

    // First character must be a letter
    const firstCharIndex: number = Math.floor(Math.random() * letters.length);
    code += letters[firstCharIndex];

    // Remaining characters can be letters or digits
    for (let i = 1; i < length; i++) {
        const randomIndex: number = Math.floor(Math.random() * allCharacters.length);
        code += allCharacters[randomIndex];
    }

    return code;
}



/**
 * Generates a random numeric string with specified length or within a numeric range.
 * 
 * @param {number} [length=10] - The length of the random string (used only when min and max are undefined)
 * @param {number|null|undefined} [min=undefined] - Minimum value (inclusive) when generating a number in range
 * @param {number|null|undefined} [max=undefined] - Maximum value (inclusive) when generating a number in range
 * @returns {string} - Random numeric string
 */
export function generateRandomNumericString(length: number = 10, min?: number | null, max?: number | null): string {
    // Generate a number within range
    if (min !== null && min !== undefined && max !== null && max !== undefined) {
        // Input validation
        if (!Number.isInteger(min) || !Number.isInteger(max)) {
            throw new Error("Min and max must be integers when provided.");
        }

        if (min > max) {
            throw new Error("Min value cannot be greater than max value.");
        }

        // Generate a random number within the range and convert to string
        return Math.floor(Math.random() * (max - min + 1) + min).toString();
    }

    // Generate a random string of specified length
    if (!Number.isInteger(length) || length <= 0) {
        throw new Error("Length must be a positive integer.");
    }

    // More efficient method for generating random digits
    let result = '';
    for (let i = 0; i < length; i++) {
        result += Math.floor(Math.random() * 10);
    }

    // Ensure first character is not zero for a consistent length string
    if (length > 1 && result[0] === '0') {
        result = String(1 + Math.floor(Math.random() * 9)) + result.substring(1);
    }

    return result;
}

export function generateHostname() {
    const words = [
        "tech",
        "cloud",
        "byte",
        "stream",
        "nexus",
        "core",
        "pulse",
        "data",
        "sync",
        "wave",
        "hub",
        "zone",
    ];

    const domains = [".com", ".net", ".io", ".ai", ".xyz", ".co"];

    const part1 = words[Math.floor(Math.random() * words.length)];
    const part2 = words[Math.floor(Math.random() * words.length)];
    const domain = domains[Math.floor(Math.random() * domains.length)];


    return `https://${part1}${part2}${domain}`;

}

export function generateCurrency() {
    const currencies = [
        {
            name: "US Dollar",
            code: "USD",
            symbol: "$",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Euro",
            code: "EUR",
            symbol: "€",
            decimalDigits: "2",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "British Pound",
            code: "GBP",
            symbol: "£",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Japanese Yen",
            code: "JPY",
            symbol: "¥",
            decimalDigits: "0",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Australian Dollar",
            code: "AUD",
            symbol: "A$",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Canadian Dollar",
            code: "CAD",
            symbol: "C$",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Swiss Franc",
            code: "CHF",
            symbol: "CHF",
            decimalDigits: "2",
            groupSeparator: "'",
            decimalSeparator: ".",
        },
        {
            name: "Chinese Yuan",
            code: "CNY",
            symbol: "¥",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Indian Rupee",
            code: "INR",
            symbol: "₹",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "South Korean Won",
            code: "KRW",
            symbol: "₩",
            decimalDigits: "0",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Mexican Peso",
            code: "MXN",
            symbol: "MX$",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Russian Ruble",
            code: "RUB",
            symbol: "₽",
            decimalDigits: "2",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "Brazilian Real",
            code: "BRL",
            symbol: "R$",
            decimalDigits: "2",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "South African Rand",
            code: "ZAR",
            symbol: "R",
            decimalDigits: "2",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "New Zealand Dollar",
            code: "NZD",
            symbol: "NZ$",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Singapore Dollar",
            code: "SGD",
            symbol: "S$",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Hong Kong Dollar",
            code: "HKD",
            symbol: "HK$",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Norwegian Krone",
            code: "NOK",
            symbol: "kr",
            decimalDigits: "2",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "Swedish Krona",
            code: "SEK",
            symbol: "kr",
            decimalDigits: "2",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "Danish Krone",
            code: "DKK",
            symbol: "kr",
            decimalDigits: "2",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Polish Złoty",
            code: "PLN",
            symbol: "zł",
            decimalDigits: "2",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "Turkish Lira",
            code: "TRY",
            symbol: "₺",
            decimalDigits: "2",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Thai Baht",
            code: "THB",
            symbol: "฿",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Indonesian Rupiah",
            code: "IDR",
            symbol: "Rp",
            decimalDigits: "0",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Malaysian Ringgit",
            code: "MYR",
            symbol: "RM",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Philippine Peso",
            code: "PHP",
            symbol: "₱",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Israeli Shekel",
            code: "ILS",
            symbol: "₪",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Saudi Riyal",
            code: "SAR",
            symbol: "﷼",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "UAE Dirham",
            code: "AED",
            symbol: "د.إ",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Czech Koruna",
            code: "CZK",
            symbol: "Kč",
            decimalDigits: "2",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "Hungarian Forint",
            code: "HUF",
            symbol: "Ft",
            decimalDigits: "0",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "Romanian Leu",
            code: "RON",
            symbol: "lei",
            decimalDigits: "2",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Bulgarian Lev",
            code: "BGN",
            symbol: "лв",
            decimalDigits: "2",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "Croatian Kuna",
            code: "HRK",
            symbol: "kn",
            decimalDigits: "2",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Icelandic Króna",
            code: "ISK",
            symbol: "kr",
            decimalDigits: "0",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Ukrainian Hryvnia",
            code: "UAH",
            symbol: "₴",
            decimalDigits: "2",
            groupSeparator: " ",
            decimalSeparator: ",",
        },
        {
            name: "Pakistani Rupee",
            code: "PKR",
            symbol: "₨",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Bangladeshi Taka",
            code: "BDT",
            symbol: "৳",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Sri Lankan Rupee",
            code: "LKR",
            symbol: "Rs",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Nepalese Rupee",
            code: "NPR",
            symbol: "₨",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Kuwaiti Dinar",
            code: "KWD",
            symbol: "د.ك",
            decimalDigits: "3",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Qatari Riyal",
            code: "QAR",
            symbol: "ر.ق",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Omani Rial",
            code: "OMR",
            symbol: "ر.ع.",
            decimalDigits: "3",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Bahraini Dinar",
            code: "BHD",
            symbol: "ب.د",
            decimalDigits: "3",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Argentine Peso",
            code: "ARS",
            symbol: "$",
            decimalDigits: "2",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Chilean Peso",
            code: "CLP",
            symbol: "$",
            decimalDigits: "0",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Colombian Peso",
            code: "COP",
            symbol: "$",
            decimalDigits: "0",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
        {
            name: "Peruvian Sol",
            code: "PEN",
            symbol: "S/.",
            decimalDigits: "2",
            groupSeparator: ",",
            decimalSeparator: ".",
        },
        {
            name: "Venezuelan Bolívar",
            code: "VES",
            symbol: "Bs.",
            decimalDigits: "2",
            groupSeparator: ".",
            decimalSeparator: ",",
        },
    ];

    if (usedCurrencies.size >= currencies.length) {
        throw new Error("All currencies have been used.");
    }

    let currency;

    do {
        const randomIndex = Math.floor(Math.random() * currencies.length);

        currency = currencies[randomIndex];
    } while (usedCurrencies.has(currency.code));

    usedCurrencies.add(currency.code);

    return currency;
}

export function generateLocale() {
    const locales = [
        {
            name: "English",
            code: "en",
            direction: "LTR"
        },
        {
            name: "French",
            code: "fr",
            direction: "LTR"
        },
        {
            name: "Spanish",
            code: "es",
            direction: "LTR"
        },
        {
            name: "Arabic",
            code: "ar",
            direction: "RTL"
        },
        {
            name: "Hebrew",
            code: "he",
            direction: "RTL"
        },
        {
            name: "Japanese",
            code: "ja",
            direction: "LTR"
        },
        {
            name: "Chinese",
            code: "zh_CN",
            direction: "LTR"
        },
        {
            name: "Hindi",
            code: "hi_IN",
            direction: "LTR"
        },
        {
            name: "Bengali",
            code: "bn",
            direction: "LTR"
        },
        {
            name: "German",
            code: "de",
            direction: "LTR"
        },
        {
            name: "Persian",
            code: "fa",
            direction: "LTR"
        },
        {
            name: "Italian",
            code: "it",
            direction: "LTR"
        },
        {
            name: "Dutch",
            code: "nl",
            direction: "LTR"
        },
    ];

    if (usedLocales.size >= locales.length) {
        throw new Error("All locales have been used.");
    }

    let locale;

    do {
        const randomIndex = Math.floor(Math.random() * locales.length);
        locale = locales[randomIndex];
    } while (usedLocales.has(locale.code));

    usedLocales.add(locale.code);

    return locale;
}

export function randomElement(array) {
    return array[Math.floor(Math.random() * array.length)];
}


//  this is getImage file of uploading image while creating product
export function getImageFile(
    directory = path.resolve(__dirname, "../utils/images")) {
    if (!fs.existsSync(directory)) {
        throw new Error(`Directory does not exist: ${directory}`);
    }

    const files = fs.readdirSync(directory);
    const imageFiles = files.filter((file) =>
        /\.(gif|jpeg|jpg|png|svg|webp)$/i.test(file)
    );

    if (!imageFiles.length) {
        throw new Error("No image files found in the directory.");
    }

    const randomIndex = Math.floor(Math.random() * imageFiles.length);

    return path.join(directory, imageFiles[randomIndex]);
}



export function generateRandomDateTime(end = new Date(new Date().setFullYear(new Date().getFullYear() + 10))) {
    const start = new Date();
    const date = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

export function generateLocation() {
    const location = [
        "New York",
        "Los Angeles",
        "Chicago",
        "Houston",
        "Phoenix",
        "Philadelphia",
        "San Antonio",
        "San Diego",
        "Dallas",
        "San Jose",
        "Austin",
        "Jacksonville",
        "San Francisco",
        "Indianapolis",
        "Columbus",
        "Fort Worth",
    ];

    return location[Math.floor(Math.random() * location.length)];
}


// this is created by me and this is nothing but a simple fumction which convert name into links but somehow it dont works in
// in playwright  but a good reusable function thats why i kept here 

function conv(name: string): string {
    let result = "";

    for (const char of name) {
        if (char !== " ") {
            result += char.toLowerCase();
        } else {
            result += "-";
        }
    }

    return result
}

//  this is for generating the product price , and inventories

export function getRandomAverageProductPrice(min = 500, max = 3000): number {
  const isFloat = Math.random() < 0.3; // 30% chance for float

  const price = Math.random() * (max - min) + min;

  return isFloat ? parseFloat(price.toFixed(2)) : Math.floor(price);
  
}

export function generateOutletName(): string {
    const prefixes = [
        "Green",
        "Urban",
        "Prime",
        "Global",
        "Next",
        "Metro",
        "Fresh",
        "Elite",
        "Modern",
        "Nova",
        "Bright",
        "Fusion"
    ];

    const nouns = [
        "Mart",
        "Store",
        "Outlet",
        "Depot",
        "Corner",
        "Market",
        "Shop",
        "Point",
        "Bazaar",
        "Center"
    ];

    const usedOutletNames = new Set<string>();
    let name: string;

    do {
        const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
        const noun = nouns[Math.floor(Math.random() * nouns.length)];
        name = `${prefix} ${noun}`;
    } while (usedOutletNames.has(name));

    usedOutletNames.add(name);

    return name;
}

export function generateGSTNumber(): string {
    const stateCode = Math.floor(1 + Math.random() * 37) // 01 to 37 (valid state codes in India)
        .toString()
        .padStart(2, '0');

    const panChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const panDigits = '0123456789';

    const pan = [
        panChars[Math.floor(Math.random() * panChars.length)], // 1st letter
        panChars[Math.floor(Math.random() * panChars.length)], // 2nd letter
        panChars[Math.floor(Math.random() * panChars.length)], // 3rd letter
        panChars[Math.floor(Math.random() * panChars.length)], // 4th letter
        panChars[Math.floor(Math.random() * panChars.length)], // 5th letter
        panDigits[Math.floor(Math.random() * panDigits.length)], // 6th digit
        panDigits[Math.floor(Math.random() * panDigits.length)], // 7th digit
        panDigits[Math.floor(Math.random() * panDigits.length)], // 8th digit
        panDigits[Math.floor(Math.random() * panDigits.length)], // 9th digit
        panChars[Math.floor(Math.random() * panChars.length)], // 10th letter
    ].join('');

    const entityNumber = Math.floor(Math.random() * 9) + 1; // 1-9 (usually 1 for first entity)
    const defaultZ = 'Z';
    const checksum = panChars[Math.floor(Math.random() * panChars.length)]; // Fake checksum

    return `${stateCode}${pan}${entityNumber}${defaultZ}${checksum}`;
}
