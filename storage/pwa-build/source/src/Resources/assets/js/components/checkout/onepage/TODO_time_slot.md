# TODO: Delivery time-slot validation UI

The Sagawa delivery time-slot picker exists as a Blade partial:
  packages/Fujiba/SagawaDelivery/src/Resources/views/partials/slot-picker.blade.php

But the PWA never renders Blade. Need to:

1. Build a Vue `<delivery-slot-picker>` component that:
   - GET /api/v1/sagawa/slots?postcode=NNNNNNN&date=YYYY-MM-DD → array of {time, label, available}
   - Disables unavailable slots with grey overlay
   - Emits `@select` with the chosen slot
   - Shows "No slots available" message when empty (this is the validation requirement)

2. Wire it into the checkout flow between shipping-method selection and place-order.

3. Save the selected slot on the cart (new column or existing `extras` JSON).

Endpoint already exists conceptually — Sagawa client has `checkAvailableSlots()` in
SagawaClient.php; just needs an API wrapper at /api/v1/sagawa/slots.
