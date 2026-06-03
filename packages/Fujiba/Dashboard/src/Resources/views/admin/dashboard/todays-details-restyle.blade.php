<style id="fujiba-todays-restyle-css">
    /* ─── Fujiba: scoped restyle of core 本日の詳細 (Today's Details) widget ───
       Anchored to the <v-dashboard-todays-details> custom element (only exists
       on dashboard route). Matches the white-card + colorful-number style used
       by Fujiba\Dashboard's Business Overview cards. */

    v-dashboard-todays-details .box-shadow.rounded {
        background: transparent !important;
        box-shadow: none !important;
        border: 0 !important;
    }

    /* The 3-card row */
    v-dashboard-todays-details .box-shadow.rounded > div {
        background: transparent !important;
        border: 0 !important;
        padding: 0 !important;
        gap: 12px !important;
    }

    /* Each individual card (Sales / Orders / Customers) — white card with colored border */
    v-dashboard-todays-details .box-shadow.rounded > div > div {
        flex: 1 1 0 !important;
        min-width: 220px !important;
        border-radius: 16px !important;
        padding: 20px 18px 22px !important;
        min-height: 150px !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        gap: 10px !important;
        background: #ffffff !important;
        border: 2px solid rgba(99,102,241,0.22) !important;
        box-shadow:
            0 2px 4px rgba(15,23,42,0.04),
            0 8px 24px -10px rgba(99,102,241,0.18) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    .dark v-dashboard-todays-details .box-shadow.rounded > div > div {
        background: rgb(17,24,39) !important;
    }
    /* Per-card border tint matching the number gradient */
    v-dashboard-todays-details .box-shadow.rounded > div > div:nth-child(1) {
        border-color: rgba(139,92,246,0.30) !important;
        box-shadow: 0 2px 4px rgba(15,23,42,0.04), 0 8px 24px -10px rgba(139,92,246,0.30) !important;
    }
    v-dashboard-todays-details .box-shadow.rounded > div > div:nth-child(2) {
        border-color: rgba(249,115,22,0.30) !important;
        box-shadow: 0 2px 4px rgba(15,23,42,0.04), 0 8px 24px -10px rgba(249,115,22,0.30) !important;
    }
    v-dashboard-todays-details .box-shadow.rounded > div > div:nth-child(3) {
        border-color: rgba(6,182,212,0.30) !important;
        box-shadow: 0 2px 4px rgba(15,23,42,0.04), 0 8px 24px -10px rgba(6,182,212,0.30) !important;
    }

    /* Hide the 60px icon image (first child of each card) */
    v-dashboard-todays-details .box-shadow.rounded > div > div > div:first-child {
        display: none !important;
    }

    /* The text block (.grid wrapper) — flex column centered */
    v-dashboard-todays-details .box-shadow.rounded > div > div > .grid {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
        gap: 6px !important;
        place-content: unset !important;
    }

    /* Big bold number (first <p>) — gradient color per card */
    v-dashboard-todays-details .box-shadow.rounded > div > div > .grid > p:nth-child(1) {
        font-size: clamp(36px,7vw,56px) !important;
        font-weight: 900 !important;
        line-height: 1.0 !important;
        letter-spacing: -0.025em !important;
        font-variant-numeric: tabular-nums !important;
        margin: 0 !important;
        order: 2 !important;
        background-image: linear-gradient(135deg,#6366f1,#8b5cf6,#ec4899) !important;
        -webkit-background-clip: text !important;
        background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        color: transparent !important;
    }
    @media (max-width: 640px) {
        /* Stack 3 cards vertically on phone, drop min-width */
        v-dashboard-todays-details .box-shadow.rounded > div {
            flex-direction: column !important;
            gap: 10px !important;
        }
        v-dashboard-todays-details .box-shadow.rounded > div > div {
            min-width: 0 !important;
            flex: 1 1 auto !important;
            min-height: 100px !important;
            padding: 14px 12px 16px !important;
        }
        v-dashboard-todays-details .box-shadow.rounded > div > div > .grid > p:nth-child(1) {
            font-size: clamp(20px,6vw,28px) !important;
        }
        v-dashboard-todays-details .box-shadow.rounded > div > div > .grid > p:nth-child(2) {
            font-size: 11px !important;
        }
    }
    /* Per-card number gradient */
    v-dashboard-todays-details .box-shadow.rounded > div > div:nth-child(1) > .grid > p:nth-child(1) {
        background-image: linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#ec4899 100%) !important;
    }
    v-dashboard-todays-details .box-shadow.rounded > div > div:nth-child(2) > .grid > p:nth-child(1) {
        background-image: linear-gradient(135deg,#f59e0b 0%,#fb923c 100%) !important;
    }
    v-dashboard-todays-details .box-shadow.rounded > div > div:nth-child(3) > .grid > p:nth-child(1) {
        background-image: linear-gradient(135deg,#06b6d4 0%,#10b981 100%) !important;
    }

    /* Label (second <p>) — muted neutral */
    v-dashboard-todays-details .box-shadow.rounded > div > div > .grid > p:nth-child(2) {
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.10em !important;
        color: #64748b !important;
        margin: 0 !important;
        order: 1 !important;
    }
    .dark v-dashboard-todays-details .box-shadow.rounded > div > div > .grid > p:nth-child(2) {
        color: #94a3b8 !important;
    }

    /* Percentage row — light pill */
    v-dashboard-todays-details .box-shadow.rounded > div > div > .grid > .flex {
        background: rgba(99,102,241,0.08) !important;
        padding: 3px 10px !important;
        border-radius: 999px !important;
        order: 3 !important;
    }

    /* ─── 2-column grid wrapping the four todays_details.after widgets ───
       Wrapper opened/closed by TodaysGridOpen/CloseListener. Children are
       the per-widget root divs (id="fujiba-*-card"). Stacks on screens <1024px. */
    .fujiba-todays-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        align-items: stretch;
    }
    .fujiba-todays-grid > [id^="fujiba-"][id$="-card"] {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    /* When the grid holds an odd number of cards (Cart Activity moved to the
       top left 3: PaymentMethods | SalesByState / OrdersByRegion alone), let
       that lone last card span both columns so there's no empty gap. */
    .fujiba-todays-grid > [id^="fujiba-"][id$="-card"]:last-child:nth-child(odd) {
        grid-column: 1 / -1;
    }
    .fujiba-todays-grid > [id^="fujiba-"][id$="-card"] > .fujiba-pie-card,
    .fujiba-todays-grid > [id^="fujiba-"][id$="-card"] > .fujiba-sbs-card,
    .fujiba-todays-grid > [id^="fujiba-"][id$="-card"] > [id$="-template"] {
        flex: 1 1 auto;
    }
    @media (max-width: 1024px) {
        .fujiba-todays-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
