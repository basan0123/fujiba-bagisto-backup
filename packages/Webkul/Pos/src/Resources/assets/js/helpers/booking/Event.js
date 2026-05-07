import Booking from '@src/helpers/Booking';

/**
 * Event booking class for handling event-related operations
 */
export default class Event extends Booking {
    /**
     * Constructor of the class
     */
    constructor(bookingProduct) {
        super();

        this.bookingProduct = bookingProduct;
    }

    /**
     * Returns event date string
     * @returns {string}
     */
    getEventDate() {
        const options = {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        };

        const from = new Date(this.bookingProduct.availableFrom);
        const to = new Date(this.bookingProduct.availableTo);

        const formatter = new Intl.DateTimeFormat('en-US', options);

        return `${formatter.format(from)} - ${formatter.format(to)}`;
    }

    /**
     * Returns tickets with formatted price
     * @returns {Array}
     */
    async getTickets() {
        if (
            !this.bookingProduct.eventTickets
            || this.bookingProduct.eventTickets.length === 0
        ) {
            return [];
        }

        return this.getFormattedTickets(this.bookingProduct.eventTickets);
    }

    /**
     * Format ticket price
     * @param {Array} tickets
     * @returns {Array}
     */
    async getFormattedTickets(tickets) {
        const formattedTickets = await Promise.all(
            tickets.map(async (ticket) => {
                let price = ticket.price;
                const isOnSale = this.isInSale(ticket);

                const result = {
                    id: ticket.id,
                };

                if (isOnSale) {
                    price = ticket.specialPrice;
                    result.originalConvertedPrice = await this.outlet.convertPrice(ticket.price);
                    result.originalFormattedPrice = await this.outlet.currency(ticket.price);
                }

                result.convertedPrice = await this.outlet.convertPrice(price);
                result.formattedPrice = await this.outlet.currency(price);

                return result;
            })
        );

        return formattedTickets;
    }

    /**
     * Add booking additional prices to cart item.
     *
     * @param {Array} products
     * @returns {Array}
     */
    async addAdditionalPrices(products) {
        for (let i = 0; i < products.length; i++) {
            const product = products[i];

            let bookingProduct = await this.DB.getItem('products', product.productId);

            bookingProduct = bookingProduct.booking.find(b => b.productId == product.productId);

            const ticket = bookingProduct.eventTickets.find(
                ticket => ticket.id == product.additional.booking.ticketId
            );

            let price = ticket.price;

            if (this.isInSale(ticket)) {
                price = ticket.specialPrice;
            }

            const convertedPrice = await this.outlet.convertPrice(price);

            products[i].product.price += convertedPrice;
            products[i].product.basePrice += price;

            products[i].price += convertedPrice;
            products[i].basePrice += price;

            products[i].total += convertedPrice * products[i].quantity;
            products[i].baseTotal += price * products[i].quantity;
        }

        return products;
    }

    /**
	 * Validate cart item product price and other things.
	 */
	async validateCartItem(item) {
		let product = await this.DB.getItem('products', item.productId);

        let basePrice = product.price;

        const bookingProduct = product.booking.find(b => b.productId == product.id);

        const ticket = bookingProduct.eventTickets.find(
            ticket => ticket.id == item.additional.booking.ticketId
        );

        if (this.isInSale(ticket)) {
            basePrice += ticket.specialPrice;
        } else {
            basePrice += ticket.price;
        }

		if (basePrice === item.basePrice) {
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
     * Determines whether a ticket is in sale
     * @param {Object} ticket
     * @returns {boolean}
     */
    isInSale(ticket) {
        const now = new Date();

        const fromValid = ! ticket.specialPriceFrom
            || ticket.specialPriceFrom === '0000-00-00 00:00:00'
            || new Date(ticket.specialPriceFrom) <= now;

        const toValid = ! ticket.specialPriceTo
            || ticket.specialPriceTo === '0000-00-00 00:00:00'
            || new Date(ticket.specialPriceTo) > now;

        return ticket.specialPrice !== null
            && ticket.specialPrice > 0
            && fromValid
            && toValid;
    }
}