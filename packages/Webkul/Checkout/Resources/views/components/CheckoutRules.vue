<!-- Checkout Vue Component with Shipping + COD Rules -->
<!-- 
  Usage: Include in your checkout.blade.php or checkout layout
  
  Handles:
  - Shipping method auto-selection based on cart total
  - COD fee calculation based on cart total + payment method
  - Realtime updates on cart/payment changes
  - Frontend-backend sync
-->

<template>
  <div id="checkout-container" class="checkout-wrapper">
    
    <!-- Cart Summary -->
    <section class="checkout-section">
      <h2>注文確認</h2>
      
      <div class="cart-summary">
        <div class="summary-row">
          <span class="label">小計:</span>
          <span class="value">{{ formatCurrency(cartSubtotal) }}</span>
        </div>
        
        <!-- Shipping Display -->
        <div class="summary-row" v-if="showShippingInfo">
          <span class="label">送料:</span>
          <span class="value">{{ formatCurrency(shippingPrice) }}</span>
          <span class="method-badge">{{ shippingMethodLabel }}</span>
        </div>
        
        <!-- COD Fee Display -->
        <div class="summary-row" v-if="showCODFee">
          <span class="label">{{ codFeeLabel }}:</span>
          <span class="value">{{ formatCurrency(codFee) }}</span>
        </div>
        
        <!-- Total -->
        <div class="summary-row grand-total">
          <span class="label">合計:</span>
          <span class="value">{{ formatCurrency(grandTotal) }}</span>
        </div>
      </div>
    </section>

    <!-- Shipping Method (Hidden/Auto-Selected) -->
    <section class="checkout-section" v-if="checkoutState.shipping_methods">
      <h2>配送方法</h2>
      
      <div class="shipping-methods">
        <div 
          v-for="method in checkoutState.shipping_methods" 
          :key="method.code"
          class="method-option"
        >
          <input 
            type="radio" 
            :id="'shipping-' + method.code"
            :value="method.code"
            v-model="selectedShippingMethod"
            @change="onShippingMethodChange"
          >
          <label :for="'shipping-' + method.code">
            <span class="method-name">{{ method.label }}</span>
            <span class="method-price">{{ formatCurrency(method.price) }}</span>
          </label>
        </div>
      </div>

      <!-- Auto-selection info -->
      <div v-if="checkoutState.rules.auto_select_enabled" class="info-badge">
        ℹ️ 条件に応じて自動選択されます
      </div>
    </section>

    <!-- Payment Method -->
    <section class="checkout-section">
      <h2>支払い方法</h2>
      
      <div class="payment-methods">
        <div class="method-option">
          <input 
            type="radio" 
            id="payment-credit"
            value="credit-card"
            v-model="selectedPaymentMethod"
            @change="onPaymentMethodChange"
          >
          <label for="payment-credit">クレジットカード</label>
        </div>
        
        <div class="method-option">
          <input 
            type="radio" 
            id="payment-cod"
            value="cashondelivery"
            v-model="selectedPaymentMethod"
            @change="onPaymentMethodChange"
          >
          <label for="payment-cod">
            代金引換
            <span v-if="codFee > 0" class="fee-info">(手数料: {{ formatCurrency(codFee) }})</span>
          </label>
        </div>
      </div>
    </section>

    <!-- Place Order Button -->
    <section class="checkout-section">
      <button 
        @click="placeOrder"
        :disabled="!isOrderReady"
        class="btn-place-order"
      >
        注文確定
      </button>
    </section>
  </div>
</template>

<script>
export default {
  name: 'CheckoutComponent',
  
  data() {
    return {
      // Cart and totals
      cartSubtotal: 0,
      shippingPrice: 0,
      codFee: 0,
      grandTotal: 0,
      
      // Selections
      selectedShippingMethod: null,
      selectedPaymentMethod: 'credit-card',
      
      // State from backend
      checkoutState: {
        shipping_methods: [],
        rules: {
          auto_select_enabled: true,
          realtime_update: true,
        },
      },
      
      // Shipping method label
      shippingMethodLabel: '送料無料',
      codFeeLabel: '代金引換手数料',
      
      // Scroll hide/show tracking (mobile)
      lastScroll: 0,
      _scrollHandler: null,
    };
  },

  computed: {
    // Show shipping info only if applicable
    showShippingInfo() {
      return this.shippingPrice !== undefined && this.shippingPrice >= 0;
    },

    // Show COD fee only if COD is selected
    showCODFee() {
      return this.selectedPaymentMethod === 'cashondelivery' && this.codFee > 0;
    },

    // Check if order can be placed
    isOrderReady() {
      return this.selectedShippingMethod && this.selectedPaymentMethod;
    },
  },

  watch: {
    // Watch cart total changes
    cartSubtotal(newTotal) {
      this.updateCheckoutState();
    },

    // Watch payment method changes
    selectedPaymentMethod(newMethod) {
      this.onPaymentMethodChange();
    },
  },

  methods: {
    /**
     * Initialize checkout component
     */
    async init() {
      await this.loadCheckoutState();
      this.autoSelectShippingMethod();
      this.updateTotals();
    },

    /**
     * Load checkout state from backend
     */
    async loadCheckoutState() {
      try {
        // Get cart total
        this.cartSubtotal = await this.getCartSubtotal();

        // Call backend API to get checkout state
        const response = await axios.get('/api/checkout/state', {
          params: {
            cart_total: this.cartSubtotal,
            payment_method: this.selectedPaymentMethod,
          }
        });

        this.checkoutState = response.data;
        
        console.log('✅ Checkout state loaded:', this.checkoutState);
      } catch (error) {
        console.error('❌ Error loading checkout state:', error);
      }
    },

    /**
     * Get current cart subtotal
     */
    async getCartSubtotal() {
      try {
        const response = await axios.get('/api/cart/subtotal');
        return response.data.subtotal || 0;
      } catch (error) {
        console.error('Error getting cart subtotal:', error);
        return 0;
      }
    },

    /**
     * Auto-select the valid shipping method
     */
    autoSelectShippingMethod() {
      if (this.checkoutState.rules.auto_select_enabled && 
          this.checkoutState.auto_selected_method) {
        
        this.selectedShippingMethod = this.checkoutState.auto_selected_method.code;
        this.shippingMethodLabel = this.checkoutState.auto_selected_method.label;
        this.shippingPrice = this.checkoutState.auto_selected_method.price;
        
        console.log('✅ Shipping method auto-selected:', this.selectedShippingMethod);
      }
    },

    /**
     * Update checkout state and totals
     */
    async updateCheckoutState() {
      await this.loadCheckoutState();
      this.autoSelectShippingMethod();
      this.updateTotals();
    },

    /**
     * Handle shipping method change
     */
    onShippingMethodChange() {
      const selected = this.checkoutState.shipping_methods.find(
        m => m.code === this.selectedShippingMethod
      );

      if (selected) {
        this.shippingPrice = selected.price;
        this.shippingMethodLabel = selected.label;
        this.updateTotals();
        
        console.log('📦 Shipping method changed:', selected);
      }
    },

    /**
     * Handle payment method change
     */
    async onPaymentMethodChange() {
      console.log('💳 Payment method changed to:', this.selectedPaymentMethod);
      
      // Reload checkout state with new payment method
      await this.updateCheckoutState();
    },

    /**
     * Update grand total calculation
     */
    updateTotals() {
      this.codFee = this.selectedPaymentMethod === 'cashondelivery' 
        ? this.checkoutState.cod_fee || 0
        : 0;

      this.grandTotal = this.cartSubtotal + this.shippingPrice + this.codFee;
      
      console.log('💰 Totals updated:', {
        subtotal: this.cartSubtotal,
        shipping: this.shippingPrice,
        cod_fee: this.codFee,
        grand_total: this.grandTotal,
      });
    },

    /**
     * Format currency (JPY)
     */
    formatCurrency(amount) {
      return '¥' + Number(amount).toLocaleString('ja-JP', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      });
    },

    /**
     * Place order
     */
    async placeOrder() {
      if (!this.isOrderReady) {
        alert('配送方法と支払い方法を選択してください');
        return;
      }

      try {
        console.log('🚀 Placing order with:', {
          shipping_method: this.selectedShippingMethod,
          shipping_price: this.shippingPrice,
          payment_method: this.selectedPaymentMethod,
          cod_fee: this.codFee,
          grand_total: this.grandTotal,
        });

        const response = await axios.post('/api/checkout/place-order', {
          shipping_method: this.selectedShippingMethod,
          shipping_price: this.shippingPrice,
          payment_method: this.selectedPaymentMethod,
          cod_fee: this.codFee,
          grand_total: this.grandTotal,
        });

        if (response.data.success) {
          // Redirect to success page
          window.location.href = '/checkout/success?order=' + response.data.order_id;
        } else {
          alert('エラーが発生しました: ' + response.data.message);
        }
      } catch (error) {
        console.error('❌ Order placement failed:', error);
        alert('注文処理に失敗しました。もう一度お試しください。');
      }
    },
  },

  /**
   * Lifecycle: Initialize on component mount
   */
  mounted() {
    this.init();

    // Watch for cart updates (if using Vuex or event bus)
    if (window.$root && window.$root.$on) {
      window.$root.$on('cart-updated', () => {
        this.init();
      });
    }

    // Setup mobile scroll auto-hide for header/footer
    this.setupScrollAutoHide();
  },

  /**
   * Setup header/footer auto-hide on scroll (mobile only)
   */
  setupScrollAutoHide() {
    // Only on mobile
    if (window.innerWidth > 768) return;

    this._scrollHandler = () => {
      const currentScroll = window.pageYOffset;
      const header = document.querySelector('.header');
      const footer = document.querySelector('.footer');

      if (!header || !footer) return;

      // Scroll down: hide header/footer
      if (currentScroll > this.lastScroll && currentScroll > 50) {
        header.classList.add('hide-header');
        footer.classList.add('hide-footer');
      } 
      // Scroll up: show header/footer
      else if (currentScroll < this.lastScroll) {
        header.classList.remove('hide-header');
        footer.classList.remove('hide-footer');
      }

      this.lastScroll = currentScroll <= 0 ? 0 : currentScroll;
    };

    window.addEventListener('scroll', this._scrollHandler, { passive: true });
  },

  /**
   * Cleanup
   */
  beforeUnmount() {
    if (window.$root && window.$root.$off) {
      window.$root.$off('cart-updated');
    }

    // Remove scroll listener
    if (this._scrollHandler) {
      window.removeEventListener('scroll', this._scrollHandler);
    }
  },
};
</script>

<style scoped>
.checkout-wrapper {
  max-width: 600px;
  margin: 20px auto;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.checkout-section {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.checkout-section h2 {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin: 0 0 15px 0;
}

/* Cart Summary */
.cart-summary {
  background: #f9f9f9;
  padding: 15px;
  border-radius: 6px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  font-size: 14px;
  border-bottom: 1px solid #eee;
}

.summary-row:last-child {
  border-bottom: none;
}

.summary-row.grand-total {
  background: #f0f0f0;
  padding: 10px;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 600;
}

.summary-row .label {
  color: #666;
  font-weight: 500;
}

.summary-row .value {
  font-weight: 600;
  color: #2196F3;
}

.method-badge {
  display: inline-block;
  background: #e3f2fd;
  color: #1976d2;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  margin-left: 10px;
}

.fee-info {
  display: block;
  font-size: 12px;
  color: #ff6f00;
  margin-top: 4px;
}

/* Shipping Methods */
.shipping-methods {
  margin-bottom: 15px;
}

.method-option {
  display: flex;
  align-items: center;
  padding: 12px;
  border: 2px solid #eee;
  border-radius: 6px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: all 0.3s;
}

.method-option:hover {
  border-color: #2196F3;
  background: #f8fbff;
}

.method-option input[type="radio"],
.method-option input[type="checkbox"] {
  margin-right: 10px;
  cursor: pointer;
}

.method-option label {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  margin: 0;
}

.method-name {
  font-weight: 500;
  color: #333;
}

.method-price {
  font-weight: 600;
  color: #2196F3;
}

/* Info Badge */
.info-badge {
  background: #e3f2fd;
  border-left: 4px solid #2196F3;
  padding: 10px;
  border-radius: 4px;
  font-size: 13px;
  color: #1565c0;
}

/* Button */
.btn-place-order {
  width: 100%;
  padding: 14px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-place-order:hover:not(:disabled) {
  background: #45a049;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-place-order:disabled {
  background: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}

/* Payment Methods */
.payment-methods {
  margin-bottom: 15px;
}

/* Header/Footer Auto-Hide on Scroll (Mobile) */
.header,
.footer {
  position: fixed !important;
  left: 0;
  width: 100%;
  z-index: 999;
  transition: transform 0.3s ease-out;
}

.header {
  top: 0;
}

.footer {
  bottom: 0 !important;
}

.hide-header {
  transform: translateY(-100%);
}

.hide-footer {
  transform: translateY(100%);
}

/* Responsive */
@media (max-width: 768px) {
  /* Account for fixed header/footer */
  body {
    padding-top: 60px;    /* header height */
    padding-bottom: 70px; /* footer height */
  }

  .checkout-wrapper {
    margin: 10px;
  }

  .checkout-section {
    padding: 15px;
  }

  .summary-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .summary-row .value {
    margin-top: 5px;
  }
}
</style>
