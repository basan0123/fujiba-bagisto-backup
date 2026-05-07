import AbstractType from '@src/helpers/productType/AbstractType';

/**
 * Downloadable Product Type
 */
export default class Downloadable extends AbstractType {
    /**
     * Constructor of the class
     */
    constructor(product) {
        super();

        this.product = product;
    }

    /**
     * Add product. Returns error message if can't prepare product.
     *
     * @param {Object} data
     * @returns {Array|string}
     */
    async prepareForCart(data) {
        if (
            ! data.links
            || data.links.length === 0
        ) {
            return this.t('pos.common.cart.missing_links');
        }

        let products = await super.prepareForCart(data);

        for (const link of this.product.downloadableLinks) {
            if (! data.links.includes(link.id)) {
                continue;
            }

            const priceConverted = await this.outlet.convertPrice(link.price);

            products[0].product.price += priceConverted;
            products[0].product.basePrice += link.price;
            
            const quantity = products[0].quantity;

            products[0].price += priceConverted;
            products[0].priceInclTax += priceConverted;
            products[0].basePrice += link.price;
            products[0].basePriceInclTax += link.price;

            const totalConverted = await this.outlet.convertPrice(priceConverted * quantity);

            products[0].total += totalConverted;
            products[0].totalInclTax += totalConverted;
            products[0].baseTotal += link.price * quantity;
        }

        return products;
    }

    /**
     * Validate cart item product price and other things.
     */
    async validateCartItem(item) {
        let basePrice = this.product.price;

        for (const link of this.product.downloadableLinks) {
            if (! item.additional.links.includes(link.id)) {
                continue;
            }

            basePrice += link.price;
        }

        if (basePrice === item.basePriceInclTax) {
            return;
        }

        item.basePrice = basePrice;
		item.basePriceInclTax = basePrice;

		const price = await this.outlet.convertPrice(basePrice);

		item.price = price;
		item.priceInclTax = price;

		item.baseTotal = basePrice * item.quantity;
		item.baseTotalInclTax = basePrice * item.quantity;

		const total = await this.outlet.convertPrice(basePrice * item.quantity);

		item.total = total;
		item.totalInclTax = total;

		await this.DB.updateItem('cart_items', item);

		return;
    }

    /**
     * Compare options.
     *
     * @param {Object} options1
     * @param {Object} options2
     * @return {boolean}
     */
    compareOptions(options1, options2) {
        if (this.product.id !== options2.productId) {
            return false;
        }

        if (
            options1.links
            && options2.links
        ) {
            return (
                options1.links.length === options2.links.length
                && options1.links.every(link => options2.links.includes(link))
            );
        }

        if (! options1.links) {
            return false;
        }

        if (! options2.links) {
            return false;
        }
    }

    /**
     * Returns additional information for items.
     *
     * @param  {Object} data
     * @return {Object}
     */
    getAdditionalOptions(data) {
        const labels = [];

        this.product.downloadableLinks.forEach(link => {
            if (data.links.includes(link.id)) {
                labels.push(link.title);
            }
        });

        if (! Array.isArray(data.attributes)) {
            data.attributes = [];
        }

        data.attributes.push({
            attributeName: this.t('pos.common.cart.downloads'),
            optionId: 0,
            optionLabel: labels.join(', ')
        });

        return data;
    }
}