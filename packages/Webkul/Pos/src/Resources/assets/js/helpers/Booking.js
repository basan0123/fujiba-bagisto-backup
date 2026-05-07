import { useIndexedDB } from '@src/composable/indexed-db';
import { useCookies } from '@src/composable/cookies';
import { useOutlet } from '@src/composable/outlet';
import I18n from '@src/plugins/i18n';

/**
 * This class is responsible for handling booking slots and their calculations.
 * It provides methods to get default slot details, format time, and calculate slots based on booking product and requested date.
 */
export default class Booking {
    /**
     * Constructor of the class
     */
    constructor() {
        this.daysOfWeek = [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
        ];

        this.DB = useIndexedDB();

        this.cookies = useCookies();

        this.outlet = useOutlet();

        this.t = I18n.global.t;
    }

    /**
     * Get the available slots for a booking product.
     * @param {Object} bookingProduct - The booking product object.
     * @param {Object} bookingProductSlot - The booking product slot object.
     * @returns {Array} - An array of available slots.
     */
    getWeekSlotDurations(bookingProduct, bookingProductSlot) {
        const slotsByDays = [];

        const availableDays = this.getAvailableWeekDays(bookingProduct);

        this.daysOfWeek.forEach((dayName, index) => {
            let slots = [];

            if (bookingProductSlot.sameSlotAllDays) {
                slots = bookingProductSlot.slotManyDays || [];
            } else {
                slots = bookingProductSlot.slotOneDay?.[index] || [];
            }

            slotsByDays.push({
                name: dayName,
                slots: availableDays[index] ? this.convert24To12Hours(slots) : [],
            });
        });

        return slotsByDays;
    }

    /**
     * 
     * @param {object} bookingProduct 
     * @returns {Array}
     */
    getAvailableWeekDays(bookingProduct) {
        if (bookingProduct.availableEveryWeek ?? true) {
            return this.daysOfWeek;
        }

        const now = new Date();

        const availableFrom = bookingProduct.availableFrom
            ? new Date(bookingProduct.availableFrom)
            : new Date(now.setHours(0, 0, 0, 0));

        const availableTo = bookingProduct.availableTo
            ? new Date(bookingProduct.availableTo)
            : new Date('2080-01-01T00:00:00');

        const availableDays = [];

        for (let i = 0; i <= 6; i++) {
            const date = new Date();

            date.setDate(now.getDate() + i);

            if (
                date >= availableFrom
                && date <= availableTo
            ) {
                const dayName = date.toLocaleString('en-US', { weekday: 'long' });
                availableDays.push(dayName);
            }
        }

        return this.sortDaysOfWeek(availableDays);
    }

    /**
     * 
     * @param {array} days 
     * @returns {Array}
     */
    sortDaysOfWeek(days) {
        const filtered = days.filter(day => this.daysOfWeek.includes(day));

        return filtered.sort((a, b) => {
            return this.daysOfWeek.indexOf(a) - this.daysOfWeek.indexOf(b);
        });
    }

    /**
     * 
     * @param {array} slots 
     * @returns {array}
     */
    convert24To12Hours(slots) {
        return slots.map(slot => ({
            from: this.formatTo12Hour(slot.from),
            to: this.formatTo12Hour(slot.to),
        }));
    }

    /**
     * Convert a 24-hour time string to a 12-hour format.
     * @param {string} time24 - The time string in the format "HH:MM".
     * @returns {string} - The time string in 12-hour format.
     */
    formatTo12Hour(time24) {
        const [hours, minutes] = time24.split(':').map(Number);
        const date = new Date();
        date.setHours(hours, minutes);

        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        });
    }

    /**
     * Get the HTML representation of today's slots.
     * @param {Object} bookingProduct - The booking product object.
     * @param {Object} bookingProductSlot - The booking product slot object.
     * @returns {string} - The HTML representation of today's slots.
     */
    getTodaySlotsHtml(bookingProduct, bookingProductSlot) {
        const slots = [];

        const weekSlots = this.getWeekSlotDurations(bookingProduct, bookingProductSlot);

        const todayIndex = new Date().getDay();
        const todaySlots = weekSlots[todayIndex]?.slots || [];

        for (const slot of todaySlots) {
            slots.push(`${slot.from} - ${slot.to}`);
        }

        return slots.length
            ? slots.join(' | ')
            : `<span class="label-closed icon-cross">${this.t('pos.common.cart.closed')}</span>`;
    }

    /**
     * Get default slot details based on booking product slot and requested date.
     * @param {Object} bookingProductSlot - The booking product slot object.
     * @param {Date} requestedDate - The requested date for the booking.
     * @returns {Array} - An array containing availableFrom, availableTo, and timeDurations.
     */
    getDefaultSlotDetails(bookingProductSlot, requestedDate) {
        const availableFrom = bookingProductSlot.availableFrom
            ? new Date(bookingProductSlot.availableFrom)
            : this.startOfDay(new Date());

        const availableTo = bookingProductSlot.availableTo
            ? new Date(bookingProductSlot.availableTo)
            : new Date('2080-01-01T00:00:00');

        const dayOfWeek = new Date(requestedDate).getDay();

        const timeDurations = bookingProductSlot.sameSlotAllDays
            ? bookingProductSlot.slotManyDays
            : bookingProductSlot.slotOneDay?.[dayOfWeek] || [];

        return [availableFrom, availableTo, timeDurations];
    }

    /**
     * Get slot details based on booking product, booking product slot, and requested date.
     * @param {Object} bookingProduct - The booking product object.
     * @param {Object} bookingProductSlot - The booking product slot object.
     * @param {Date} requestedDate - The requested date for the booking.
     * @returns {Array} - An array containing availableFrom, availableTo, and timeDurations.
     */
    getSlotDetails(bookingProduct, bookingProductSlot, requestedDate) {
        if (bookingProduct.type === 'default') {
            return this.getDefaultSlotDetails(bookingProductSlot, requestedDate);
        }

        const availableFrom = ! bookingProduct.availableEveryWeek && bookingProduct.availableFrom
            ? new Date(bookingProduct.availableFrom)
            : this.startOfDay(new Date());

        const availableTo = ! bookingProduct.availableEveryWeek && bookingProduct.availableTo
            ? new Date(bookingProduct.availableTo)
            : new Date('2080-01-01T00:00:00');

        const dayOfWeek = new Date(requestedDate).getDay();

        const timeDurations = bookingProductSlot.sameSlotAllDays
            ? bookingProductSlot.slotManyDays
            : bookingProductSlot.slotOneDay?.[dayOfWeek] || [];

        return [availableFrom, availableTo, timeDurations];
    }

    /**
     * Set the time for a specific day of the week.
     * @param {Date} baseDate - The base date to set the time for.
     * @param {number} dayOfWeek - The day of the week (0-6, where 0 is Sunday and 6 is Saturday).
     * @param {string} time - The time string in the format "HH:MM".
     * @returns {Date} - A Date object with the specified time set for the given day of the week.
     */
    startOfDay(date) {
        const d = new Date(date);
        d.setHours(0, 0, 0, 0);

        return d;
    }

    /**
     * Format a Date object to a string in the format "HH:MM AM/PM".
     * @param {Date} date - The Date object to format.
     * @returns {string} - The formatted time string.
     */
    formatTime(date) {
        return new Intl.DateTimeFormat('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }).format(date);
    }

    /**
     * Format timestamp or date string into "DD Month, YYYY h:mm A"
     */
    formatDate(input, withTime = true) {
        const date =
            typeof input === 'number'
                ? new Date(input * 1000)
                : new Date(input);

        const options = {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        };

        if (withTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
            options.hour12 = true;
        }

        return new Intl.DateTimeFormat('en-GB', options).format(date);
    }

    /**
     * Calculate available slots based on booking product, requested date, and booking product slot.
     * @param {Object} bookingProduct - The booking product object.
     * @param {Date} requestedDate - The requested date for the booking.
     * @param {Object} bookingProductSlot - The booking product slot object.
     * @returns {Array} - An array of available slots.
     */
    slotsCalculation(bookingProduct, requestedDate, bookingProductSlot) {
        let availableFrom, availableTo, timeDurations;

        const requestDateStart = new Date(new Date(requestedDate).setHours(0, 0, 0, 0));

        if (bookingProduct.type === 'default') {
            [availableFrom, availableTo, timeDurations] = this.getDefaultSlotDetails(
                bookingProductSlot,
                requestDateStart
            );

            if (
                ! timeDurations.length
                || ! timeDurations[0].status
            ) {
                return [];
            }
        } else {
            [availableFrom, availableTo, timeDurations] = this.getSlotDetails(
                bookingProduct,
                bookingProductSlot,
                requestDateStart
            );

            if (
                requestDateStart < availableFrom
                || requestDateStart > availableTo
            ) {
                return [];
            }
        }

        const slots = [];

        for (let [index, timeDuration] of timeDurations.entries()) {
            const [fromHour, fromMinute] = timeDuration.from.split(':').map(Number);
            const [toHour, toMinute] = timeDuration.to.split(':').map(Number);

            const startDayTime = new Date(requestDateStart);
            startDayTime.setMinutes(fromHour * 60 + fromMinute);

            const endDayTime = new Date(requestDateStart);
            endDayTime.setMinutes(toHour * 60 + toMinute);

            let tempStartDayTime = new Date(startDayTime);
            let isFirstIteration = true;

            while (true) {
                let from = new Date(tempStartDayTime);

                if (bookingProduct.type === 'rental') {
                    tempStartDayTime.setMinutes(tempStartDayTime.getMinutes() + 60);
                } else {
                    tempStartDayTime.setMinutes(tempStartDayTime.getMinutes() + bookingProductSlot.duration);

                    if (! isFirstIteration) {
                        from.setMinutes(from.getMinutes() + bookingProductSlot.breakTime);
                        tempStartDayTime.setMinutes(tempStartDayTime.getMinutes() + bookingProductSlot.breakTime);
                    }

                    isFirstIteration = false;
                }

                const to = new Date(tempStartDayTime);

                const now = new Date();

                if (
                    startDayTime <= from
                    && from <= availableTo
                    && availableTo >= to
                    && to >= startDayTime
                    && from <= endDayTime
                    && endDayTime >= to
                ) {
                    const qty = timeDuration.qty ?? 1;

                    if (now <= from) {
                        if (bookingProduct.type === 'rental') {
                            if (! slots[index]) {
                                slots[index] = {
                                    time: `${this.formatTime(startDayTime)} - ${this.formatTime(endDayTime)}`,
                                    slots: [],
                                };
                            }

                            slots[index].slots.push({
                                from: this.formatTime(from),
                                to: this.formatTime(to),
                                fromTimestamp: Math.floor(from.getTime() / 1000),
                                toTimestamp: Math.floor(to.getTime() / 1000),
                                qty: qty,
                            });
                        } else {
                            slots.push({
                                from: this.formatTime(from),
                                to: this.formatTime(to),
                                timestamp: `${Math.floor(from.getTime() / 1000)}-${Math.floor(to.getTime() / 1000)}`,
                                qty: qty,
                            });
                        }
                    }
                } else {
                    break;
                }
            }
        }

        return slots;
    }

    /**
     * Returns additional cart item information.
     * @param {Array} data
     * @param {Object} product
     * @returns {Array}
     */
    getCartItemOptions(data, product) {
        const bookingProduct = product.booking.find(booking => {
            return booking.productId == data.productId;
        });

        data.attributes = this.getBookingAttributes(bookingProduct, data);

        return data;
    }

    /**
     * Get booking attributes based on booking type (async version).
     */
    getBookingAttributes(bookingProduct, data) {
        switch (bookingProduct.type) {
            case 'event':
                return this.getEventAttributes(bookingProduct, data);

            case 'rental':
                return this.getRentalAttributes(bookingProduct, data);

            case 'table':
                return this.getTableAttributes(data);

            default:
                return this.getDefaultAttributes(data);
        }
    }

    /**
     * Get event booking attributes.
     */
    getEventAttributes(bookingProduct, data) {
        const ticket = bookingProduct.eventTickets.find(
            (t) => t.id == data.booking.ticketId
        );

        const locale = this.cookies.get('locale')?.code || 'en';

        const label = ticket.translations.find(t => t.locale == locale)?.name || '';

        return [
            {
                attributeName: this.t('pos.common.cart.event_ticket'),
                optionId: 0,
                optionLabel: label,
            },
            {
                attributeName: this.t('pos.common.cart.event_from'),
                optionId: 0,
                optionLabel: this.formatDate(bookingProduct.availableFrom, false),
            },
            {
                attributeName: this.t('pos.common.cart.event_till'),
                optionId: 0,
                optionLabel: this.formatDate(bookingProduct.availableTo, false),
            },
        ];
    }

    /**
     * Get rental booking attributes.
     */
    getRentalAttributes(bookingProduct, data) {
        const rentingType =
            data.booking?.rentingType || bookingProduct.rentalSlot?.rentingType;

        let from, to;

        if (rentingType === 'daily') {
            from = this.formatDate(`${data.booking.dateFrom} 00:00:01`, false);
            to = this.formatDate(`${data.booking.dateTo} 23:59:59`, false);
        } else {
            from = this.formatDate(data.booking.slot.from, true);
            to = this.formatDate(data.booking.slot.to, true);
        }

        return [
            {
                attributeName: this.t('pos.common.cart.rent_type'),
                optionId: 0,
                optionLabel: rentingType,
            },
            {
                attributeName: this.t('pos.common.cart.rent_from'),
                optionId: 0,
                optionLabel: from,
            },
            {
                attributeName: this.t('pos.common.cart.rent_till'),
                optionId: 0,
                optionLabel: to,
            },
        ];
    }

    /**
     * Get table booking attributes.
     */
    getTableAttributes(data) {
        const { from, to } = data.booking.slot;

        const attributes = [
            {
                attributeName: this.t('pos.common.cart.booking_from'),
                optionId: 0,
                optionLabel: this.formatDate(from, true),
            },
            {
                attributeName: this.t('pos.common.cart.booking_till'),
                optionId: 0,
                optionLabel: this.formatDate(to, true),
            },
        ];

        if (
            data.booking.note
            && data.booking.note.trim() !== ''
        ) {
            attributes.push({
                attributeName: 'Special Note',
                optionId: 0,
                optionLabel: data.booking.note,
            });
        }

        return attributes;
    }

    /**
     * Get default booking attributes.
     */
    getDefaultAttributes(data) {
        const { from, to } = data.booking.slot;

        return [
            {
                attributeName: this.t('pos.common.cart.booking_from'),
                optionId: 0,
                optionLabel: this.formatDate(from, true),
            },
            {
                attributeName: this.t('pos.common.cart.booking_till'),
                optionId: 0,
                optionLabel: this.formatDate(to, true),
            },
        ];
    }

    /**
     * Add booking additional prices to cart item.
     * 
     * @param {Array} products
     * @returns {Array}
     */
    async addAdditionalPrices(products) {
        return products;
    }

    /**
     * Validate cart item product price.
     */
    async validateCartItem(item) {
        return await this.updateCartItemPrice(item);
    }

    /**
     * Update the cart item price.
     */
    async updateCartItemPrice(item) {
        let product = await this.DB.getItem('products', item.productId);

        const basePrice = product.price;

        if (basePrice != item.basePrice) {
            item.basePrice = basePrice;
            item.price = await this.outlet.convertPrice(basePrice);

            item.baseTotal = basePrice * item.quantity;
            item.total = await this.outlet.convertPrice(basePrice * item.quantity);

            await this.DB.updateItem('cart_items', item);
        }

        return;
    }
}