import { useIndexedDB } from '@src/composable/indexed-db';
import { useCookies } from '@src/composable/cookies';

/**
 * Tax Helper Class
 */
export default class Tax {
    /**
     * Tax rate precision.
     */
    static TAX_RATE_PRECISION = 4;

    /**
     * Tax amount precision.
     */
    static TAX_AMOUNT_PRECISION = 2;

    /**
     * Constructor of the class
     */
    constructor() {
        this.DB = useIndexedDB();

        this.cookies = useCookies();
    }

    /**
     * Is product prices are tax inclusive.
     */
    async isInclusiveTaxProductPrices() {
        const value = await this.getConfigData('sales.taxes.calculation.product_prices');

        return value === 'including_tax';
    }

    /**
     * Get shipping origin from core config.
     */
    async getShippingOriginAddress() {
        const country = await this.getConfigData('sales.shipping.origin.country');
        const state = await this.getConfigData('sales.shipping.origin.state');
        const postcode = await this.getConfigData('sales.shipping.origin.postcode');

        return {
            country: country || this.cookies.get('default_country')?.toUpperCase(),
            state,
            postcode
        };
    }

    /**
     * Get default address from core config.
     */
    async getDefaultAddress() {
        const country = await this.getConfigData('sales.taxes.default_destination_calculation.country');
        const state = await this.getConfigData('sales.taxes.default_destination_calculation.state');
        const postcode = await this.getConfigData('sales.taxes.default_destination_calculation.post_code');

        return {
            country: country || this.cookies.get('default_country')?.toUpperCase(),
            state,
            postcode
        };
    }

    /**
     * Check if tax is applicable for the current address and execute operation
     * @param {Object} taxCategory
     * @param {Object} address
     * @param {Function} operation
     */
    isTaxApplicableInCurrentAddress(taxCategory, address, operation) {
        if (! address?.country) {
            return;
        }

        const taxRates = taxCategory.taxRates
            .filter(rate => rate.country === address.country)
            .sort((a, b) => b.taxRate - a.taxRate);

        if (! taxRates.length) {
            return;
        }

        for (const rate of taxRates) {
            if (
                rate.state && rate.state !== '*'
                && rate.state !== address.state
            ) {
                continue;
            }

            let haveTaxRate = false;

            if (! rate.isZip) {
                if (! rate.zipCode
                    || ['*', address.postcode].includes(rate.zipCode)
                ) {
                    haveTaxRate = true;
                }
            } else {
                if (
                    address.postcode >= rate.zipFrom
                    && address.postcode <= rate.zipTo
                ) {
                    haveTaxRate = true;
                }
            }

            if (haveTaxRate) {
                operation(rate);

                break;
            }
        }
    }

    /**
     * Get configuration from index db
     * @param {String} code
     */
    async getConfigData(code) {
        const data = await this.DB.getItem('configurations', 1);

        return data?.configurations?.find(config => config.code == code)?.value;
    }
}
