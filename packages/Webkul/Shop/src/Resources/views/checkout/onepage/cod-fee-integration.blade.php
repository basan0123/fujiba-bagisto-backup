{{--
  PAYMENT METHOD INTEGRATION WITH COD FEE
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  This component handles:
  1. Payment method selection
  2. Dynamic COD fee calculation based on (Subtotal + Shipping)
  3. Real-time total updates
  4. Integration with shipping auto-selection

  Events:
  - shipping-updated: Fired when shipping method changes
  - payment-changed: Fired when payment method changes
--}}

@once
@push('scripts')
<script type="module">
    /**
     * Handle COD Fee Integration with Shipping
     * 
     * When shipping changes, recalculate COD fee if payment method is COD
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Listen for shipping updates
        window.addEventListener('shipping-updated', function(event) {
            console.log('[COD Fee Integration] Shipping updated:', event.detail);
            
            const paymentMethod = document.querySelector('input[name="payment"]:checked');
            if (paymentMethod && paymentMethod.value === 'cashondelivery') {
                updateCODFeeDisplay(event.detail);
            }
        });

        // Listen for payment method changes
        const paymentRadios = document.querySelectorAll('input[name="payment"]');
        paymentRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('[COD Fee Integration] Payment method changed:', this.value);
                
                if (this.value === 'cashondelivery') {
                    // Get current cart totals
                    const cartTotal = getCartTotal();
                    const shippingPrice = getShippingPrice();
                    
                    updateCODFeeDisplay({
                        cart_total: cartTotal,
                        shipping_price: shippingPrice,
                    });
                } else {
                    hideCODFeeDisplay();
                }
            });
        });
    });

    /**
     * Get current cart subtotal from DOM
     */
    function getCartTotal() {
        const element = document.querySelector('[data-cart-total], .cart-total, .subtotal-amount');
        if (element) {
            return parseFloat(element.getAttribute('data-cart-total') || element.textContent.replace(/[^\d.]/g, ''));
        }
        return 0;
    }

    /**
     * Get current shipping price
     */
    function getShippingPrice() {
        const element = document.querySelector('[data-shipping-price], .shipping-amount, .shipping-price');
        if (element) {
            return parseFloat(element.getAttribute('data-shipping-price') || element.textContent.replace(/[^\d.]/g, ''));
        }
        return 0;
    }

    /**
     * Update COD fee display based on total
     * 
     * Rules:
     * - (Subtotal + Shipping) >= 10000 JPY: COD Fee = 440 JPY
     * - (Subtotal + Shipping) < 10000 JPY: COD Fee = 330 JPY
     */
    function updateCODFeeDisplay(data) {
        const cartTotal = data.cart_total || getCartTotal();
        const shippingPrice = data.shipping_price || getShippingPrice();
        const totalAmount = cartTotal + shippingPrice;

        // Calculate COD fee based on total
        let codFee = 330; // Default for < 10000
        if (totalAmount >= 10000) {
            codFee = 440;
        }

        console.log('[COD Fee Integration] Calculated fee:', {
            cart_subtotal: cartTotal,
            shipping: shippingPrice,
            total: totalAmount,
            cod_fee: codFee,
        });

        // Update COD fee display in DOM
        updateCODFeeInDOM(codFee, totalAmount);

        // Store in hidden input for form submission
        storeCODFeeInForm(codFee);

        // Trigger custom event for other components
        const event = new CustomEvent('cod-fee-updated', {
            detail: {
                cod_fee: codFee,
                total_with_cod: totalAmount + codFee,
            }
        });
        window.dispatchEvent(event);
    }

    /**
     * Update COD fee display in the DOM
     */
    function updateCODFeeInDOM(codFee, totalAmount) {
        // Find or create COD fee display element
        let codFeeElement = document.querySelector('.cod-fee-display');
        
        if (!codFeeElement) {
            // Create it if it doesn't exist
            const container = document.querySelector('.payment-method-container, .checkout-payment');
            if (container) {
                codFeeElement = document.createElement('div');
                codFeeElement.className = 'cod-fee-display mt-4 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800';
                container.appendChild(codFeeElement);
            }
        }

        if (codFeeElement) {
            codFeeElement.innerHTML = `
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            COD Fee (Cash on Delivery)
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Based on total: ¥${totalAmount.toLocaleString()}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-orange-600 dark:text-orange-400">
                            ¥${codFee.toLocaleString()}
                        </p>
                    </div>
                </div>
            `;
            codFeeElement.style.display = 'block';
        }
    }

    /**
     * Store COD fee in hidden input for form submission
     */
    function storeCODFeeInForm(codFee) {
        let hiddenInput = document.querySelector('input[name="cod_fee"]');
        
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'cod_fee';
            document.querySelector('form') && document.querySelector('form').appendChild(hiddenInput);
        }

        if (hiddenInput) {
            hiddenInput.value = codFee;
        }
    }

    /**
     * Hide COD fee display when non-COD payment is selected
     */
    function hideCODFeeDisplay() {
        const codFeeElement = document.querySelector('.cod-fee-display');
        if (codFeeElement) {
            codFeeElement.style.display = 'none';
        }

        // Clear hidden input
        const hiddenInput = document.querySelector('input[name="cod_fee"]');
        if (hiddenInput) {
            hiddenInput.value = '';
        }
    }

    // Export for use in Vue components
    window.CODFeeIntegration = {
        updateCODFeeDisplay,
        hideCODFeeDisplay,
        getCartTotal,
        getShippingPrice,
    };
</script>
@endpush
@endonce
