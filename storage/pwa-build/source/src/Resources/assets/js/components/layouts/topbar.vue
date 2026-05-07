<template>
    <div class="topbar" :class="{ hide: isScrollingDown || zoomActive }" v-if="show">
        <div class="tb-ticker" :title="tickerText">
            <span class="tb-bell">🔔</span>
            <span class="tb-ticker-text">{{ tickerText }}</span>
        </div>

        <div class="tb-controls">
            <div class="tb-sel" v-if="currencies.length > 1">
                <select v-model="curr" @change="onCurrency">
                    <option v-for="c in currencies" :key="c.code" :value="c.code">
                        {{ symbolFor(c.code) }} {{ c.code }}
                    </option>
                </select>
            </div>

            <div class="tb-sel" v-if="locales.length > 1">
                <select v-model="loc" @change="onLocale">
                    <option v-for="l in locales" :key="l.code" :value="l.code">
                        🌐 {{ l.code }}
                    </option>
                </select>
            </div>

            <button class="theme-pill" @click="toggleTheme" :aria-label="$t('Toggle theme')">
                {{ isDark ? '☀️' : '🌙' }}
            </button>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'pwa-topbar',

        data () {
            return {
                isDark: false,
                curr: '',
                loc: '',
                isScrollingDown: false,
                _lastScrollY: 0,
                _ticking: false,
                zoomActive: false,
            };
        },

        computed: {
            tickerText () {
                var t = (window.PWA_FRONTEND && window.PWA_FRONTEND.topbar && window.PWA_FRONTEND.topbar.text) || '';
                return t || this.$t('Welcome — fresh picks for you');
            },
            show () {
                if (window.PWA_FRONTEND && window.PWA_FRONTEND.topbar) {
                    var t = window.PWA_FRONTEND.topbar;
                    if (t.text || t.show_lang_switch || t.show_currency_switch || t.show_theme_toggle) return true;
                }
                return true;
            },
            currencies () {
                try {
                    return (window.config && window.config.currencies) || [];
                } catch (_) { return []; }
            },
            locales () {
                try {
                    return (window.config && window.config.locales) || [];
                } catch (_) { return []; }
            },
        },

        mounted () {
            this.isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            try {
                this.curr = (window.config && window.config.currentCurrency && window.config.currentCurrency.code) || '';
                this.loc  = (window.config && window.config.currentLocale  && window.config.currentLocale.code)  || '';
            } catch (_) {}
            this._lastScrollY = window.scrollY;
            window.addEventListener('scroll', this._onScroll, { passive: true });

            var self = this;
            this._onZoomOpen  = function () { self.zoomActive = true;  };
            this._onZoomClose = function () { self.zoomActive = false; };
            EventBus.$on('pwa.zoom.open',  this._onZoomOpen);
            EventBus.$on('pwa.zoom.close', this._onZoomClose);
        },

        beforeDestroy () {
            window.removeEventListener('scroll', this._onScroll);
            EventBus.$off('pwa.zoom.open',  this._onZoomOpen);
            EventBus.$off('pwa.zoom.close', this._onZoomClose);
        },

        methods: {
            symbolFor (code) {
                var m = { JPY: '¥', USD: '$', EUR: '€', GBP: '£', MMK: 'K' };
                return m[code] || code;
            },

            onCurrency () {
                if (!this.curr) return;
                EventBus.$emit('show-ajax-loader');
                this.$http.get('/api/v1/pwa-frontend/switch-currency', { params: { currency: this.curr } })
                    .then(function () { window.location.reload(); })
                    .catch(function () { EventBus.$emit('hide-ajax-loader'); });
            },

            onLocale () {
                if (!this.loc) return;
                var self = this;
                EventBus.$emit('show-ajax-loader');
                this.$http.get('/api/v1/pwa-frontend/switch-locale', { params: { locale: this.loc } })
                    .then(function () {
                        try { self.$i18n.locale = self.loc; } catch (_) {}
                        window.location.reload();
                    })
                    .catch(function () { EventBus.$emit('hide-ajax-loader'); });
            },

            _onScroll () {
                if (this._ticking) return;
                this._ticking = true;
                var self = this;
                requestAnimationFrame(function () {
                    var y = window.scrollY;
                    var delta = y - self._lastScrollY;
                    if (Math.abs(delta) >= 10) {
                        self.isScrollingDown = delta > 0 && y > 60;
                        self._lastScrollY = y;
                    }
                    self._ticking = false;
                });
            },

            toggleTheme () {
                var next = this.isDark ? 'light' : 'dark';
                if (next === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
                try { localStorage.setItem('pwa.theme', next); } catch (_) {}
                this.isDark = (next === 'dark');
            },
        },
    };
</script>

<style lang="scss" scoped>
    .topbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 32px;
        background: var(--dark, #1a0800);
        color: rgba(255,255,255,0.92);
        font-size: 11px;
        display: flex;
        align-items: center;
        padding: 0 10px;
        gap: 10px;
        z-index: 110;
        transition: transform 0.3s ease;
        will-change: transform;
    }
    .topbar.hide { transform: translateY(-100%); }

    .tb-ticker {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 6px;
        overflow: hidden;
        white-space: nowrap;
    }
    .tb-bell { flex: 0 0 auto; font-size: 12px; }
    .tb-ticker-text {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 500;
    }

    .tb-controls {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tb-sel {
        position: relative;
    }
    .tb-sel select {
        background: rgba(255,255,255,0.10);
        border: 0;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 18px 4px 8px;
        border-radius: 99px;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8' width='10' height='8' fill='%23ffffff'><polygon points='0,0 12,0 6,8'/></svg>");
        background-repeat: no-repeat;
        background-position: right 6px center;
    }
    .tb-sel select option { color: #1a0800; background: #fff; }

    .theme-pill {
        background: rgba(255,255,255,0.10);
        border: 0;
        border-radius: 99px;
        width: 26px; height: 22px;
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        padding: 0;
    }
    .theme-pill:hover { background: rgba(255,255,255,0.18); }
</style>
