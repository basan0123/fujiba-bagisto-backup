import Booking from '@src/helpers/Booking';

/**
 * Default booking class.
 * This class extends the Booking class and provides methods to handle booking slots.
 */
export default class Default extends Booking {
    /**
     * Get slots by date.
     * @param {Object} bookingProduct 
     * @param {Object} bookingProductSlot
     * @param {string} date (format: YYYY-MM-DD)
     * @returns {Array}
     */
    getSlotsByDate(bookingProduct, bookingProductSlot, date) {
        const requestedDate = new Date(`${date}T00:00:00`);

        const availableFrom = (! bookingProduct.availableEveryWeek && bookingProduct.availableFrom)
            ? new Date(bookingProduct.availableFrom)
            : this.startOfToday();

        const availableTo = (! bookingProduct.availableEveryWeek && bookingProduct.availableFrom)
            ? new Date(bookingProduct.availableTo)
            : new Date('2080-01-01T00:00:00');

        if (
            requestedDate < availableFrom
            || requestedDate > availableTo
        ) {
            return [];
        }

        return bookingProductSlot.bookingType === 'one'
            ? this.getOneBookingForManyDaysSlots(bookingProductSlot, requestedDate)
            : this.getManyBookingsForOneDaySlots(bookingProductSlot, bookingProduct, requestedDate);
    }

    /**
     * Get slots for one booking for many days.
     * @param {Object} bookingProductSlot 
     * @param {Date} requestedDate 
     * @returns {Array}
     */
    getOneBookingForManyDaysSlots(bookingProductSlot, requestedDate) {
        const slots = [];

        for (const timeDuration of bookingProductSlot.slotManyDays) {
            if (requestedDate.getDay() !== timeDuration.fromDay) {
                continue;
            }

            const startDate = this.setTimeForDay(requestedDate, timeDuration.fromDay, timeDuration.from);
            const endDate = this.setTimeForDay(requestedDate, timeDuration.toDay, timeDuration.to);

            slots.push({
                from: this.formatTime(startDate),
                to: this.formatTime(endDate),
                timestamp: `${Math.floor(startDate.getTime() / 1000)}-${Math.floor(endDate.getTime() / 1000)}`
            });
        }

        return slots;
    }

    /**
     * Get slots for many bookings for one day.
     * @param {Object} bookingProductSlot 
     * @param {Object} bookingProduct
     * @param {Date} requestedDate 
     * @returns {Array}
     */
    getManyBookingsForOneDaySlots(bookingProductSlot, bookingProduct, requestedDate) {
        return this.slotsCalculation(bookingProduct, requestedDate, bookingProductSlot);
    }

    /**
     * Set time for a specific day of the week.
     * @param {Date} baseDate
     * @param {number} dayOfWeek (0-6, where 0 is Sunday and 6 is Saturday)
     * @param {string} time (format: HH:mm)
     */
    setTimeForDay(baseDate, dayOfWeek, time) {
        const [hour, minute] = time.split(':').map(Number);
        const targetDate = new Date(baseDate);
        const currentDay = baseDate.getDay();
        const diff = dayOfWeek - currentDay;

        targetDate.setDate(baseDate.getDate() + diff);
        targetDate.setHours(hour, minute, 0, 0);

        return targetDate;
    }

    /**
     * Get start of today.
     * @returns {Date}
     */
    startOfToday() {
        const now = new Date();

        return new Date(now.getFullYear(), now.getMonth(), now.getDate());
    }
}