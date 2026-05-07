import AbstractType from '@src/helpers/productType/AbstractType';

/**
 * Configurable Product Type
 */
export default class Configurable extends AbstractType {
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
        data.quantity = this.handleQuantity(parseInt(data.quantity));

        if (! data.selectedConfigurableOption) {
            return this.t('pos.common.cart.missing_options');
        }

        data = await this.getQtyRequest(data);

        const childProduct = await this.product.variants.find(variant => {
            return variant.id == data.selectedConfigurableOption;
        });

        const price = childProduct.price;

        const convertedPrice = await this.outlet.convertPrice(childProduct.price);

        return [
            {
                productId: this.product.id,
                sku: this.product.sku,
                name: this.product.name,
                type: this.product.type,
                quantity: data.quantity,
                price: convertedPrice,
                priceInclTax: convertedPrice,
                basePrice: price,
                basePriceInclTax: price,
                total: convertedPrice * data.quantity,
                totalInclTax: convertedPrice * data.quantity,
                baseTotal: price * data.quantity,
                baseTotalInclTax: price * data.quantity,
                weight: childProduct.weight,
                totalWeight: childProduct.weight * data.quantity,
                baseTotalWeight: childProduct.weight * data.quantity,
                discountAmount: 0,
                baseDiscountAmount: 0,
                taxAmount: 0,
                baseTaxAmount: 0,
                additional: await this.getAdditionalOptions(data),
                product: {
					price: convertedPrice,
					basePrice: price
				},
            },
            {
                parentId: this.product.id,
                productId: Number(data.selectedConfigurableOption),
                sku: childProduct.sku,
                name: childProduct.name,
                type: childProduct.type,
                additional: {
                    productId: Number(data.selectedConfigurableOption),
                    parentId: this.product.id,
                },
            },
        ];
    }

    /**
	 * Validate cart item product price and other things.
	 */
	async validateCartItem(item) {
        const childProduct = await this.product.variants.find(variant => {
            return variant.id == item.additional.selectedConfigurableOption;
        });

		const basePrice = childProduct.price;

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
     * Compare cart item options
     *
     * @param {Object} options1
     * @param {Object} options2
     * @returns {boolean}
     */
    compareOptions(options1, options2) {
        if (this.product.id !== options2.productId) {
            return false;
        }

        const opt1 = options1.selectedConfigurableOption;
        const opt2 = options2.selectedConfigurableOption;

        if (
            opt1
            && opt2
        ) {
            return opt1 === opt2;
        }

        if (
            ! opt1
            || ! opt2
        ) {
            return false;
        }
    }

    /**
     * Return additional information for items.
     *
     * @param {Object} data
     * @returns {Object}
     */
    async getAdditionalOptions(data) {
        const childProduct = await this.product.variants.find(variant => variant.id == data.selectedConfigurableOption);

        data.attributes = data.attributes || {};

        for (const attribute of this.product.superAttributes) {
            const option = attribute.options.find(opt => opt.id == childProduct[attribute.code]);

            data.attributes[attribute.code] = {
                attributeName: attribute.adminName,
                optionId: option.id,
                optionLabel: option.adminName,
            };
        }

        return data;
    }
}