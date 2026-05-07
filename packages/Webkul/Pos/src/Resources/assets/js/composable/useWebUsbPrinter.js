/**
 * useWebUsbPrinter — WebUSB composable for USB thermal printers.
 *
 * Sends raw ESC/POS bytes directly from the browser to a USB printer
 * without any server-side CUPS dependency. Works on Chrome/Edge with HTTPS.
 *
 * Usage:
 *   const { supported, connecting, selectPrinter, printRaw } = useWebUsbPrinter();
 *   await selectPrinter();       // shows USB device picker once
 *   await printRaw(base64str);   // auto-reconnects to saved device
 */

import { ref } from 'vue';

const STORAGE_KEY = 'pos_usb_printer_v1';

/** Decode a base64 string to a Uint8Array */
function b64ToBytes(b64) {
    const bin = atob(b64);
    const arr = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
    return arr;
}

/**
 * Walk the device's interface/endpoint tree and return the first bulk-OUT
 * endpoint. Most USB thermal printers expose one on interface 0, endpoint 1.
 */
function findBulkOut(usbDevice) {
    for (const iface of usbDevice.configuration?.interfaces ?? []) {
        for (const alt of iface.alternates) {
            for (const ep of alt.endpoints) {
                if (ep.type === 'bulk' && ep.direction === 'out') {
                    return {
                        interfaceNumber: iface.interfaceNumber,
                        endpointNumber: ep.endpointNumber,
                    };
                }
            }
        }
    }
    // Fallback — common default for thermal printers
    return { interfaceNumber: 0, endpointNumber: 1 };
}

/** Open, configure and claim the device. Returns a handle or throws. */
async function openDevice(usbDevice) {
    try {
        await usbDevice.open();
    } catch (e) {
        if (!e.message?.includes('already')) throw e;
    }

    if (usbDevice.configuration === null) {
        await usbDevice.selectConfiguration(1);
    }

    const { interfaceNumber, endpointNumber } = findBulkOut(usbDevice);

    try {
        await usbDevice.claimInterface(interfaceNumber);
    } catch (e) {
        // "already claimed" is fine — another tab may have it
        if (!e.message?.includes('already claimed') && !e.message?.includes('already')) {
            throw e;
        }
    }

    return { usbDevice, interfaceNumber, endpointNumber };
}

export function useWebUsbPrinter() {
    const supported = typeof navigator !== 'undefined' && 'usb' in navigator;
    const connecting = ref(false);

    /**
     * Try to reconnect to the previously authorized device.
     * Returns a handle object or null.
     */
    async function getStoredDevice() {
        if (!supported) return null;

        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null');
        if (!saved) return null;

        try {
            const devices = await navigator.usb.getDevices();
            const match = devices.find(
                d => d.vendorId === saved.vendorId && d.productId === saved.productId
            );
            if (!match) return null;
            return await openDevice(match);
        } catch {
            return null;
        }
    }

    /**
     * Show the USB device picker so the user authorizes a printer.
     * Must be called from a user gesture (button click).
     * Returns true on success.
     */
    async function selectPrinter() {
        if (!supported) return false;
        connecting.value = true;
        try {
            const usbDevice = await navigator.usb.requestDevice({ filters: [] });
            const handle = await openDevice(usbDevice);

            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                vendorId: usbDevice.vendorId,
                productId: usbDevice.productId,
            }));

            return true;
        } catch {
            return false;
        } finally {
            connecting.value = false;
        }
    }

    /**
     * Send raw ESC/POS bytes (base64-encoded) to the USB printer.
     * Auto-reconnects to the last-authorized device.
     * If no device is stored, shows the picker automatically (requires user gesture).
     * Returns true on success.
     */
    async function printRaw(base64) {
        if (!supported) return false;

        let handle = await getStoredDevice();

        if (!handle) {
            // No stored / available device — show picker
            connecting.value = true;
            try {
                const usbDevice = await navigator.usb.requestDevice({ filters: [] });
                handle = await openDevice(usbDevice);

                localStorage.setItem(STORAGE_KEY, JSON.stringify({
                    vendorId: usbDevice.vendorId,
                    productId: usbDevice.productId,
                }));
            } catch {
                return false;
            } finally {
                connecting.value = false;
            }
        }

        const bytes = b64ToBytes(base64);
        // Send in 16KB chunks (safe for all USB bulk transfer implementations)
        const CHUNK = 16384;
        try {
            for (let offset = 0; offset < bytes.length; offset += CHUNK) {
                await handle.usbDevice.transferOut(
                    handle.endpointNumber,
                    bytes.slice(offset, offset + CHUNK)
                );
            }
            return true;
        } catch {
            // Device disconnected — clear stored info
            localStorage.removeItem(STORAGE_KEY);
            return false;
        }
    }

    return { supported, connecting, selectPrinter, printRaw };
}
