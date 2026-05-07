import { useIndexedDB } from '@src/composable/indexed-db';
import { useOutlet } from '@src/composable/outlet';
import { pushHeartbeat } from '@src/composable/customer-display-tokens';
import Tax from '@src/helpers/Tax';
import Booking from '@src/helpers/productType/Booking';
import Bundle from '@src/helpers/productType/Bundle';
import Configurable from '@src/helpers/productType/Configurable';
import Downloadable from '@src/helpers/productType/Downloadable';
import Grouped from '@src/helpers/productType/Grouped';
import Simple from '@src/helpers/productType/Simple';
import Virtual from '@src/helpers/productType/Virtual';

/**
 * Cart Helper Class
 */
export default class Cart {
    /**
     * Constructor of the class
     */
    constructor() {
        this.DB = useIndexedDB();

        this.outlet = useOutlet();
    }

    /**
     * Set Cart
     */
    async setCart() {
        const cart = await this.DB.getAllItems('cart');

        if (
            ! cart
            || cart.length === 0
        ) {
            this.cart = null;
        } else {
            this.cart = cart[0];

            const cartItems = await this.DB.getAllItems('cart_items');

            this.cart.allItems = cartItems.filter(item => item.cartId === this.cart.id);

            this.cart.items = this.cart.allItems.filter(item => ! item.parentId);

            this.DB.updateItem('cart', this.cart);
        }
    }

    /**
     * Create Cart
     */
    async createCart() {
        const customer = await this.DB.getCartCustomer();

        await this.DB.addItem('cart', {
            isGuest: 0,
            customerId: customer.id,
            customerFirstName: customer.firstName,
            customerLastName: customer.lastName,
            customerEmail: customer.email,
            items: [],
            allItems: [],
        });

        await this.setCart();
    }

    /**
     * Add To Cart
     *
     * @param {Object} data
     */
    async addToCart(data) {
        await this.setCart();

        const product = await this.DB.getItem('products', data.productId);

        await this.addProduct(product, data);
    }

    /**
     * Add Product
     *
     * @param {Object} product
     * @param {Object} data
     */
    async addProduct(product, data) {
        if (! this.cart) {
            await this.createCart();
        }

        const typeInstance = this.getProductTypeInstance(product);

        const cartProducts = await typeInstance.prepareForCart({
            ...data,
            cartId: this.cart.id
        });        

        if (typeof cartProducts === 'string') {
            if (! this.cart?.all_items?.length) {
                await this.removeCart();
            } else {
                await this.setCart();

                await this.collectTotals();
            }

            throw new Error(cartProducts);
        } else {
            let parentCartItem = null;

            for (const cartProduct of cartProducts) {
                let cartItem = await this.getItemByProduct(cartProduct, data);

                if (cartProduct.hasOwnProperty('parentId')) {
                    cartProduct.parentId = parentCartItem?.id || null;
                }

                const newItemId = `item_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;

                if (! cartItem) {
                    await this.DB.addItem('cart_items', {
                        ...cartProduct,
                        id: newItemId,
                        cartId: this.cart.id,
                    });

                    cartItem = await this.DB.getItem('cart_items', newItemId);
                } else {
                    if (
                        cartProduct.parentId
                        && cartItem.parentId != (parentCartItem ? parentCartItem.id : undefined)
                    ) {
                        await this.DB.addItem('cart_items', {
                            ...cartProduct,
                            id: newItemId,
                            cartId: this.cart.id,
                        });

                        cartItem = await this.DB.getItem('cart_items', newItemId);
                    } else {
                        await this.DB.updateItem('cart_items', {
                            ...cartProduct,
                            id: cartItem.id,
                            cartId: this.cart.id,
                        });

                        cartItem = await this.DB.getItem('cart_items', cartItem.id);
                    }
                }

                if (! parentCartItem) {
                    parentCartItem = cartItem;
                }
            }
        }

        await this.setCart();

        await this.collectTotals();

        return this.cart;
    }

    /**
     * Remove Cart
     */
    async removeCart() {
        await this.setCart();

        if (! this.cart) {
            return;
        }

        await this.DB.deleteAllItems('cart');

        await this.DB.deleteAllItems('cart_items');

        this.cart = null;
    }

    /**
     * Update cart items information.
     * @param {Object} data 
     * @returns {Boolean}
     */
    async updateItems(data) {
        await this.setCart();

        if (! this.cart) {
            return false;
        }

        for (const { cartItemId, customPrice, quantity } of data.qty) {
            let item = await this.DB.getItem('cart_items', String(cartItemId));

            await this.DB.updateItem('cart_items', {...item, customPrice})

            item = await this.DB.getItem('cart_items', String(cartItemId));

            if (! item) {
                continue;
            }

            await this.DB.updateItem('cart_items', {
                ...item,
                quantity: quantity,
                total: await this.outlet.convertPrice(item.basePrice * quantity),
                totalInclTax: await this.outlet.convertPrice(item.basePriceInclTax * quantity),
                baseTotal: item.basePrice * quantity,
                baseTotalInclTax: item.basePriceInclTax * quantity,
                totalWeight: item.weight * quantity,
                baseTotalWeight: item.weight * quantity,
                additional: {
                    ...item.additional,
                    quantity: quantity,
                }
            });

            await this.setCart();
        }

        await this.collectTotals();

        return true;
    }

    /**
     * Remove Item
     *
     * @param {Number} itemId
     */
    async removeItem(itemId) {
        await this.setCart();

        if (! this.cart) {
            return;
        }

        const childItems = this.cart.allItems.filter(item => item.parentId === itemId);

        const updatedAllItems = this.cart.allItems.filter(
            item => item.id !== itemId && item.parentId !== itemId
        );

        await this.DB.deleteItem('cart_items', itemId);

        for (const child of childItems) {
            await this.DB.deleteItem('cart_items', child.id);
        }

        if (! updatedAllItems.length) {
            await this.DB.deleteAllItems('cart');

            this.cart = null;

            return;
        }

        await this.DB.updateItem('cart', {
            ...this.cart,
            items: updatedAllItems.filter(item => !item.parentId),
            allItems: updatedAllItems,
        });

        await this.setCart();

        await this.collectTotals();
    }

    /**
     * Collect Totals
     */
    async collectTotals() {
        if (! (await this.validateItems())) {
            await this.setCart();
        }        

        if (! this.cart) {
            return;
        }

        await this.calculateItemsTax();

        await this.setCart();

        this.cart.subTotal = this.cart.baseSubTotal = 0;
        this.cart.subTotalInclTax = this.cart.baseSubTotalInclTax = 0;

        this.cart.grandTotal = this.cart.baseGrandTotal = 0;
        this.cart.taxTotal = this.cart.baseTaxTotal = 0;

        this.cart.discountAmount = this.cart.baseDiscountAmount = 0;

        this.cart.shippingAmount = this.cart.baseShippingAmount = 0;
        this.cart.shippingAmountInclTax = this.cart.baseShippingAmountInclTax = 0;

        let quantities = 0;

        this.cart.items.forEach(item => {
            this.cart.discountAmount += item.discountAmount;
            this.cart.baseDiscountAmount += item.baseDiscountAmount;

            this.cart.taxTotal += item.taxAmount;
            this.cart.baseTaxTotal += item.baseTaxAmount;

            this.cart.subTotal += parseFloat(item.total);
            this.cart.baseSubTotal += parseFloat(item.baseTotal);

            this.cart.subTotalInclTax += parseFloat(item.totalInclTax);
            this.cart.baseSubTotalInclTax += parseFloat(item.baseTotalInclTax);

            quantities += item.quantity;
        });

        this.cart.itemsQty = quantities;
        this.cart.itemsCount = this.cart.items.length;

        this.cart.grandTotal = this.cart.subTotal + this.cart.taxTotal - this.cart.discountAmount;
        this.cart.baseGrandTotal = this.cart.baseSubTotal + this.cart.baseTaxTotal - this.cart.baseDiscountAmount;

        this.cart.discountAmount = Number(this.cart.discountAmount.toFixed(2));
        this.cart.baseDiscountAmount = Number(this.cart.baseDiscountAmount.toFixed(2));

        this.cart.subTotal = Number(this.cart.subTotal.toFixed(2));
        this.cart.baseSubTotal = Number(this.cart.baseSubTotal.toFixed(2));

        this.cart.subTotalInclTax = Number(this.cart.subTotalInclTax.toFixed(2));
        this.cart.baseSubTotalInclTax = Number(this.cart.baseSubTotalInclTax.toFixed(2));

        this.cart.grandTotal = Number(this.cart.grandTotal.toFixed(2));
        this.cart.baseGrandTotal = Number(this.cart.baseGrandTotal.toFixed(2));

        await this.DB.updateItem('cart', this.cart);

        await this.setCart();

        // Push to customer display (no-op if no display paired).
        pushHeartbeat({
            subtotal: Number(this.cart.subTotal) || 0,
            tax:      Number(this.cart.taxTotal) || 0,
            total:    Number(this.cart.grandTotal) || 0,
            items:    Number(this.cart.itemsQty) || 0,
            currency: this.cart.cartCurrencyCode || 'JPY',
        });
    }

    /**
     * Get Item By Product
     *
     * @param {Object} data
     * @param {Object} parentData
     * @returns {Object|null}
     */
    async getItemByProduct(data, parentData = null) {
        const items = this.cart?.allItems || [];

        for (const item of items) {
            let product = await this.DB.getItem('products', item.productId);

            if (! product) {
                const parentProduct = await this.DB.getItem('products', item?.additional?.parentId);

                product = parentProduct.variants.find(variant => variant.id === item.productId);
            }

            const typeInstance = this.getProductTypeInstance(product);

            if (typeInstance.compareOptions(item.additional, data.additional)) {
                if (! data.additional?.parentId) {
                    return item;
                }

                const parentItem = items.find(i => i.id === item.parentId);

                const parentProduct = await this.DB.getItem('products', parentItem.productId);

                const parentTypeInstance = this.getProductTypeInstance(parentProduct);

                if (parentTypeInstance.compareOptions(parentItem.additional, parentData?.additional || data.additional)) {
                    return item;
                }
            }
        }

        return null;
    }

    /**
     * validate cart items
     */
    async validateItems() {
        if (! this.cart) {
            return false;
        }

        if (! this.cart?.items?.length) {
            await this.removeCart();

            return false;
        }

        const tax = new Tax();

        let isInvalid = false;

        const inclusiveTax = await tax.isInclusiveTaxProductPrices();        

        for (let i = 0; i < this.cart.items.length; i++) {
            const item = this.cart.items[i];

            let product = await this.DB.getItem('products', item.productId);

            const typeInstance = this.getProductTypeInstance(product);

            await typeInstance.validateCartItem(item);

            const basePrice = inclusiveTax
                ? item.basePriceInclTax
                : item.basePrice;

            const baseDiscountAmount = (item.customPrice > 0)
                ? (basePrice - item.customPrice)
                : 0;

            const discountAmount = await this.outlet.convertPrice(baseDiscountAmount);

            const price = await this.outlet.convertPrice(basePrice);

            const total = await this.outlet.convertPrice(basePrice * item.quantity);

            const updatedItem = {
                ...item,
                price,
                priceInclTax: price,
                basePrice: basePrice,
                basePriceInclTax: basePrice,
                total,
                totalInclTax: total,
                baseTotal: basePrice * item.quantity,
                baseTotalInclTax: basePrice * item.quantity,
                discountAmount: discountAmount * item.quantity,
                baseDiscountAmount: baseDiscountAmount * item.quantity,
            };            

            await this.DB.updateItem('cart_items', updatedItem);

            this.cart.items[i] = updatedItem;
        }        

        await this.setCart();

        return ! isInvalid;
    }

    /**
     * Calculate tax for cart items
     */
    async calculateItemsTax() {
        if (! this.cart) {
            return;
        }

        const tax = new Tax();

        const taxCategories = {};

        const customer = await this.DB.getCartCustomer();

        const { outlet } = await this.DB.getAgent();

        for (let i = 0; i < this.cart.items.length; i++) {
            const item = this.cart.items[i];

            const product = await this.DB.getItem('products', item.productId);

            let taxCategoryId = item.taxCategoryId || product?.taxCategoryId;

            if (! taxCategoryId) {
                taxCategoryId = await tax.getConfigData('sales.taxes.categories.product');
            }

            if (! taxCategoryId) {
                continue;
            }

            if (! taxCategories[taxCategoryId]) {
                taxCategories[taxCategoryId] = await this.DB.getItem('tax_categories', 1).then(data => {
                    return data?.taxCategories?.find(category => category.id == taxCategoryId);
                });
            }

            const taxCategory = taxCategories[taxCategoryId];

            if (! taxCategory) {
                continue;
            }

            const basedOn = await tax.getConfigData('sales.taxes.calculation.based_on');

            let address = null;

            if (basedOn === 'shipping_origin') {
                address = await tax.getShippingOriginAddress();
            } else {
                address = this.getCustomerAddress('cart_billing', customer, outlet);
            }

            if (! address) {
                address = customer.addresses.find(address => address.defaultAddress) || null;
            }

            if (! address) {
                address = await tax.getDefaultAddress();
            }

            item.appliedTaxRate = null;
            item.taxPercent = 0;
            item.taxAmount = 0;
            item.baseTaxAmount = 0;

            const inclusiveTax = await tax.isInclusiveTaxProductPrices();

            await tax.isTaxApplicableInCurrentAddress(
                taxCategory,
                address,
                rate => {
                    item.appliedTaxRate = rate.identifier;
                    item.taxCategoryId = taxCategoryId;
                    item.taxPercent = rate.taxRate;

                    if (inclusiveTax) {
                        item.taxAmount = parseFloat(((item.totalInclTax * rate.taxRate) / (100 + rate.taxRate)).toFixed(4));
                        item.baseTaxAmount = parseFloat(((item.baseTotalInclTax * rate.taxRate) / (100 + rate.taxRate)).toFixed(4));

                        item.total = item.totalInclTax - item.taxAmount;
                        item.baseTotal = item.baseTotalInclTax - item.baseTaxAmount;

                        item.price = item.total / item.quantity;
                        item.basePrice = item.baseTotal / item.quantity;
                    } else {
                        item.taxAmount = parseFloat(((item.total * rate.taxRate) / 100).toFixed(4));
                        item.baseTaxAmount = parseFloat(((item.baseTotal * rate.taxRate) / 100).toFixed(4));

                        item.totalInclTax = item.total + item.taxAmount;
                        item.baseTotalInclTax = item.baseTotal + item.baseTaxAmount;

                        item.priceInclTax = item.price + item.taxAmount / item.quantity;
                        item.basePriceInclTax = item.basePrice + item.baseTaxAmount / item.quantity;
                    }
                }
            );

            if (! item.appliedTaxRate) {
                item.priceInclTax = item.price;
                item.basePriceInclTax = item.basePrice;
                item.totalInclTax = item.total;
                item.baseTotalInclTax = item.baseTotal;
            }

            await this.DB.updateItem('cart_items', item);

            this.cart.items[i] = item;
        }

        await this.setCart();
    }

    /**
     * Get customer address
     * @param {string} type
     * @param {Object} customer
     * @param {Object} outlet
     * @returns {Object}
     */
    getCustomerAddress(type, customer, outlet) {
        return {
            addressType: type,
            firstName: customer.firstName,
            lastName: customer.lastName,
            gender: customer.gender,
            companyName: customer.companyName,
            email: customer.email,
            phone: customer.phone || outlet.phone,
            address: outlet.address,
            city: outlet.city,
            state: outlet.state,
            country: outlet.country,
            postcode: outlet.postcode
        };
    }

    /**
     * Get Product Type Instance
     *
     * @param {Object} product
     * @returns {Object}
     */
    getProductTypeInstance(product) {
        switch (product.type) {
            case 'booking':
                return new Booking(product);
            case 'bundle':
                return new Bundle(product);
            case 'configurable':
                return new Configurable(product);
            case 'downloadable':
                return new Downloadable(product);
            case 'grouped':
                return new Grouped(product);
            case 'simple':
                return new Simple(product);
            case 'virtual':
                return new Virtual(product);
            default:
                return new Simple(product);
        }
    }
}