import AbstractType from '@src/helpers/productType/AbstractType';
import Simple from '@src/helpers/productType/Simple';

/**
 * Bundle Product Type
 */
export default class Bundle extends AbstractType {
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
     * @returns {Array|String}
     */
    async prepareForCart(data) {
        if (
            ! data.bundleOptions
            || Object.keys(data.bundleOptions).length === 0
        ) {
            return this.t('pos.common.cart.missing_options');
        }

        data.bundleOptions = this.validateBundleOptionForCart(data.bundleOptions).filter(option => option);

        if (! data.bundleOptions.length) {
            return this.t('pos.common.cart.missing_options');
        }

        let products = await super.prepareForCart(data);

        const childProducts = this.getCartChildProducts(data);

        for (const { productId, quantity } of Object.values(childProducts)) {
            const product = await this.DB.getItem('products', productId);

            if (product.type !== 'simple') {
                return this.t('pos.common.cart.selected_products_simple');
            }

            const simpleTypeInstance = new Simple(product);

            const cartProduct = await simpleTypeInstance.prepareForCart({
                quantity,
                parentId: this.product.id,
            });

            if (typeof cartProduct === 'string') {
                return cartProduct;
            }

            cartProduct[0].parentId = this.product.id;

            const main = products[0];
            const child = cartProduct[0];

            main.price += child.total;
            main.priceInclTax += child.total;
            main.basePrice += child.baseTotal;
            main.basePriceInclTax += child.baseTotal;
            main.total += child.total;
            main.totalInclTax += child.total;
            main.baseTotal += child.baseTotal;
            main.baseTotalInclTax += child.baseTotal;
            main.weight += child.totalWeight;

            products = [...products, ...cartProduct];
        }

        products[0].totalWeight = products[0].weight * products[0].quantity;
        products[0].baseTotalWeight = products[0].totalWeight;

        return products;
    }

    /**
	 * Validate cart item product price and other things.
	 */
	async validateCartItem(item) {
		let basePrice = 0;

        const cart = await this.DB.getCart();

		const children = cart.items.filter(item => {
            return item.parentId = item.id;
        })

        for (const childItem of children) {
            let product = await this.DB.getItem('products', childItem.productId);

            const simpleTypeInstance = new Simple(product);

            await simpleTypeInstance.validateCartItem(item);

            basePrice += childItem.basePrice;
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
     * Compare options
     */
    compareOptions(options1, options2) {
        if (
            options1?.bundleOptions
            && options2?.bundleOptions
        ) {
            return (
                options1.bundleOptions === options2.bundleOptions
                && options1.bundleOptionQty === this.getOptionQuantities(options2)
            );
        }

        return false;
    }

    /**
     * Get child products for bundle cart request
     */
    getCartChildProducts(data) {
        const products = {};

        for (const { bundleOptionId, bundleOptionProductId, qty } of data.bundleOptions) {
            const option = this.product.options.find(o => o.id === bundleOptionId);

            if (! option) {
                continue;
            }

            for (const optionProductId of bundleOptionProductId) {
                const optionProduct = option.products.find(p => p.id === optionProductId);

                if (! optionProduct) {
                    continue;
                }

                const quantity = qty ?? optionProduct.qty;

                if (! products[optionProduct.productId]) {
                    products[optionProduct.productId] = {
                        productId: optionProduct.productId,
                        quantity,
                    };
                } else {
                    products[optionProduct.productId].quantity += quantity;
                }
            }
        }

        return products;
    }

    /**
     * Returns additional information for bundle item quantities.
     */
    getOptionQuantities(data) {
        const optionQuantities = {};

        for (const { bundleOptionId, bundleOptionProductId, qty } of data.bundleOptions) {
            for (const optionProductId of bundleOptionProductId) {
                const option = this.product.options.find(o => o.id === bundleOptionId);

                if (! option) {
                    continue;
                }

                const optionProduct = option.products.find(p => p.id === optionProductId);

                if (! optionProduct) {
                    continue;
                }

                const quantity = qty ?? optionProduct.qty;

                optionQuantities[bundleOptionId] = quantity;
            }
        }

        return optionQuantities;
    }

    /**
     * Recursively clean invalid values
     */
    validateBundleOptionForCart(data) {
        const cleaned = Array.isArray(data) ? [...data] : { ...data };

        for (const key in cleaned) {
            const value = cleaned[key];

            if (
                Array.isArray(value)
                || (
                    typeof value === 'object'
                    && value !== null
                )
            ) {
                cleaned[key] = this.validateBundleOptionForCart(value);
            } else if (value) {
                cleaned[key] = parseInt(value, 10);
            } else {
                delete cleaned[key];
            }
        }

        return cleaned;
    }

    /**
     * Returns additional information for items.
     */
    async getAdditionalOptions(data) {
        const bundleOptionQuantities = {};

        data.attributes = [];

        for (const { bundleOptionId, bundleOptionProductId, qty } of data.bundleOptions) {
            const option = this.product.options.find(o => o.id === bundleOptionId);

            if (! option) {
                continue;
            }

            const labels = [];

            for (const optionProductId of bundleOptionProductId) {
                const optionProduct = option.products.find(p => p.id === optionProductId);

                if (! optionProduct) {
                    continue;
                }

                const quantity = qty ?? optionProduct.qty;

                bundleOptionQuantities[bundleOptionId] = quantity;

                let label = `${quantity} x ${optionProduct.name}`;

                const price = optionProduct.price?.final?.price ?? 0;

                if (price !== 0) {
                    label += ` ${await this.outlet.currency(price)}`;
                }

                labels.push(label);
            }

            if (labels.length > 0) {
                data.attributes.push({
                    attributeName: option.label,
                    optionId: option.id,
                    optionLabel: labels.join(', '),
                });
            }
        }

        data.bundleOptionQty = bundleOptionQuantities;

        return data;
    }
}
