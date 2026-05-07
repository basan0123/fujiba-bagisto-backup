import AbstractType from '@src/helpers/productType/AbstractType';
import Simple from '@src/helpers/productType/Simple';

/**
 * Grouped Product Type
 */
export default class Grouped extends AbstractType {
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
            ! data.qty
            || ! data.qty.length
        ) {
            return this.t('pos.common.cart.missing_options');
        }

        const cartProductsList = [];

        for (const { productId, quantity } of data.qty) {
            if (
                ! quantity
                || quantity <= 0
            ) {
                continue;
            }

            const product = await this.DB.getItem('products', productId);

            if (product.type !== 'simple') {
                return this.t('pos.common.cart.selected_products_simple');
            }

            const simpleTypeInstance = new Simple(product);

            const cartProducts = await simpleTypeInstance.prepareForCart({
                productId: productId,
                quantity: quantity,
            });

            if (typeof cartProducts === 'string') {
                return cartProducts;
            }

            cartProductsList.push(cartProducts);
        }

        const products = cartProductsList.flat();

        if (! products.length) {
            return this.t('pos.common.cart.qty_missing');
        }

        return products;
    }
}