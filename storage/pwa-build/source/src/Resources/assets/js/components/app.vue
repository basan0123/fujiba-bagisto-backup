<template>
    <div id="app-inner" :class="{ 'distraction-free': distractionFree }">

        <div slot="content">
            <pwa-topbar v-if="!distractionFree"></pwa-topbar>

            <header-nav v-if="!distractionFree" @toggleDrawer="handleToggleDrawer"></header-nav>

            <div class="page-view-container" :class="{ 'no-header-offset': distractionFree }">
                <drawer-sidebar ref="drawer">
                    <div class="drawer">

                        <div class="drawer-header">
                            <router-link :to="'/customer/login-register'" class="login-info" v-if="! currentUser">
                                <div class="avatar"></div>
                                <h2>{{ $t('Sign In') }}</h2>
                                <p>{{ $t('to your account') }}</p>
                                <i class="icon arrow-right-icon"></i>
                            </router-link>

                            <router-link :to="'/customer/account/dashboard'" class="login-info" v-if="currentUser">
                                <div class="avatar"></div>
                                <h2>{{ $t('Hello!') }}</h2>
                                <p>{{ currentUser.name }}</p>
                                <i class="icon arrow-right-icon"></i>
                            </router-link>
                        </div>

                        <div class="drawer-content">

                            <div class="drawer-box categories">
                                <h2>{{ $t('Categories') }}</h2>

                                <ul>
                                    <li :key="category.id" v-for="category in categories" class="category-list-item">
                                        <router-link :to="'/categories/' + category.id">
                                            {{ category.name }}
                                        </router-link>
                                        <i class="icon sharp-arrow-right-icon"  @click="openSubcategories(category.id, $event)"></i>
                                    </li>
                                </ul>
                            </div>

                            <div class="drawer-box account" v-if="currentUser">
                                <h2>{{ $t('Account') }}</h2>

                                <ul>
                                    <li>
                                        <router-link :to="'/customer/account/dashboard'">
                                            {{ $t('Dashboard') }}
                                            <i class="icon sharp-arrow-right-icon"></i>
                                        </router-link>
                                    </li>

                                    <li>
                                        <router-link :to="'/customer/account/wishlist'">
                                            {{ $t('Wishlist') }}
                                            <i class="icon sharp-arrow-right-icon"></i>
                                        </router-link>
                                    </li>

                                    <li>
                                        <router-link :to="'/compare'">
                                            {{ $t('Compare') }}
                                            <i class="icon sharp-arrow-right-icon"></i>
                                        </router-link>
                                    </li>

                                    <li>
                                        <router-link :to="'/customer/account/orders'">
                                            {{ $t('Orders') }}
                                            <i class="icon sharp-arrow-right-icon"></i>
                                        </router-link>
                                    </li>

                                    <li>
                                        <router-link :to="'/customer/account/addresses'">
                                            {{ $t('Address Book') }}
                                            <i class="icon sharp-arrow-right-icon"></i>
                                        </router-link>
                                    </li>

                                    <li>
                                        <router-link :to="'/customer/account/reviews'">
                                            {{ $t('Product Reviews') }}
                                            <i class="icon sharp-arrow-right-icon"></i>
                                        </router-link>
                                    </li>
                                </ul>
                            </div>

                            <div class="drawer-box preference" v-if="currencies.length > 1 || locales.length > 1">
                                <h2>{{ $t('Preference') }}</h2>

                                <ul>
                                    <li v-if="currencies.length > 1" @click="handleToggleDrawer(); bottomSheets.currency = true">
                                        {{ $t('Currency -') }} {{ currentCurrency.name }} ({{ currentCurrency.code }})
                                        <i class="icon sharp-arrow-right-icon"></i>
                                    </li>

                                    <li v-if="locales.length > 1" @click="handleToggleDrawer(); bottomSheets.locale = true">
                                        {{ $t('Language -') }} {{ currentLocale.name }} ({{ currentLocale.code }})
                                        <i class="icon sharp-arrow-right-icon"></i>
                                    </li>
                                </ul>
                            </div>

                            <div class="drawer-box theme-row">
                                <span>{{ $t('Dark mode') !== 'Dark mode' ? $t('Dark mode') : 'Dark mode' }}</span>
                                <button type="button" class="theme-pill" :class="{ on: isDarkMode }" @click="toggleTheme" aria-label="Toggle theme"></button>
                            </div>

                            <div class="drawer-box logout" v-if="currentUser">
                                <button class="logout-btn" @click="logout">{{ $t('Log Out') }}</button>
                            </div>

                        </div>

                    </div>
                </drawer-sidebar>

                <bottom-sheet :show="bottomSheets.subCategory" @onBottomSheetClose="bottomSheets.subCategory = false; ">
                    <h4 slot="header">
                    {{ $t('Sub Categories') }}
                    </h4>

                    <div slot="content">
                        <ul>
                            <li @click="redirectToCategory(subCategory.id)" :key="index" v-for="(subCategory, index) in subCategories[parent_id]">
                                <template v-if="subCategory">
                                    {{ subCategory.name }}
                                </template>
                            </li>
                        </ul>
                    </div>
                </bottom-sheet>

                <bottom-sheet v-if="currencies.length > 1" :show="bottomSheets.currency" @onBottomSheetClose="bottomSheets.currency = false; ">
                    <div slot="header">
                    {{ $t('Currency') }}
                    </div>

                    <div slot="content">
                        <ul>
                            <li :key="currency.id" v-for="currency in currencies">
                                <span class="radio" :class="currency.code">
                                    <input type="radio" :id="currency.code" name="currency" :checked="currency.code == currentCurrency.code" @change="switchCurrency(currency)">
                                    <label class="radio-view" :for="currency.code"></label>
                                    {{ currency.name }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </bottom-sheet>

                <bottom-sheet v-if="locales.length > 1" :show="bottomSheets.locale" @onBottomSheetClose="bottomSheets.locale = false; ">
                    <div slot="header">
                        {{ $t('Languages') }}
                    </div>

                    <div slot="content">
                        <ul>
                            <li :key="index" v-for="(locale, index) in locales">
                                <span class="radio" :class="locale.code">
                                    <input type="radio" :id="locale.code" name="locale" :checked="locale.code == currentLocale.code" @change="switchLocale(locale)">
                                    <label class="radio-view" :for="locale.code"></label>
                                    {{ locale.name }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </bottom-sheet>

                <router-view></router-view>
            </div>
        </div>

        <bottom-nav v-if="!distractionFree"></bottom-nav>

        <ajax-loader></ajax-loader>
    </div>
</template>

<script>
    import BottomSheet   from './shared/bottom-sheet';
    import HeaderNav     from './layouts/header-nav';
    import BottomNav     from './layouts/bottom-nav';
    import PwaTopbar     from './layouts/topbar';
    import AjaxLoader    from './common/ajax-loader';
    import DrawerSidebar from './common/drawer-sidebar';

    export default {
        name: 'app',

        components: { HeaderNav, BottomNav, PwaTopbar, BottomSheet, AjaxLoader, DrawerSidebar },

        data () {
			return {
                categories: [],
                subCategories: {},
                locales: window.config.locales,
                currencies: window.config.currencies,
                currentLocale: window.config.currentLocale,
                parent_id: window.channel.root_category_id,
                currentCurrency: window.config.currentCurrency,

                bottomSheets: {
                    locale: false,
                    currency: false,
                    subCategory: false,
                },

                currentUser: false,

                isDarkMode: false,
			}
		},

        computed: {
            distractionFree () {
                // Distraction-free routes hide topbar + header-nav + bottom-nav.
                // Cart and checkout-style routes belong here so the user
                // focuses on completing the purchase.
                var name = this.$route && this.$route.name;
                return ['cart', 'chat-checkout', 'onepage', 'order-success'].indexOf(name) !== -1;
            },
        },

        beforeCreate () {
            // Restore theme as early as possible to avoid a light->dark flash
            try {
                if (localStorage.getItem('pwa.theme') === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (_) {}
        },

        mounted () {
            // Messenger auto-login hand-off. The redirect URL ends with
            // ?fjbtok=<sanctum>; persist it to localStorage so subsequent
            // API calls authenticate, then strip from the URL so it's not
            // bookmarked/shared. We do this *before* reading currentUser so
            // the rest of mount sees the new token.
            this.consumeMessengerToken();

            this.currentUser = JSON.parse(localStorage.getItem('currentUser'));
            this.isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';

            var this_this = this;

            EventBus.$on('user-logged-in', function(user) {

                this_this.currentUser = user.data;
            });

            EventBus.$on('user-logged-out', function() {
                this_this.currentUser = null;
            });

            this.getCategories();
            this.applyFooterVisibility(this.$route);

            // Mark the body as mounted so the pre-paint shell can fade out.
            // Wait for one paint frame so the SPA's first render commits
            // before we start the cross-fade — eliminates any flash.
            this.$nextTick(function () {
                requestAnimationFrame(function () {
                    document.body.classList.add('pwa-mounted');
                });
            });
        },

        watch: {
            $route (to, from) {
                this.$refs.drawer.close();
                this.applyFooterVisibility(to);
            }
        },

        methods: {
            // One-shot hand-off from /pwa/messenger/autologin (and the other
            // two Messenger autologin routes). The backend redirects with
            // ?token=<one-time> — we POST it to /api/autologin to exchange
            // for a real Sanctum token, stash that in localStorage, and
            // strip the URL so the one-time token isn't shareable. The
            // legacy ?fjbtok= path is kept for any in-flight Chatpion link.
            consumeMessengerToken () {
                try {
                    var params = new URLSearchParams(window.location.search);
                    var legacy = params.get('fjbtok');
                    var oneTime = params.get('token');

                    var stripAndPersist = function (sanctum) {
                        localStorage.setItem('token', JSON.stringify(sanctum));
                        if (window.axios) {
                            window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + sanctum;
                        }
                        localStorage.removeItem('currentUser');
                        params.delete('fjbtok');
                        params.delete('token');
                        var qs = params.toString();
                        var clean = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
                        window.history.replaceState({}, document.title, clean);
                    };

                    if (legacy) {
                        stripAndPersist(legacy);
                        return;
                    }

                    if (! oneTime) return;

                    // Strip the one-time token from the visible URL up-front
                    // so a refresh doesn't replay it (the POST below also
                    // marks it used server-side, but this avoids leaving it
                    // in history even if the network call is in flight).
                    params.delete('token');
                    var qs = params.toString();
                    var clean = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
                    window.history.replaceState({}, document.title, clean);

                    fetch('/api/autologin', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ token: oneTime }),
                    })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (data) {
                            if (data && data.status === 'success' && data.token) {
                                stripAndPersist(data.token);
                            }
                        })
                        .catch(function () {});
                } catch (_) {}
            },

            applyFooterVisibility (route) {
                // Footer is hidden by default in CSS (avoids a first-paint
                // flash above content before Vue mounts). We add a body class
                // only when we're actually on the home route.
                try {
                    var name = route && route.name ? route.name : '';
                    document.body.classList.toggle('pwa-show-static-footer', name === 'home');
                } catch (_) {}
            },

            toggleTheme () {
                var next = this.isDarkMode ? 'light' : 'dark';
                if (next === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
                try { localStorage.setItem('pwa.theme', next); } catch (_) {}
                this.isDarkMode = (next === 'dark');
            },

            handleToggleDrawer () {
				if (this.$refs.drawer.active) {
					this.$refs.drawer.close();
				} else {
					this.$refs.drawer.open();
				}
			},

            getCategories (parent_id = window.channel.root_category_id) {
                EventBus.$emit('show-ajax-loader');

                this.$http.get("/api/v1/descendant-categories", { params: { parent_id } })
                    .then(response => {

                        EventBus.$emit('hide-ajax-loader');
                        if (parent_id == window.channel.root_category_id) {
                            this.categories = response.data.data;
                        } else {
                            this.subCategories[parent_id] = response.data.data;

                            this.handleToggleDrawer();
                            this.bottomSheets.subCategory = true;
                        }

                    })
                    .catch(error => {});
            },

            switchCurrency (currency) {
                this.bottomSheets.currency = false;

                EventBus.$emit('show-ajax-loader');

                this.$http.get("/api/v1/pwa-frontend/switch-currency", { params: { currency: currency.code } })
                    .then(function(response) {
                        EventBus.$emit('hide-ajax-loader');

                        window.location.reload()
                    })
                    .catch(function (error) {});
            },

            switchLocale (locale) {
                this.bottomSheets.locale = false;

                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.get("/api/v1/pwa-frontend/switch-locale", { params: { locale: locale.code } })
                    .then(function(response) {
                        EventBus.$emit('hide-ajax-loader');

                        this_this.$i18n.locale = locale.code;

                        window.location.reload()
                    })
                    .catch(function (error) {});
            },

            logout () {
                this.handleToggleDrawer();

                EventBus.$emit('show-ajax-loader');

                this.$http.post("/api/v1/customer/logout")
                    .then(function(response) {
                        EventBus.$emit('hide-ajax-loader');

                        localStorage.removeItem('currentUser');
                        localStorage.removeItem('token');

                        EventBus.$emit('user-logged-out');
                    });
            },

            openSubcategories(parent_id, event) {
                this.parent_id = parent_id;

                if (! this.subCategories[parent_id]) {
                    this.getCategories(parent_id);
                } else {
                    this.handleToggleDrawer();
                    this.bottomSheets.subCategory = true;
                }
            },

            redirectToCategory(categoryId) {
                this.$router.push({ name: 'category', params: { id: categoryId } })
                this.bottomSheets.subCategory = false;

            }
        }
    }
</script>


<style lang="scss">
    @import '~@/_variables.scss';

    .drawer {
        background: #F5F5F5;
        width: 100%;
        position: absolute;
        top: 0;
        bottom: 0;
        overflow-y: auto;

        .drawer-header {
            height: 132px;
            position: relative;

            .login-info {
                position: absolute;
                bottom: 0;
                padding: 15px;
                width: 100%;
                        color: #ffffff;

                .avatar {
                    width: 48px;
                    height: 48px;
                    float: left;
                    border-radius: 50%;
                    margin-right: 15px;
                }

                h2 {
                    color: #ffffff;
                    margin-top: 7px;
                }

                p {
                    color: #ffffff;
                    font-weight: 600;
                    font-size: 16px;
                }

                .arrow-right-icon {
                    position: absolute;
                    right: 0;
                    bottom: 15px;
                }
            }
        }

        .drawer-content {
            .drawer-box {
                padding: 15px 0px 0px 15px;
                background: #ffffff;
                margin-bottom: 15px;

                &:last-child {
                    margin-bottom: 0;
                }

                h2 {
                    font-size: 12px;
                    color: $font-light-black-color;
                    text-transform: uppercase;
                    margin-bottom: 5px;
                }

                ul {
                    li {
                        border-bottom: 1px solid rgba(0,0,0,0.08);
                        font-size: 14px;
                        font-weight: 600;

                        a {
                            color: rgba(0,0,0,0.86);
                            padding: 15px 15px 15px 0px;
                            display: block;
                        }

                        &:last-child {
                            border-bottom: 0;
                        }

                        .sharp-arrow-right-icon {
                            float: right;
                            opacity: 0.16;
                        }
                    }
                }

                &.preference {
                    ul {
                        li {
                            padding: 15px 15px 15px 0px;
                        }
                    }
                }

                &.logout {
                    padding: 15px;

                    button.logout-btn {
                        text-transform: uppercase;
                        border: 3px solid #000000;
                        font-size: 14px;
                        font-weight: 600;
                        color: #000000;
                        width: 100%;
                        background: #ffffff;
                        padding: 15px;
                    }
                }

                .category-list-item {
                    a {
                        overflow: hidden;
                        white-space: nowrap;
                        display: inline-block;
                        text-overflow: ellipsis;
                        width: calc(100% - 30px);
                    }

                    i {
                        top: 10px;
                        right: 10px;
                        position: relative;
                    }
                }
            }
        }
    }

    .page-view-container {
        top: 84px; /* topbar 32 + nav 52 */
        position: relative;
    }
    .page-view-container.no-header-offset {
        top: 0;
    }
</style>
