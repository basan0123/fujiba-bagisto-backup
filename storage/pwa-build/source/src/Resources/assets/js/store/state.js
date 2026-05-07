var state = {
    cart: null,
    cartUpdatedAt: 0,        // ms timestamp of last cart write
    cartOptimisticDelta: 0,  // synthetic count bump used between an
                             // add-to-cart click and the API response
    cartOptimisticIds: [],   // product_ids the user just optimistically added
    pagination: null,
    customer: false,
    isCustomerFetched: false,
    token: null,
};

setTimeout(() => {
    EventBus.$on("user-logged-in", (user) => {
        state.customer = user.data;
        state.token = user.token;
    });

    EventBus.$on("user-logged-out", () => {
        state.customer = null;
    });

    EventBus.$on("checkout.cart.changed", (cart) => {
        state.cart = cart;
        state.cartUpdatedAt = Date.now();
        // Real cart state replaces any optimistic counters.
        state.cartOptimisticDelta = 0;
        state.cartOptimisticIds = [];
    });
}, 0);

export default state;
