const getCustomer = ({ commit }) => {
    commit("GET_CUSTOMER");
};

const getCart = ({ commit }) => {
    commit("GET_CART");
};

// Fetch cart only if the cached copy is older than `maxAgeMs` (default 5 s).
// Lets routes that just navigated from cart-update flows reuse Vuex state
// instead of paying for another network round-trip on mount.
const getCartIfStale = ({ commit, state }, maxAgeMs = 5000) => {
    var age = Date.now() - (state.cartUpdatedAt || 0);
    if (state.cart && age < maxAgeMs) return;
    commit("GET_CART");
};

export default {
    getCustomer,
    getCart,
    getCartIfStale,
};
