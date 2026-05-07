import AbstractType from '@src/helpers/productType/AbstractType';

/**
 * Virtual Product Type
 */
export default class Virtual extends AbstractType {
	/**
     * Constructor of the class
     */
	constructor(product) {
		super();

		this.product = product;
	}

	/**
	 * Prepare product data for cart.
	 *
	 * @param {Object} product
	 * @param {Object} data
	 * @returns {Array|String}
	 */
	async prepareForCart(data) {
		data.quantity = this.handleQuantity(parseInt(data.quantity));

		data = await this.getQtyRequest(data);

        let price = this.getProductPrice();

		if (
			data.customizableOptions
			&& data.customizableOptions.length > 0
		) {
			let formattedCustomizableOptions = this.formatRequestedCustomizableOptions(
				data.customizableOptions,
				this.product.customizableOptions
			);

			let totalPrice = formattedCustomizableOptions.reduce((sum, option) => {
				return sum + (option.totalPrice || 0);
			}, 0);

			price += totalPrice;

			data.formattedCustomizableOptions = formattedCustomizableOptions;
		}

		const convertedPrice = await this.outlet.convertPrice(price);

		const products = [
			{
				productId: this.product.id,
				sku: this.product.sku,
				quantity: data.quantity,
				name: this.product.name,
				price: convertedPrice,
				priceInclTax: convertedPrice,
				basePrice: price,
				basePriceInclTax: price,
				total: convertedPrice * data.quantity,
				totalInclTax: convertedPrice * data.quantity,
				baseTotal: price * data.quantity,
				baseTotalInclTax: price * data.quantity,
				weight: parseFloat(this.product.weight || 0),
				totalWeight: parseFloat(this.product.weight || 0) * data.quantity,
				baseTotalWeight: parseFloat(this.product.weight || 0) * data.quantity,
				discountAmount: 0,
				baseDiscountAmount: 0,
				taxAmount: 0,
				baseTaxAmount: 0,
				type: this.product.type,
				additional: await this.getAdditionalOptions(data),
				product: {
					price: convertedPrice,
					basePrice: price
				},
			},
		];

		return products;
	}

	/**
	 * Validate cart item product price and other things.
	 */
	async validateCartItem(item) {
        let basePrice = this.getProductPrice();

		if (
			item.additional?.customizableOptions
			&& item.additional?.customizableOptions.length > 0
		) {
			let formattedCustomizableOptions = this.formatRequestedCustomizableOptions(
				item.additional?.customizableOptions,
				this.product.customizableOptions
			);

			let totalPrice = formattedCustomizableOptions.reduce((sum, option) => {
				return sum + (option.totalPrice || 0);
			}, 0);

			basePrice += totalPrice;
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
	 * Returns additional information for items.
	 *
	 * @param {Object} data
	 * @returns {Object}
	 */
	async getAdditionalOptions(data) {
		if (
			data.formattedCustomizableOptions
			&& data.formattedCustomizableOptions.length > 0
		) {
			data.attributes = [];

			for (const option of data.formattedCustomizableOptions) {
				const locale = this.cookies.get('locale')?.code || 'en';

				const attributeName = option.label[locale];

				if (['checkbox', 'multiselect'].includes(option.type)) {
					const optionLabel = option.prices
						.map(price => price.label)
						.join(', ')
						.replace(/,([^,]*)$/, ' and$1');

					data.attributes.push({
						attributeType: option.type,
						attributeName: attributeName,
						optionLabel: optionLabel,
					});
				} else {
					data.attributes.push({
						attributeType: option.type,
						attributeName: attributeName,
						optionLabel: option.prices[0]?.label || '',
					});
				}
			}
		}

		return data;
	}

	/**
	 * Compare options.
	 *
	 * @param {Object} options1
	 * @param {Object} options2
	 * @return {Boolean}
	 */
	compareOptions(options1, options2) {
		const hasCustom1 = options1.hasOwnProperty('customizableOptions');
		const hasCustom2 = options2.hasOwnProperty('customizableOptions');

		if (
			hasCustom1
			&& hasCustom2
		) {
			return options1.customizableOptions === options2.customizableOptions;
		}

		if (
			(
				! hasCustom1
				&& hasCustom2
			)
			|| (
				hasCustom1
				&& ! hasCustom2
			)
		) {
			return false;
		}

		if (
			! hasCustom1
			&& ! hasCustom2
		) {
			return this.product.id === options2.productId;
		}

		return false;
	}

	/**
	 * Format the requested customizable options
	 *
	 * @param {Array} requestedCustomizableOptions
	 * @param {Array} customizableOptions
	 * @returns {Array}
	 */
	formatRequestedCustomizableOptions(requestedCustomizableOptions, customizableOptions) {
		const formattedCustomizableOptions = [];

		for (const customizableOption of customizableOptions) {
			const {
				id,
				type,
				isRequired,
				customizableOptionPrices,
				translations
			} = customizableOption;

			const selectedOption = requestedCustomizableOptions.find(opt => opt.id === id);

			if (!selectedOption) {
				continue;
			}

			let selectedValues = selectedOption.value;

			if (! Array.isArray(selectedValues)) {
				selectedValues = [selectedValues];
			}

			switch (type) {
				case 'text':
				case 'textarea':
				case 'date':
				case 'datetime':
				case 'time': {
					const value = selectedValues[0];

					if (
						! isRequired
						&& ! value
					) {
						continue;
					}

					const optionPrice = customizableOptionPrices?.[0] || { id: null, price: 0 };

					formattedCustomizableOptions.push({
						id,
						type,
						label: {
							[translations?.locale || 'en']: translations?.label || ''
						},
						prices: [{
							id: optionPrice.id,
							label: value,
							price: optionPrice.price
						}],
						totalPrice: optionPrice.price
					});

					break;
				}

				case 'checkbox':
				case 'radio':
				case 'select':
				case 'multiselect': {
					if (
						! isRequired
						&& selectedValues.length === 0
					) {
						continue;
					}

					if (
						selectedValues.includes(0)
						|| selectedValues.includes('0')
					) {
						continue;
					}

					const numericSelected = selectedValues.map(v => Number(v));

					const optionPrices = (customizableOptionPrices || [])
						.filter(p => numericSelected.includes(p.id));

					formattedCustomizableOptions.push({
						id,
						type,
						label: {
							[translations?.locale || 'en']: translations?.label || ''
						},
						prices: optionPrices.map(p => ({
							id: p.id,
							label: p.label,
							price: p.price
						})),
						totalPrice: optionPrices.reduce((sum, p) => sum + (p.price || 0), 0)
					});

					break;
				}
			}
		}

		return formattedCustomizableOptions;
    }

    /**
     * Get product price considering special price.
     *
     * @return {Number}
     */
    getProductPrice() {
        let price = this.product.price;

        const { specialPrice, specialPriceFrom, specialPriceTo } = this.product;

        if (specialPrice) {
            const today = new Date().setHours(0, 0, 0, 0);
            const from = specialPriceFrom ? new Date(specialPriceFrom).setHours(0, 0, 0, 0) : null;
            const to = specialPriceTo ? new Date(specialPriceTo).setHours(0, 0, 0, 0) : null;

            // If both dates exist → apply only when today is between the range
            if (from && to) {
                if (today >= from && today <= to) {
                    price = specialPrice;
                }
            } else {
                price = specialPrice;
            }
        }

        return price;
    }
}
