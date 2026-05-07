/**
 * Convert camelCase → snake_case
 * @param {String} str
 * @returns {String}
 */
export function camelToSnake(str) {
    return str.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
}

/**
 * Convert snake_case → camelCase
 * @param {String} str
 * @returns {String}
 */
export function snakeToCamel(str) {
    return str.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
}

/**
 * Recursively convert object keys from camelCase → snake_case
 * @param {Object|Array} obj
 * @returns {Object|Array}
 */
export function keysToSnake(obj) {
    if (Array.isArray(obj)) {
        return obj.map(v => keysToSnake(v));
    } else if (
        obj !== null
        && typeof obj === 'object'
    ) {
        return Object.keys(obj).reduce((acc, key) => {
            const newKey = camelToSnake(key);

            acc[newKey] = keysToSnake(obj[key]);

            return acc;
        }, {});
    }

    return obj;
}

/**
 * Recursively convert object keys from snake_case → camelCase
 * @param {Object|Array} obj
 * @returns {Object|Array}
 */
export function keysToCamel(obj) {
    if (Array.isArray(obj)) {
        return obj.map(v => keysToCamel(v));
    } else if (
        obj !== null
        && typeof obj === 'object'
    ) {
        return Object.keys(obj).reduce((acc, key) => {
            const newKey = snakeToCamel(key);

            acc[newKey] = keysToCamel(obj[key]);
            
            return acc;
        }, {});
    }

    return obj;
}
