import { ref } from 'vue';

/**
 * Composable for loading & persisting per-agent hardware settings (printer,
 * scanner, paper size) via the REST API at /api/v1/pos/agent/settings.
 */
export function useHardwareDevices() {
    const printers       = ref([]);
    const loadingPrinters = ref(false);

    /**
     * Fetch CUPS printer list from the server.
     * Returns an array of printer name strings.
     */
    async function loadPrinters() {
        loadingPrinters.value = true;

        try {
            const token    = localStorage.getItem('accessToken');
            const response = await fetch('/api/v1/pos/system/printers', {
                headers: {
                    'Accept'        : 'application/json',
                    'Authorization' : `Bearer ${token}`,
                },
            });

            if (response.ok) {
                const data   = await response.json();
                printers.value = data.printers ?? [];
            }
        } catch {
            // Non-fatal — printer list stays empty
        } finally {
            loadingPrinters.value = false;
        }
    }

    /**
     * Fetch the current agent's hardware settings from the API.
     * Returns an object: { paper_size, default_printer, default_scanner, extra }
     */
    async function loadSettings() {
        const token    = localStorage.getItem('accessToken');
        const response = await fetch('/api/v1/pos/agent/settings', {
            headers: {
                'Accept'        : 'application/json',
                'Authorization' : `Bearer ${token}`,
            },
        });

        if (! response.ok) {
            return null;
        }

        return response.json();
    }

    /**
     * Persist hardware settings for the current agent.
     * @param {object} settings  { paper_size?, default_printer?, default_scanner?, extra? }
     * @returns {object|null}    Updated settings or null on failure.
     */
    async function saveSettings(settings) {
        const token    = localStorage.getItem('accessToken');
        const response = await fetch('/api/v1/pos/agent/settings', {
            method : 'POST',
            headers: {
                'Content-Type'  : 'application/json',
                'Accept'        : 'application/json',
                'Authorization' : `Bearer ${token}`,
            },
            body: JSON.stringify(settings),
        });

        if (! response.ok) {
            return null;
        }

        return response.json();
    }

    return {
        printers,
        loadingPrinters,
        loadPrinters,
        loadSettings,
        saveSettings,
    };
}
