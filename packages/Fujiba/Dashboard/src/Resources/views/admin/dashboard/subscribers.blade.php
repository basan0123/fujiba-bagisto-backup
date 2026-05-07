@php
    $initialJson = json_encode($subscribersInitial, JSON_UNESCAPED_UNICODE);
    $endpointUrl = route('admin.fujiba.dashboard.subscribers');
@endphp

<div class="flex flex-col gap-2" id="fujiba-subscribers-card">
    <v-fujiba-subscribers
        endpoint="{{ $endpointUrl }}"
        :initial-data="{{ $initialJson }}"
    ></v-fujiba-subscribers>
</div>

<script type="text/x-template" id="v-fujiba-subscribers-template">
    <div class="fujiba-subs-shell">
        <div class="fujiba-subs-titlebar">
            <span class="fujiba-subs-title-icon">👥</span>
            <span class="fujiba-subs-title-text">@lang('fujiba::app.dashboard.subscribers.title')</span>
            <span class="fujiba-subs-channel" v-if="data.channel_name">@{{ data.channel_name }}</span>
        </div>

        <div class="fujiba-subs-grid">
            <!-- Total Subscribers -->
            <div class="fujiba-subs-kpi fujiba-subs-kpi-total">
                <div class="fujiba-subs-kpi-label">
                    @lang('fujiba::app.dashboard.subscribers.total')
                </div>
                <div class="fujiba-subs-kpi-value tabular-nums">
                    @{{ formatCount(data.stats.total) }}
                </div>
            </div>

            <!-- Subscriber Gain (current period) -->
            <div class="fujiba-subs-kpi fujiba-subs-kpi-gain">
                <div class="fujiba-subs-kpi-label">
                    @lang('fujiba::app.dashboard.subscribers.subscriber_gain')
                    <span class="fujiba-subs-kpi-period">@{{ periodLabel }}</span>
                </div>
                <div class="fujiba-subs-kpi-value tabular-nums" :class="gainClass">
                    @{{ gainPrefix }}@{{ formatCount(data.stats.gain) }}
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="fujiba-subs-loading">
            <span class="shimmer fujiba-subs-shimmer-bar"></span>
        </div>
    </div>
</script>

<style>
    .fujiba-subs-shell {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 12px -8px rgba(15,23,42,0.06);
        border: 1px solid rgba(15,23,42,0.06);
        overflow: hidden;
        position: relative;
    }
    .dark .fujiba-subs-shell { background: rgb(17,24,39); border-color: rgba(255,255,255,0.06); }

    .fujiba-subs-titlebar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 18px;
        border-bottom: 1px solid rgba(15,23,42,0.06);
    }
    .dark .fujiba-subs-titlebar { border-bottom-color: rgba(255,255,255,0.06); }
    .fujiba-subs-title-icon { font-size: 16px; }
    .fujiba-subs-title-text {
        font-size: 14px;
        font-weight: 600;
        color: #1e3a8a;
    }
    .dark .fujiba-subs-title-text { color: #93c5fd; }
    .fujiba-subs-channel {
        margin-left: auto;
        font-size: 11px;
        color: #6b7280;
        background: rgba(59,130,246,0.08);
        padding: 2px 8px;
        border-radius: 999px;
    }
    .dark .fujiba-subs-channel { color: #cbd5e1; background: rgba(59,130,246,0.18); }

    .fujiba-subs-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }

    .fujiba-subs-kpi {
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        position: relative;
    }
    .fujiba-subs-kpi + .fujiba-subs-kpi {
        border-left: 1px solid rgba(15,23,42,0.06);
    }
    .dark .fujiba-subs-kpi + .fujiba-subs-kpi { border-left-color: rgba(255,255,255,0.06); }

    .fujiba-subs-kpi-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 500;
        display: flex;
        align-items: baseline;
        gap: 6px;
        flex-wrap: wrap;
    }
    .dark .fujiba-subs-kpi-label { color: #9ca3af; }
    .fujiba-subs-kpi-period {
        font-size: 10px;
        color: #9ca3af;
    }

    .fujiba-subs-kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        letter-spacing: -0.01em;
    }
    .dark .fujiba-subs-kpi-value { color: #f3f4f6; }
    .fujiba-subs-kpi-value.is-up   { color: #059669; }
    .fujiba-subs-kpi-value.is-down { color: #dc2626; }
    .dark .fujiba-subs-kpi-value.is-up   { color: #34d399; }
    .dark .fujiba-subs-kpi-value.is-down { color: #f87171; }

    .fujiba-subs-loading {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2px;
    }
    .fujiba-subs-shimmer-bar {
        display: block;
        height: 2px;
        width: 100%;
        background: linear-gradient(90deg, transparent, rgba(59,130,246,0.5), transparent);
        animation: fujiba-subs-bar 1.2s linear infinite;
    }
    @keyframes fujiba-subs-bar {
        0%   { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @media (max-width: 640px) {
        .fujiba-subs-grid { grid-template-columns: 1fr; }
        .fujiba-subs-kpi + .fujiba-subs-kpi {
            border-left: 0;
            border-top: 1px solid rgba(15,23,42,0.06);
        }
        .dark .fujiba-subs-kpi + .fujiba-subs-kpi { border-top-color: rgba(255,255,255,0.06); }
        .fujiba-subs-kpi-value { font-size: 24px; }
    }
</style>

<script type="module">
    (function () {
        if (window.__fujibaSubscribersRegistered) return;
        window.__fujibaSubscribersRegistered = true;

        // Move our <style> block out of #app (where Vue may strip/skip it)
        // and into <head> so the browser parses it as a real stylesheet.
        try {
            const inApp = document.querySelectorAll('#app style');
            inApp.forEach(s => {
                if (s.textContent && s.textContent.includes('fujiba-subs-')) {
                    document.head.appendChild(s);
                }
            });
        } catch (e) { console.warn('Fujiba subscribers style relocate failed', e); }

        let tries = 0;
        function register() {
            tries++;
            if (typeof window.app === 'undefined' || !window.app || !window.app.component) {
                if (tries > 600) {
                    console.error('[Fujiba Subscribers] gave up waiting for app');
                    return;
                }
                return requestAnimationFrame(register);
            }

            const tplEl  = document.getElementById('v-fujiba-subscribers-template');
            const tplStr = tplEl ? tplEl.innerHTML : '';

            window.app.component('v-fujiba-subscribers', {
                template: tplStr,

                props: {
                    endpoint:    { type: String, required: true },
                    initialData: { type: Object, required: true },
                },

                data() {
                    return {
                        data: this.initialData,
                        isLoading: false,
                        currentFilters: null,
                    };
                },

                computed: {
                    gainClass() {
                        const g = Number(this.data?.stats?.gain ?? 0);
                        if (g > 0) return 'is-up';
                        if (g < 0) return 'is-down';
                        return '';
                    },

                    gainPrefix() {
                        const g = Number(this.data?.stats?.gain ?? 0);
                        return g > 0 ? '+' : '';
                    },

                    periodLabel() {
                        const p = this.data?.period;
                        if (!p?.start || !p?.end) return '';
                        return p.start + ' → ' + p.end;
                    },
                },

                mounted() {
                    this.$emitter.on('reporting-filter-updated', this.onFilterChange);
                },

                methods: {
                    formatCount(n) {
                        return Number(n || 0).toLocaleString();
                    },

                    async onFilterChange(filters) {
                        this.currentFilters = filters;
                        this.isLoading = true;
                        try {
                            const params = {
                                channel: filters.channel ?? '',
                                start:   filters.start ?? '',
                                end:     filters.end ?? '',
                                mode:    filters.mode ?? 'orders',
                            };
                            const resp = await this.$axios.get(this.endpoint, { params });
                            this.data = resp.data;
                        } catch (e) {
                            console.error('Fujiba subscribers fetch failed', e);
                        } finally {
                            this.isLoading = false;
                        }
                    },
                },
            });
        }

        register();
    })();
</script>
