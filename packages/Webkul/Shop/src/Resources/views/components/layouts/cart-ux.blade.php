{{--
    Add-to-cart UX (Shopee/Lazada-style):
      - Suppresses default green flash for add-to-cart success
      - Fly-to-cart image animation (clones product image, animates to cart icon)
      - Cart-icon bounce on success
      - Live cart badge update via existing window.lcUpdateCartBadge / lcFetchCartCount
      - Tiny bottom toast ("Added to cart")

    Single-include module — no per-component edits required for the toast / bounce /
    badge update. Fly-to-cart triggers from clicks on any element with [data-add-to-cart]
    (set on the product card and PGF "add" buttons).
--}}
@pushOnce('styles')
<style>
/* Fly-to-cart clone */
.cux-fly{
    position:fixed;z-index:9998;pointer-events:none;
    width:64px;height:64px;border-radius:8px;overflow:hidden;
    box-shadow:0 8px 24px rgba(0,0,0,.18),0 1px 0 rgba(0,0,0,.08);
    background:#fff;
    will-change:transform,opacity,width,height,border-radius;
    transform:translate3d(0,0,0);
}
.cux-fly img{width:100%;height:100%;object-fit:cover;display:block}

/* Cart-icon bounce — applied briefly on success */
@keyframes cuxBounce{
    0%{transform:scale(1)}
    30%{transform:scale(1.30)}
    55%{transform:scale(.92)}
    80%{transform:scale(1.08)}
    100%{transform:scale(1)}
}
.cux-bounce{animation:cuxBounce .55s cubic-bezier(.4,.0,.2,1)}

/* Tiny bottom toast */
.cux-toast{
    position:fixed;left:50%;bottom:88px;transform:translate(-50%,12px);
    background:#111827;color:#fff;font-size:13px;font-weight:600;
    padding:9px 16px;border-radius:99px;
    box-shadow:0 6px 20px rgba(0,0,0,.25);
    opacity:0;pointer-events:none;z-index:9999;
    transition:opacity .18s ease,transform .18s ease;
    display:flex;align-items:center;gap:8px;max-width:88vw;white-space:nowrap;
}
.cux-toast.cux-show{opacity:1;transform:translate(-50%,0)}
.cux-toast .cux-check{
    width:16px;height:16px;border-radius:50%;background:#10b981;color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;
    flex:0 0 auto;
}
.cux-toast.cux-error .cux-check{background:#ef4444}
@media (min-width:1024px){
    .cux-toast{bottom:32px}
}

/* Reduced motion respect */
@media (prefers-reduced-motion:reduce){
    .cux-fly{transition:opacity .2s ease !important}
    .cux-bounce{animation:none}
    .cux-toast{transition:opacity .12s ease}
}
</style>
@endPushOnce

@pushOnce('scripts')
<script>
(function(){
    'use strict';

    /* ── Toast ───────────────────────────────────────────────── */
    var toastEl = null, toastTimer = null;
    function ensureToast(){
        if (toastEl) return toastEl;
        toastEl = document.createElement('div');
        toastEl.className = 'cux-toast';
        toastEl.innerHTML = '<span class="cux-check">✓</span><span class="cux-text"></span>';
        document.body.appendChild(toastEl);
        return toastEl;
    }
    function showToast(msg, opts){
        opts = opts || {};
        var el = ensureToast();
        el.classList.toggle('cux-error', !!opts.error);
        el.querySelector('.cux-text').textContent = msg;
        // restart animation
        el.classList.remove('cux-show');
        // force reflow so the next add re-triggers transition
        void el.offsetWidth;
        el.classList.add('cux-show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function(){ el.classList.remove('cux-show'); }, opts.duration || 1700);
    }

    /* ── Cart icon resolution ─────────────────────────────────
       Mobile bottom-nav cart icon takes priority on small screens
       (it's what the user actually sees), desktop header otherwise. */
    function findCartTarget(){
        var w = window.innerWidth;
        var mobileNav = document.querySelector('#lc-mobile-nav [data-nav="cart"] .lc-nav-icon-wrap');
        var desktopCart = document.querySelector('header .icon-cart, .icon-cart');
        if (w <= 768 && mobileNav) return mobileNav;
        if (desktopCart) return desktopCart;
        return mobileNav || desktopCart || null;
    }

    /* ── Bounce ───────────────────────────────────────────────
       Apply to the cart icon's container so badge moves with it. */
    function bounceCart(){
        var t = findCartTarget();
        if (!t) return;
        t.classList.remove('cux-bounce');
        void t.offsetWidth;
        t.classList.add('cux-bounce');
        setTimeout(function(){ t.classList.remove('cux-bounce'); }, 700);
    }

    /* ── Badge update ─────────────────────────────────────────
       Prefer the qty from the AJAX response (no extra request);
       fall back to the existing global fetcher if shape unknown. */
    function updateBadgeFromPayload(payload){
        var qty = null;
        try {
            if (payload && typeof payload === 'object') {
                if (typeof payload.items_qty === 'number') qty = payload.items_qty;
                else if (typeof payload.items_count === 'number') qty = payload.items_count;
                else if (payload.data && typeof payload.data.items_qty === 'number') qty = payload.data.items_qty;
                else if (payload.data && typeof payload.data.items_count === 'number') qty = payload.data.items_count;
            }
        } catch(e){}

        if (qty !== null && typeof window.lcUpdateCartBadge === 'function') {
            window.lcUpdateCartBadge(qty);
            // Mirror to the global event so any other listeners (header) react too.
            window.dispatchEvent(new CustomEvent('lc-cart-updated', { detail: qty }));
            return;
        }
        // Unknown shape — fetch authoritative count.
        if (typeof window.lcFetchCartCount === 'function') window.lcFetchCartCount();
    }

    /* ── Fly-to-cart ──────────────────────────────────────────
       From the clicked button, find the nearest product image
       and animate a clone to the cart icon. */
    function findProductImage(button){
        if (!button) return null;
        // Look up the card root, then any <img> inside it.
        var root = button.closest(
            '[data-product-card], .pgf-card, .pgf-list-card, .product-card, ' +
            '.group, [data-add-to-cart-root]'
        ) || button.parentElement;
        if (!root) return null;
        var img = root.querySelector('img');
        return img && img.naturalWidth > 0 ? img : null;
    }

    function flyToCart(button){
        var src = findProductImage(button);
        var target = findCartTarget();
        if (!src || !target) return;

        var srcRect = src.getBoundingClientRect();
        var tgtRect = target.getBoundingClientRect();
        if (srcRect.width === 0 || tgtRect.width === 0) return;

        var clone = document.createElement('div');
        clone.className = 'cux-fly';
        var img = document.createElement('img');
        img.src = src.currentSrc || src.src;
        img.alt = '';
        clone.appendChild(img);

        var startX = srcRect.left + srcRect.width / 2 - 32;
        var startY = srcRect.top + srcRect.height / 2 - 32;
        var endX   = tgtRect.left + tgtRect.width / 2 - 18;
        var endY   = tgtRect.top + tgtRect.height / 2 - 18;

        clone.style.left = '0';
        clone.style.top = '0';
        clone.style.transform = 'translate3d(' + startX + 'px,' + startY + 'px,0) scale(1)';
        document.body.appendChild(clone);

        // Curved path via mid-point keyframe (slight arc up).
        var midX = (startX + endX) / 2;
        var midY = Math.min(startY, endY) - 80;

        var anim = clone.animate([
            { transform: 'translate3d(' + startX + 'px,' + startY + 'px,0) scale(1)',     opacity: 1 },
            { transform: 'translate3d(' + midX   + 'px,' + midY   + 'px,0) scale(.85)',   opacity: .95, offset: .55 },
            { transform: 'translate3d(' + endX   + 'px,' + endY   + 'px,0) scale(.25)',   opacity: .35 }
        ], {
            duration: 700,
            easing: 'cubic-bezier(.55,.05,.4,.95)',
            fill: 'forwards'
        });

        anim.onfinish = function(){
            try { clone.remove(); } catch(e){}
            bounceCart();
        };
        // Safety: remove even if onfinish never fires.
        setTimeout(function(){ try { clone.remove(); } catch(e){} }, 1200);
    }

    /* ── Capture clicks on add-to-cart triggers ───────────────
       We listen on document so dynamically-rendered cards (Vue) work
       without per-component wiring. */
    document.addEventListener('click', function(e){
        var btn = e.target.closest('[data-add-to-cart], .pgf-cart-btn, .pgf-list-cart-btn');
        if (!btn) return;
        if (btn.disabled) return;
        // Defer one frame so Vue's @click handler runs first
        // and we don't fly when the click was actually a quantity-cap warning.
        requestAnimationFrame(function(){ flyToCart(btn); });
    }, true);

    /* ── Hook into global emitter for success/failure ─────────
       The product card + PGF emit `update-mini-cart` on success and
       `add-flash` for messages. We intercept add-flash to suppress the
       default green box when it's an add-to-cart success, and listen for
       `update-mini-cart` to drive bounce + badge + toast. */
    function isAddToCartSuccessFlash(flash){
        if (!flash || flash.type !== 'success') return false;
        if (flash.meta && flash.meta.silent) return true;
        // Best-effort heuristic for default Bagisto success messages.
        var m = (flash.message || '').toLowerCase();
        return m.indexOf('cart') !== -1 || m.indexOf('カート') !== -1;
    }

    function patchEmitter(emitter){
        if (!emitter || emitter.__cuxPatched) return;
        emitter.__cuxPatched = true;

        var origEmit = emitter.emit.bind(emitter);
        emitter.emit = function(event, payload){
            if (event === 'add-flash' && isAddToCartSuccessFlash(payload)) {
                // Swallow default green flash; we'll show our own toast on
                // update-mini-cart (or right here if no payload arrives).
                return;
            }
            return origEmit(event, payload);
        };

        emitter.on('update-mini-cart', function(payload){
            updateBadgeFromPayload(payload);
            // Toast (concise — translated via blade-injected label below).
            showToast(window.cuxLabels && window.cuxLabels.added || 'Added to cart');
        });
    }

    // The emitter is attached to the Vue app on mount; poll briefly until ready.
    // After mount, `app.config` may be undefined — fall back to `app._context.config`.
    var tries = 0;
    (function attach(){
        var app = window.app;
        var cfg = app && (app.config || (app._context && app._context.config));
        var emitter = cfg && cfg.globalProperties && cfg.globalProperties.$emitter;
        if (emitter) { patchEmitter(emitter); return; }
        if (++tries < 60) setTimeout(attach, 50);
    })();
})();
</script>
<script>
    // Localizable labels for the toast (no rebuild needed; blade renders these).
    // `__()` returns the key string when the translation is missing, so the
    // `?: 'Added to cart'` fallback never fires — explicit equality check.
    @php
        $cuxAddedKey   = 'shop::app.components.products.card.add-to-cart-success';
        $cuxAddedLabel = __($cuxAddedKey);
        if ($cuxAddedLabel === $cuxAddedKey) {
            $cuxAddedLabel = match (app()->getLocale()) {
                'ja' => 'カートに追加しました',
                'my' => 'စျေးဝယ်ခြင်းတွင်ထည့်ပြီးပြီ',
                default => 'Added to cart',
            };
        }
    @endphp
    window.cuxLabels = {
        added: @json($cuxAddedLabel)
    };
</script>
@endPushOnce
