<template>
    <div>
        <button
            type="button"
            class="secondary-button w-full"
            :disabled="printing"
            @click="printInvoice"
        >
            <span
                class="text-2xl"
                :class="printing ? 'icon-spinner animate-spin' : 'icon-print'"
            ></span>

            {{ receiptType === 'ryoshuho' ? $t('pos.orders.print.ryoshuho_btn') : $t('pos.orders.print.btn_title') }}
        </button>
    </div>
</template>

<script setup>
    import { ref, inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useWebUsbPrinter } from '@src/composable/useWebUsbPrinter';

    const props = defineProps({
        outletOrder: {
            type: Object,
            required: true
        },
        receiptType: {
            type: String,
            default: null
        }
    });

    const DB      = useIndexedDB();
    const emitter = inject('emitter');
    const { t }   = useI18n();

    const printing = ref(false);

    const { supported: webUsbSupported, printRaw } = useWebUsbPrinter();

    // ─────────────────────────────────────────────────────────────────────────
    // One print handler — same path for every order regardless of language.
    //
    // 1. Call the backend /api/v1/pos/print-receipt for every order.
    //    The backend detects non-ASCII and automatically switches between:
    //      • ESC/POS text commands  (ASCII product names)
    //      • GD bitmap → ESC/POS raster using Pyidaungsu / IPA Gothic fonts
    //        (Myanmar / Japanese product names)
    //    Both return the same `raw` base64 field — no branching here.
    //
    // 2. Send raw bytes via WebUSB (direct to printer, bypasses CUPS/PostScript).
    //
    // 3. If WebUSB is unavailable, open an HTML fallback window.
    //    Fonts are loaded from the local server (/fonts/*) so they are
    //    available immediately and document.fonts.ready works correctly.
    // ─────────────────────────────────────────────────────────────────────────
    const printInvoice = async () => {
        if (printing.value) return;

        const posOrderId = props.outletOrder?.id;
        if (! posOrderId) {
            emitter.emit('add_flash', {
                type:    'warning',
                message: t('pos.orders.print.no_receipt'),
            });
            return;
        }

        printing.value = true;

        try {
            const hw        = await DB.getItem('agent_hardware_settings', 1);
            const paperSize = hw?.paper_size      ?? '80mm';
            const printer   = hw?.default_printer ?? null;

            const token    = localStorage.getItem('accessToken');
            const response = await fetch('/api/v1/pos/print-receipt', {
                method : 'POST',
                headers: {
                    'Content-Type' : 'application/json',
                    'Accept'       : 'application/json',
                    'Authorization': `Bearer ${token}`,
                },
                body: JSON.stringify({
                    pos_order_id:  posOrderId,
                    paper_size:    paperSize,
                    printer:       printer,
                    receipt_type:  props.receiptType ?? null,
                }),
            });

            if (! response.ok) {
                const data = await response.json().catch(() => ({}));
                emitter.emit('add_flash', {
                    type:    'error',
                    message: data.message ?? t('pos.orders.print.print_failed'),
                });
                return;
            }

            const data = await response.json();

            // Try WebUSB first — sends raw ESC/POS bytes directly to the printer,
            // bypassing the OS print system (CUPS/PostScript) entirely.
            if (data.raw && webUsbSupported) {
                const sent = await printRaw(data.raw);
                if (sent) {
                    emitter.emit('add_flash', {
                        type:    'success',
                        message: t('pos.orders.print.print_sent'),
                    });
                    return;
                }
            }

            // WebUSB unavailable — open HTML receipt window.
            // Prefer server-rendered HTML (has outlet name/address, no cashier).
            // Falls back to client-built HTML only if backend didn't return one.
            if (data.html) {
                openPrintWindow(data.html);
            } else {
                printHtmlWindow(props.outletOrder, paperSize, props.receiptType);
            }

        } catch (err) {
            emitter.emit('add_flash', {
                type:    'error',
                message: t('pos.orders.print.print_failed'),
            });
        } finally {
            printing.value = false;
        }
    };

    // Open a popup window and write the given HTML — used for server-rendered
    // receipt HTML that already contains its own auto-print script.
    function openPrintWindow(html) {
        const win = window.open('', '_blank', 'width=480,height=700');
        if (! win) {
            emitter.emit('add_flash', {
                type:    'warning',
                message: t('pos.orders.print.popup_blocked'),
            });
            return;
        }
        win.document.open();
        win.document.write(html);
        win.document.close();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTML fallback — fonts served from the local Bagisto server.
    // Pyidaungsu for Myanmar, IPA Gothic for Japanese, Hind for Latin.
    // ─────────────────────────────────────────────────────────────────────────
    function printHtmlWindow(outletOrder, paperSize, receiptType = null) {
        const order = outletOrder?.order ?? {};
        const items = order.items        ?? [];
        const width = paperSize === '58mm' ? '58mm' : '80mm';
        const fmt   = (v) => parseFloat(v ?? 0).toFixed(2);

        const itemRows = items.map(item => {
            const qty   = parseInt(item.qty_ordered ?? item.qty ?? 1);
            const total = fmt(item.total ?? (parseFloat(item.price ?? 0) * qty));
            return `<tr>
                <td>${item.name ?? ''}</td>
                <td class="r">${qty}</td>
                <td class="r">${fmt(item.price)}</td>
                <td class="r">${total}</td>
            </tr>`;
        }).join('');

        const taxAmt      = parseFloat(order.taxAmount      ?? order.taxTotal ?? 0);
        const discountAmt = parseFloat(order.discountAmount ?? 0);

        // Base URL of the current page so relative /fonts/ path resolves correctly
        const base = window.location.origin;

        const html = `<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  @font-face {
    font-family: 'Pyidaungsu';
    src: url('${base}/fonts/Pyidaungsu-Regular.ttf') format('truetype');
    font-weight: normal; font-style: normal;
  }
  @font-face {
    font-family: 'IPAGothic';
    src: url('${base}/fonts/IPAGothic-Regular.ttf') format('truetype');
    font-weight: normal; font-style: normal;
  }
  @font-face {
    font-family: 'Hind';
    src: url('${base}/fonts/Hind-Regular.ttf') format('truetype');
    font-weight: normal; font-style: normal;
  }
  /* Lock paper size for browsers that honor @page; !important so user/host stylesheet can't undo. */
  @page{size:${width} auto !important;margin:3mm 2mm !important}
  @page :first{size:${width} auto !important;margin:3mm 2mm !important}
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{width:${width} !important;max-width:${width} !important;margin:0 !important;padding:0 !important;background:#fff}
  body{
    font-family:'IPAGothic','Pyidaungsu','Hind','Courier New',Courier,monospace;
    font-size:11px
  }
  @media print{
    html,body{width:${width} !important;max-width:${width} !important;margin:0 !important;padding:0 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;zoom:1 !important;transform:none !important}
    .no-print{display:none !important}
  }
  h1{font-size:14px;text-align:center;margin-bottom:2px}
  .c{text-align:center}.r{text-align:right}
  hr{border:none;border-top:1px dashed #000;margin:4px 0}
  table{width:100%;border-collapse:collapse}
  th,td{padding:1px 2px;font-size:11px}
  th{text-align:left;border-bottom:1px dashed #000}
  th.r,td.r{text-align:right}
  .grand td{font-size:13px;font-weight:bold;border-top:1px dashed #000;padding-top:3px}
  .hint-bar{position:fixed;top:0;left:0;right:0;background:#1C1C1E;color:#fff;font:600 12px/1.4 system-ui,sans-serif;padding:8px 12px;display:flex;justify-content:space-between;align-items:center;z-index:9999}
  .hint-bar button{background:#00B8D9;color:#fff;border:0;border-radius:6px;padding:6px 12px;font:inherit;cursor:pointer}
  .hint-bar small{opacity:.85;font-weight:400}
  .rcpt{margin-top:44px}
</style></head><body>
  <div class="hint-bar no-print">
    <span>Paper size: <strong>${width}</strong> &nbsp; <small>If preview shows A4, change Paper Size in the dialog.</small></span>
    <button onclick="window.print()">Print</button>
  </div>
  <div class="rcpt">
  <h1>${receiptType === 'ryoshuho' ? '領収証' : (document.title || '')}</h1>
  ${receiptType === 'ryoshuho' ? '<div class="c" style="font-size:10px;margin-bottom:4px;">（正式領収書）</div>' : ''}
  <div class="c">${new Date().toLocaleString()}</div>
  ${order.id ? `<div class="c">Order #${order.id}</div>` : ''}
  <hr>
  <table><thead><tr>
    <th>Item</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Total</th>
  </tr></thead>
  <tbody>${itemRows}</tbody></table>
  <hr>
  <table>
    <tr><td>Subtotal</td><td class="r">${fmt(order.subTotal)}</td></tr>
    ${taxAmt > 0      ? `<tr><td>Tax</td><td class="r">${fmt(taxAmt)}</td></tr>` : ''}
    ${discountAmt > 0 ? `<tr><td>Discount</td><td class="r">-${fmt(discountAmt)}</td></tr>` : ''}
    <tr class="grand"><td>TOTAL</td><td class="r">${fmt(order.grandTotal)}</td></tr>
  </table>
  <hr><div class="c" style="margin-top:6px">Thank you!</div>
  </div>
<script>
// Wait for window.load (fonts CSS parsed) then document.fonts.ready
// (font files downloaded from local server) before printing.
// Don't auto-close: cashier may need to retry with a different paper size.
window.addEventListener('load', function () {
  var go = function () { try { window.focus(); window.print(); } catch(e) {} };
  document.fonts && document.fonts.ready ? document.fonts.ready.then(go).catch(go) : go();
});
<\/script>
</body></html>`;

        const win = window.open('', '_blank', 'width=480,height=700');
        if (! win) {
            emitter.emit('add_flash', {
                type:    'warning',
                message: t('pos.orders.print.popup_blocked'),
            });
            return;
        }
        win.document.write(html);
        win.document.close();
    }
</script>
