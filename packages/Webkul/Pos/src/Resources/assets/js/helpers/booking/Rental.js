import Booking from '@src/helpers/Booking';

/**
 * Rental class that extends the Booking class.
 * This class is used to handle Rental booking specific logic and functionality.
 */
export default class Rental extends Booking {
    /**
     * Add booking additional prices to cart item.
     * @param {Array} products
     * @returns {Array}
     */
    async addAdditionalPrices(products) {
        let bookingProduct = await this.DB.getItem('products', products[0].productId);

        bookingProduct = bookingProduct.booking.find(b => b.productId == products[0].productId);

        const rentingType =
            products[0]?.additional?.booking?.rentingType ??
            bookingProduct.rentalSlot.rentingType;

        let price = 0;

        if (rentingType === 'daily') {
            const from = new Date(`${products[0].additional.booking.dateFrom}T00:00:00`);

            const to = new Date(`${products[0].additional.booking.dateTo}T24:00:00`);

            const diffInDays = Math.ceil((to - from) / (1000 * 60 * 60 * 24));

            price += bookingProduct.rentalSlot.dailyPrice * diffInDays;
        } else {
            const from = new Date(products[0].additional.booking.slot.from * 1000);

            const to = new Date(products[0].additional.booking.slot.to * 1000);

            const diffInHours = Math.ceil((to - from) / (1000 * 60 * 60));

            price += bookingProduct.rentalSlot.hourlyPrice * diffInHours;
        }

        const convertedPrice = await this.outlet.convertPrice(price);

        products[0].product.price += convertedPrice;
        
        products[0].product.basePrice += price;

        const quantity = products[0].quantity;

        products[0].price += convertedPrice;
        products[0].basePrice += convertedPrice;
        products[0].total += convertedPrice * quantity;
        products[0].baseTotal += convertedPrice * quantity;

        return products;
    }

    /**
	 * Validate cart item product price and other things.
	 */
	async validateCartItem(item) {
		let product = await this.DB.getItem('products', item.productId);

        let basePrice = product.price;

        const bookingProduct = product.booking.find(b => b.productId == product.id);

        const booking = item.additional?.booking || [];

        const rentingType =
            booking?.rentingType ??
            bookingProduct.rentalSlot.rentingType;

        if (rentingType === 'daily') {
            const from = new Date(`${booking.dateFrom}T00:00:00`);

            const to = new Date(`${booking.dateTo}T24:00:00`);

            const diffInDays = Math.ceil((to - from) / (1000 * 60 * 60 * 24));

            basePrice += bookingProduct.rentalSlot.dailyPrice * diffInDays;
        } else {
            const from = new Date(booking.slot.from * 1000);

            const to = new Date(booking.slot.to * 1000);

            const diffInHours = Math.ceil((to - from) / (1000 * 60 * 60));

            basePrice += bookingProduct.rentalSlot.hourlyPrice * diffInHours;
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
}