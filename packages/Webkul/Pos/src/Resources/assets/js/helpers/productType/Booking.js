import AbstractType from '@src/helpers/productType/AbstractType';
import BookingHelper from '@src/helpers/Booking';
import AppointmentBooking from '@src/helpers/booking/Appointment';
import DefaultBooking from '@src/helpers/booking/Default';
import Event from '@src/helpers/booking/Event';
import Rental from '@src/helpers/booking/Rental';
import Table from '@src/helpers/booking/Table';

/**
 * Booking Product Type
 */
export default class Booking extends AbstractType {
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
        if (! data.booking) {
            return this.t('pos.common.cart.missing_options');
        }

        let products = [];

        const bookingProduct = this.product.booking.find(
            booking => booking.productId == data.productId
        );

        if (bookingProduct.type === 'rental') {
            if (data.booking.slot?.from) {
                const time = data.booking.slot.to - data.booking.slot.from;
                const hours = Math.floor(time / 60) / 60;

                if (hours > 1) {
                    return this.t('pos.common.cart.select_hourly_duration');
                }
            }

            products = await super.prepareForCart(data);
        } else if (bookingProduct.type === 'event') {
            const now = new Date();

            if (
                now > new Date(bookingProduct.availableFrom)
                && now > new Date(bookingProduct.availableTo)
            ) {
                return this.t('pos.common.cart.event_expired');
            }

            const quantities = data.booking.qty;

            const filtered = quantities.filter(item => item.quantity !== 0);

            if (! filtered.length) {
                return this.t('pos.common.cart.missing_options');
            }

            let cartProductsList = [];

            for (const { ticketId, quantity } of quantities) {
                if (! quantity) {
                    continue;
                }

                data.quantity = quantity;

                data.booking.ticketId = ticketId;

                data.booking.slot = `${new Date(bookingProduct.availableFrom).getTime()}-${new Date(bookingProduct.availableTo).getTime()}`;

                const cartProducts = await super.prepareForCart({ ...data });

                if (typeof cartProducts === 'string') {
                    return cartProducts;
                }

                cartProductsList.push(cartProducts);
            }

            products = cartProductsList.flat();
        } else {
            products = await super.prepareForCart(data);
        }

        const typeHelper = this.getTypeHelper(bookingProduct);

        products = typeHelper.addAdditionalPrices(products);

        return products;
    }

    /**
	 * Validate cart item product price and other things.
	 */
	async validateCartItem(item) {
        const bookingProduct = this.product.booking.find(
            booking => booking.productId == item.productId
        );

		const typeHelper = this.getTypeHelper(bookingProduct);

        return await typeHelper.validateCartItem(item);
	}

    /**
     * @param {Object} options1
     * @param {Object} options2
     * @returns {boolean}
     */
    compareOptions(options1, options2) {
        if (this.product.id !== parseInt(options2.productId)) {
            return false;
        }

        if (
            options1.booking
            && options2.booking
            && options1.booking.ticketId
            && options2.booking.ticketId
            && options1.booking.ticketId === options2.booking.ticketId
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns additional information for items.
     */
    async getAdditionalOptions(data) {
        const bookingHelper = new BookingHelper();

        return bookingHelper.getCartItemOptions(data, this.product);
    }

    /**
     * Get booking type helper
     *
     * @param {Object} bookingProduct
     * @returns {AbstractBooking}
     */
    getTypeHelper(bookingProduct) {
        switch (bookingProduct.type) {
            case 'appointment':
                return new AppointmentBooking();
            case 'default':
                return new DefaultBooking();
            case 'event':
                return new Event(bookingProduct);
            case 'rental':
                return new Rental();
            case 'table':
                return new Table();
            default:
                return new BookingHelper();
        }
    }
}