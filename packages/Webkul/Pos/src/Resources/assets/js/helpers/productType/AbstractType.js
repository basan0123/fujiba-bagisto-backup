import { useIndexedDB } from '@src/composable/indexed-db';
import Cart from '@src/helpers/Cart';
import { useCookies } from '@src/composable/cookies';
import { useOutlet } from '@src/composable/outlet';
import I18n from '@src/plugins/i18n';

/**
 * Abstract Product Type
 */
export default class AbstractType {
	/**
	 * Constructor of the class
	 */
	constructor(product) {
		this.DB = useIndexedDB();

		this.product = product;

		this.cookies = useCookies();

		this.outlet = useOutlet();

		this.t = I18n.global.t;
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

		const price = this.product.price;

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
					basePrice: price,
					name: this.product.name,
					images: this.product.images,
				},
			},
		];

		return products;
	}

	/**
	 * Validate cart item product price and other things.
	 */
	async validateCartItem(item) {
		const basePrice = this.product.price;

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
	 * Handle quantity to ensure it's at least 1.
	 *
	 * @param {number} quantity
	 * @returns {number}
	 */
	handleQuantity(quantity) {
		return quantity > 0 ? quantity : 1;
	}

	/**
	 * Get request quantity.
	 *
	 * @param {Object} data
	 * @returns {Object}
	 */
	async getQtyRequest(data) {
		const cart = new Cart();

		await cart.setCart();

		const item = await cart.getItemByProduct({ additional: data });

		if (item) {
			data.quantity += item.quantity;
		}

		return data;
	}

	/**
	 * Get additional options for product type.
	 *
	 * @param {Object} data
	 * @returns {Object}
	 */
	async getAdditionalOptions(data) {
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
		if (this.product.id !== options2.productId) {
			return false;
		} else {
			if (
				options1.hasOwnProperty('parentId')
				&& options2.hasOwnProperty('parentId')
			) {
				return options1.parentId === options2.parentId;
			} else if (
				options1.hasOwnProperty('parentId')
				&& ! options2.hasOwnProperty('parentId')
			) {
				return false;
			} else if (
				options2.hasOwnProperty('parentId')
				&& ! options1.hasOwnProperty('parentId')
			) {
				return false;
			}
		}

		return true;
	}
}