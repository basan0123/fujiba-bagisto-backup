#!/usr/bin/env bash
# =============================================================================
# Thermal Printer RAW Queue Setup
# Run this on each cashier station (or on the server if printer is USB-to-server)
# =============================================================================
set -e

PRINTER_NAME="${1:-ThermalPrinter}"

echo "=== Starting CUPS ==="
sudo systemctl start cups
sudo systemctl enable cups

echo ""
echo "=== Detecting USB printer device ==="
USB_URI=$(lpinfo -v 2>/dev/null | grep -i "usb://" | head -1 | awk '{print $2}')

if [ -z "$USB_URI" ]; then
    echo "ERROR: No USB printer detected."
    echo "Check: lsusb | grep -i printer"
    echo "Ensure printer is powered on and USB cable is connected."
    exit 1
fi

echo "Found USB device: $USB_URI"

echo ""
echo "=== Removing any existing queue named '$PRINTER_NAME' ==="
sudo lpadmin -x "$PRINTER_NAME" 2>/dev/null || true

echo ""
echo "=== Adding RAW printer queue (no PostScript filter) ==="
sudo lpadmin \
    -p "$PRINTER_NAME" \
    -E \
    -v "$USB_URI" \
    -m raw

echo ""
echo "=== Accepting jobs and enabling printer ==="
sudo cupsenable "$PRINTER_NAME"
sudo cupsaccept "$PRINTER_NAME"

echo ""
echo "=== Verifying — filter should show 'raw' ==="
lpoptions -p "$PRINTER_NAME" -l 2>/dev/null | grep -i "filter" || true
lpoptions -p "$PRINTER_NAME" | tr ' ' '\n' | grep -i "raw\|filter" || true

echo ""
echo "=== Test print — should print 'Hello Thermal' and cut ==="
printf "\x1b\x40Hello Thermal\n\x1d\x56\x41\x03" | lp -d "$PRINTER_NAME" -o raw

echo ""
echo "=== Done ==="
echo "Printer '$PRINTER_NAME' is ready."
echo "In POS Hardware Settings, set 'Default Printer' to: $PRINTER_NAME"
