import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Global passive barcode scanner composable.
 *
 * A hardware barcode scanner acts like a keyboard — it types characters
 * very fast (< 50 ms between keys) then sends Enter. This composable
 * listens to window keydown events, distinguishes scanner bursts from
 * normal human typing by timing, and fires onScan(barcode) on Enter.
 *
 * @param {Function} onScan   - Callback fired with the scanned string.
 * @param {Object}   options  - Optional config.
 * @param {Ref<boolean>} options.disabled - When true, scanner is paused.
 */
export function useBarcodeScanner(onScan, options = {}) {
    const buffer        = ref('')
    const lastKeyTime   = ref(0)
    const scannerActive = ref(false)

    const SCAN_SPEED_THRESHOLD = 50   // ms — scanner types faster than this
    const MIN_BARCODE_LENGTH   = 3    // ignore accidental single keystrokes

    // Duplicate-scan prevention state
    let lastScanned     = ''
    let lastScannedTime = 0

    // Timer handle to auto-reset scannerActive
    let activeTimer = null

    function handleKeydown(event) {
        // Respect disabled option (e.g. modal open)
        if (options?.disabled?.value) return

        // Ignore keystrokes while the user is typing in a form field
        const tag = document.activeElement?.tagName?.toLowerCase()
        if (['input', 'textarea', 'select'].includes(tag)) return

        const now      = Date.now()
        const timeDiff = now - lastKeyTime.value
        lastKeyTime.value = now

        // Long gap → start of a new scan attempt, reset buffer
        if (timeDiff > 300) {
            buffer.value = ''
        }

        if (event.key === 'Enter') {
            const scanned = buffer.value.trim()
            buffer.value  = ''

            // Reset scanner-active indicator after short delay
            clearTimeout(activeTimer)
            activeTimer = setTimeout(() => {
                scannerActive.value = false
            }, 500)

            if (scanned.length < MIN_BARCODE_LENGTH) return

            // Deduplicate: ignore the same barcode within 1 500 ms
            if (scanned === lastScanned && now - lastScannedTime < 1500) return

            lastScanned     = scanned
            lastScannedTime = now

            onScan(scanned)
            return
        }

        // Collect printable characters only
        if (event.key.length === 1) {
            buffer.value += event.key

            // Light up the scanner-active indicator on first char of a burst
            if (!scannerActive.value) {
                scannerActive.value = true
            }

            // Auto-reset if no Enter arrives within 500 ms (incomplete scan)
            clearTimeout(activeTimer)
            activeTimer = setTimeout(() => {
                scannerActive.value = false
                buffer.value        = ''
            }, 500)
        }
    }

    onMounted(() => {
        window.addEventListener('keydown', handleKeydown)
    })

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeydown)
        clearTimeout(activeTimer)
    })

    return { buffer, scannerActive }
}
