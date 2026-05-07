/**
 * Local registry of active customer-display pairing tokens for this terminal.
 * Read by the pagehide beacon to revoke server-side when the tab closes.
 *
 * Storage shape: localStorage['pos_customer_display_tokens'] = JSON array of strings.
 *
 * Active session for heartbeat: localStorage['pos_customer_display_session'] = {id, token}
 * Set by CustomerDisplaySettings.vue after pair() success.
 */

const KEY = 'pos_customer_display_tokens';
const SESSION_KEY = 'pos_customer_display_session';

function read() {
    try {
        const raw = localStorage.getItem(KEY);
        const arr = raw ? JSON.parse(raw) : [];
        return Array.isArray(arr) ? arr.filter(t => typeof t === 'string') : [];
    } catch (_) {
        return [];
    }
}

function write(arr) {
    try {
        localStorage.setItem(KEY, JSON.stringify(arr.slice(0, 20)));
    } catch (_) {
        /* quota or disabled — tolerate */
    }
}

export function rememberToken(token) {
    if (!token) return;
    const arr = read();
    if (!arr.includes(token)) {
        arr.push(token);
        write(arr);
    }
}

export function forgetToken(token) {
    write(read().filter(t => t !== token));
}

export function getTokens() {
    return read();
}

export function clearTokens() {
    write([]);
}

export function setActiveSession(session) {
    try {
        if (session && session.id && session.token) {
            localStorage.setItem(SESSION_KEY, JSON.stringify({ id: session.id, token: session.token }));
        } else {
            localStorage.removeItem(SESSION_KEY);
        }
    } catch (_) { /* tolerate */ }
}

export function getActiveSession() {
    try {
        const raw = localStorage.getItem(SESSION_KEY);
        const obj = raw ? JSON.parse(raw) : null;
        return (obj && obj.id && obj.token) ? obj : null;
    } catch (_) {
        return null;
    }
}

/**
 * Fire-and-forget heartbeat. Called from Cart.collectTotals after every
 * change to subtotal/tax/total. Failures are swallowed — customer display
 * is non-critical to checkout.
 *
 * Payload: {subtotal, tax, total, items, currency, state}
 *   state defaults to 'cart' (running totals as cashier scans).
 *   state='paid' is sent once at checkout success — locks the display on
 *   the paid total for `PAID_HOLD_MS` so the empty-cart heartbeat that
 *   fires from removeCart() doesn't immediately wipe the screen to ¥0.
 */
const PAID_HOLD_MS = 8000;

let inFlight = false;
let paidLockUntil = 0;

export async function pushHeartbeat(totals) {
    const session = getActiveSession();
    if (!session) return;
    if (inFlight) return;

    const accessToken = localStorage.getItem('accessToken');
    if (!accessToken) return;

    const state = totals.state || 'cart';

    // Suppress 'cart' heartbeats while the paid lock is active.
    // 'paid' heartbeats always go through.
    if (state === 'cart' && Date.now() < paidLockUntil) return;

    inFlight = true;
    try {
        await fetch(`/pos/api/customer-display/sessions/${session.id}/heartbeat`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
            body: JSON.stringify(totals),
            keepalive: true,
        });
        if (state === 'paid') {
            paidLockUntil = Date.now() + PAID_HOLD_MS;
        }
    } catch (_) { /* fail-soft */ }
    finally {
        inFlight = false;
    }
}
