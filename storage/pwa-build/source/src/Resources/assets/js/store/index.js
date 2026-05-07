import Vue from 'vue';
import Vuex from "vuex";
import state from './state';
import actions from './actions';
import mutations from './mutations';

Vue.use(Vuex);

// Set-based product-id lookup so a 3,000-product grid doesn't re-render every
// card on a single cart mutation. ProductCard reads `inCart` as a prop instead
// of subscribing to Vuex directly.
const getters = {
    cartProductIds (state) {
        var ids = new Set();
        if (state.cart && Array.isArray(state.cart.items)) {
            state.cart.items.forEach(function (it) {
                if (it && it.product_id != null) ids.add(it.product_id);
            });
        }
        // Include products that have been optimistically added but whose
        // server response hasn't reconciled into cart.items yet — so the
        // ✓ check on the product card flips immediately on tap.
        if (Array.isArray(state.cartOptimisticIds)) {
            state.cartOptimisticIds.forEach(function (id) { ids.add(id); });
        }
        return ids;
    },
    /**
     * Map product_id → quantity already in the cart. Used by storefront
     * surfaces to cap the qty stepper at remaining stock.
     */
    cartQtyByProduct (state) {
        var map = {};
        if (state.cart && Array.isArray(state.cart.items)) {
            state.cart.items.forEach(function (it) {
                if (! it || it.product_id == null) return;
                map[it.product_id] = (map[it.product_id] || 0) + (parseInt(it.quantity, 10) || 0);
            });
        }
        // Optimistic adds count as +1 each (we add 1 per click).
        if (Array.isArray(state.cartOptimisticIds)) {
            state.cartOptimisticIds.forEach(function (id) {
                map[id] = (map[id] || 0) + 1;
            });
        }
        return map;
    },
};

const store = new Vuex.Store({
    state,
    actions,
    mutations,
    getters,
});

export default store;