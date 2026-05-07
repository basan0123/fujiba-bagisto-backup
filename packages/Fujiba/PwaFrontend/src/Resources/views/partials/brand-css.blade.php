@php
    /**
     * Per-channel brand CSS injected into every Bagisto Blade page (and the
     * PWA master.blade pulls the same fields directly). Channel-aware:
     * core()->getCurrentChannel() resolves from the request host so each
     * tenant's customer-facing pages get the right palette.
     */
    $brandCh   = core()->getCurrentChannel();
    $brandCode = $brandCh?->code;
    $brandLoc  = app()->getLocale();
    $bget      = fn (string $k) => core()->getConfigData($k, $brandCode, $brandLoc);

    $bPrimary    = $bget('general.design.brand_color.value')        ?: '#c8420a';
    $bAccent     = $bget('general.design.brand_color.accent_color') ?: '#f5a623';
    $bDark       = $bget('general.design.brand_color.dark_color')   ?: '#1a0800';
    $bBtnRadius  = $bget('general.design.brand_color.btn_radius')   ?: '12px';
    $bCardRadius = $bget('general.design.brand_color.card_radius')  ?: '18px';

    $bHeaderBg   = $bget('general.design.brand_color.header_bg')   ?: '';
    $bHeaderText = $bget('general.design.brand_color.header_text') ?: '';
    $bFooterBg   = $bget('general.design.brand_color.footer_bg')   ?: '';
    $bFooterText = $bget('general.design.brand_color.footer_text') ?: '';
    $bBtnText    = $bget('general.design.brand_color.button_text') ?: '#ffffff';

    $bRgb = sscanf($bPrimary, '#%02x%02x%02x') ?: [200, 66, 10];
@endphp

<style id="fujiba-brand-css">
    :root {
        --p:    {{ $bPrimary }};
        --p2:   {{ $bPrimary }};
        --acc:  {{ $bAccent }};
        --dark: {{ $bDark }};
        --br:   {{ $bBtnRadius }};
        --cr:   {{ $bCardRadius }};
        --p-rgb: {{ implode(',', $bRgb) }};
        --brand-color: {{ $bPrimary }};
        --brand-color-rgb: {{ implode(',', $bRgb) }};

        @if ($bHeaderBg)   --header-bg:   {{ $bHeaderBg }};   @endif
        @if ($bHeaderText) --header-text: {{ $bHeaderText }}; @endif
        @if ($bFooterBg)   --footer-bg:   {{ $bFooterBg }};   @endif
        @if ($bFooterText) --footer-text: {{ $bFooterText }}; @endif
        --btn-text: {{ $bBtnText }};
    }

    /* Primary CTAs across the Blade storefront. Tailwind utility classes
       like `primary-button` / `secondary-button` are re-themed here so the
       admin colour picker actually flows through. Buttons can be
       a-tags or buttons; both are covered. */
    .primary-button,
    button.primary-button,
    .add-to-cart-button,
    .place-order-button,
    .btn-primary {
        background-color: var(--brand-color) !important;
        border-color: var(--brand-color) !important;
        color: var(--btn-text) !important;
    }
    .secondary-button {
        background-color: var(--brand-color) !important;
        border-color: var(--brand-color) !important;
        color: var(--btn-text) !important;
        border-radius: var(--br) !important;
    }
    a.text-primary, .text-primary { color: var(--brand-color) !important; }

    /* Header background/text. Targets Bagisto Blade desktop + mobile nav.
       Empty admin values leave the rule with `var(--header-bg)` undefined so
       Bagisto's default (white) keeps applying. */
    @if ($bHeaderBg)
        header.lc-nav,
        .desktop-header,
        .mobile-header,
        .header-content,
        #pwa-header,
        header.navbar.navbar-top { background: var(--header-bg) !important; }
    @endif
    @if ($bHeaderText)
        header.lc-nav, header.lc-nav a, header.lc-nav button,
        .desktop-header, .desktop-header a,
        .mobile-header,  .mobile-header a,
        .header-content, .header-content a,
        header.navbar.navbar-top, header.navbar.navbar-top a { color: var(--header-text) !important; }
    @endif

    /* Footer background/text. */
    @if ($bFooterBg)
        footer.lc-footer,
        .footer-content,
        #pwa-footer { background: var(--footer-bg) !important; }
    @endif
    @if ($bFooterText)
        footer.lc-footer, footer.lc-footer a,
        .footer-content, .footer-content a,
        #pwa-footer, #pwa-footer a { color: var(--footer-text) !important; }
    @endif
</style>
