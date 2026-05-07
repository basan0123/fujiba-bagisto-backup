<template>
    <div class="min-h-screen bg-[#F2F2F7]">

        <!-- Section header -->
        <div class="pos-section-header mt-4 px-4">
            {{ t('pos.settings.customer_display.mode_section') }}
        </div>

        <!-- Mode selection list -->
        <div class="bg-white">
            <div
                v-for="(mode, index) in displayModes"
                :key="mode.value"
                class="flex cursor-pointer items-center px-4 py-4 active:bg-[#F2F2F7]"
                :class="index < displayModes.length - 1 ? 'border-b border-[#E5E5EA]' : ''"
                @click="selectMode(mode.value)"
            >
                <span class="flex-1 text-[17px] text-[#1C1C1E]">{{ mode.label }}</span>
                <span
                    v-if="selectedMode === mode.value"
                    class="icon-check text-xl text-[#00B8D9]"
                ></span>
            </div>
        </div>

        <p class="mt-2 px-4 text-[13px] text-[#8E8E93]">
            {{ t('pos.settings.customer_display.mode_helper') }}
        </p>

        <!-- Second screen setup section -->
        <template v-if="selectedMode === 'second_screen'">

            <div class="pos-section-header mt-6 px-4">
                {{ t('pos.settings.customer_display.second_screen_title') }}
            </div>

            <div class="bg-white">
                <div class="flex flex-col items-center px-4 py-6">

                    <!-- QR / loading / error states -->
                    <div
                        v-if="pairingState === 'loading'"
                        class="flex h-[220px] w-[220px] items-center justify-center rounded-2xl bg-[#F2F2F7]"
                    >
                        <span class="text-[14px] text-[#8E8E93]">
                            {{ t('pos.settings.customer_display.pairing_loading') }}
                        </span>
                    </div>

                    <div
                        v-else-if="pairingState === 'error'"
                        class="flex h-[220px] w-[220px] items-center justify-center rounded-2xl bg-[#FFEDED] px-4"
                    >
                        <span class="text-center text-[13px] leading-5 text-[#FF3B30]">
                            {{ pairingError || t('pos.settings.customer_display.pairing_error') }}
                        </span>
                    </div>

                    <div
                        v-else-if="pairingState === 'ready' && pairing"
                        class="rounded-2xl bg-white p-2"
                    >
                        <QrcodeVue
                            :value="pairing.url"
                            :size="220"
                            level="M"
                            background="#FFFFFF"
                            foreground="#000000"
                        />
                    </div>

                    <p
                        v-if="pairingState === 'ready' && pairing"
                        class="mt-4 text-center text-[13px] text-[#8E8E93]"
                    >
                        {{ t('pos.settings.customer_display.scan_qr') }}
                    </p>

                    <p
                        v-if="pairingState === 'ready' && pairing"
                        class="mt-1 break-all text-center text-[12px] text-[#3C3C43]"
                    >
                        {{ pairing.url }}
                    </p>

                    <ol class="mt-6 w-full space-y-3">
                        <li
                            v-for="(step, i) in setupSteps"
                            :key="i"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-[#00B8D9] text-[12px] font-bold text-white"
                            >{{ i + 1 }}</span>
                            <span class="text-[15px] leading-6 text-[#1C1C1E]">{{ step }}</span>
                        </li>
                    </ol>

                    <p class="mt-5 w-full text-[13px] text-[#8E8E93]">
                        {{ t('pos.settings.customer_display.wifi_hint') }}
                    </p>
                </div>
            </div>

            <!-- Discovery list -->
            <div class="pos-section-header mt-6 px-4">
                {{ t('pos.settings.customer_display.paired_devices') }}
            </div>

            <div class="bg-white">
                <div
                    v-if="!sessions.length"
                    class="px-4 py-6 text-center text-[13px] text-[#8E8E93]"
                >
                    {{ t('pos.settings.customer_display.no_devices') }}
                </div>

                <div
                    v-for="(s, idx) in sessions"
                    :key="s.id"
                    class="flex items-center px-4 py-3"
                    :class="idx < sessions.length - 1 ? 'border-b border-[#E5E5EA]' : ''"
                >
                    <div class="flex-1 min-w-0">
                        <div class="truncate text-[15px] text-[#1C1C1E]">
                            {{ s.paired_device_label || t('pos.settings.customer_display.awaiting') }}
                        </div>
                        <div class="mt-0.5 text-[12px]">
                            <span :class="s.online ? 'text-[#34C759]' : 'text-[#8E8E93]'">●</span>
                            <span class="ml-1 text-[#8E8E93]">
                                {{ s.online
                                    ? t('pos.settings.customer_display.online')
                                    : t('pos.settings.customer_display.offline', { time: relativeTime(s.last_seen_at) })
                                }}
                            </span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ml-3 text-[15px] text-[#FF3B30]"
                        @click="onRevoke(s.id)"
                    >
                        {{ t('pos.settings.customer_display.revoke') }}
                    </button>
                </div>
            </div>

        </template>

    </div>
</template>

<script setup>
    import { ref, watch, onMounted, onUnmounted } from 'vue';
    import { useI18n } from 'vue-i18n';
    import QrcodeVue from 'qrcode.vue';
    import { rememberToken, forgetToken, setActiveSession } from '@src/composable/customer-display-tokens';

    const { t } = useI18n();

    const STORAGE_KEY = 'pos_display_mode';

    const displayModes = [
        { value: 'none',              label: '利用しない' },
        { value: 'second_screen',     label: 'セカンドスクリーン' },
        { value: 'bluetooth_display', label: 'Bluetoothディスプレイ' },
        { value: 'network_share',     label: 'ネットワーク共有' },
    ];

    const setupSteps = [
        'お客様用iPhoneでカメラを起動',
        'QRコードをスキャン',
        'ブラウザでURLを開く',
        '接続完了',
    ];

    const selectedMode = ref(localStorage.getItem(STORAGE_KEY) ?? 'none');

    const pairing = ref(null);
    const pairingState = ref('idle');
    const pairingError = ref('');
    const sessions = ref([]);

    let pollTimer = null;

    const selectMode = (value) => {
        selectedMode.value = value;
        localStorage.setItem(STORAGE_KEY, value);
    };

    const authHeaders = () => {
        const token = localStorage.getItem('accessToken') || '';
        return token ? { Authorization: `Bearer ${token}`, Accept: 'application/json' } : { Accept: 'application/json' };
    };

    const startPairing = async () => {
        pairingState.value = 'loading';
        pairingError.value = '';

        try {
            const res = await fetch('/pos/api/customer-display/pair', {
                method:  'POST',
                headers: { ...authHeaders(), 'Content-Type': 'application/json' },
                body:    JSON.stringify({}),
            });

            if (!res.ok) {
                pairingState.value = 'error';
                pairingError.value = `HTTP ${res.status}`;
                return;
            }

            const data = await res.json();
            pairing.value = data;
            pairingState.value = 'ready';
            rememberToken(data.token);
            setActiveSession({ id: data.id, token: data.token });
            await loadSessions();
        } catch (e) {
            pairingState.value = 'error';
            pairingError.value = e?.message || '';
        }
    };

    const loadSessions = async () => {
        try {
            const res = await fetch('/pos/api/customer-display/sessions', {
                method:  'GET',
                headers: authHeaders(),
            });
            if (!res.ok) return;
            const data = await res.json();
            sessions.value = Array.isArray(data.sessions) ? data.sessions : [];
        } catch (_) { /* polling tolerates failures */ }
    };

    const onRevoke = async (id) => {
        if (!window.confirm(t('pos.settings.customer_display.revoke_confirm'))) return;

        const target = sessions.value.find(s => s.id === id);

        try {
            await fetch(`/pos/api/customer-display/sessions/${id}`, {
                method:  'DELETE',
                headers: authHeaders(),
            });
        } catch (_) { /* fall through to refresh anyway */ }

        if (pairing.value && target && pairing.value.id === id) {
            forgetToken(pairing.value.token);
            setActiveSession(null);
            pairing.value = null;
            pairingState.value = 'idle';
            await startPairing();
        } else {
            await loadSessions();
        }
    };

    const relativeTime = (iso) => {
        if (!iso) return '—';
        const t1 = new Date(iso).getTime();
        if (isNaN(t1)) return '—';
        const diff = Math.max(0, Math.floor((Date.now() - t1) / 1000));
        if (diff < 60)    return `${diff}s`;
        if (diff < 3600)  return `${Math.floor(diff / 60)}m`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
        return `${Math.floor(diff / 86400)}d`;
    };

    const startPolling = () => {
        if (pollTimer) return;
        pollTimer = setInterval(loadSessions, 5000);
    };

    const stopPolling = () => {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    };

    const onVisibilityChange = () => {
        if (document.visibilityState === 'visible' && selectedMode.value === 'second_screen') {
            loadSessions();
            startPolling();
        } else {
            stopPolling();
        }
    };

    watch(selectedMode, (mode) => {
        if (mode === 'second_screen') {
            startPairing();
            startPolling();
        } else {
            stopPolling();
            setActiveSession(null);
            pairing.value = null;
            pairingState.value = 'idle';
            pairingError.value = '';
        }
    }, { immediate: false });

    onMounted(() => {
        document.addEventListener('visibilitychange', onVisibilityChange);
        if (selectedMode.value === 'second_screen') {
            startPairing();
            startPolling();
        }
    });

    onUnmounted(() => {
        document.removeEventListener('visibilitychange', onVisibilityChange);
        stopPolling();
    });
</script>
