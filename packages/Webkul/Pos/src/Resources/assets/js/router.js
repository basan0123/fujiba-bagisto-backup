import Login from "@components/anonymous/Login.vue";
import PageNotFound from "@components/anonymous/pageNotFound.vue";
import MainLayout from "@components/secured/layouts/Main.vue";
import Dashboard from "@components/secured/Dashboard.vue";
import Home from "@components/secured/home/Main.vue";
import Customers from "@components/secured/customers/Main.vue";
import CustomerCreate from "@components/secured/customers/Create.vue";
import CustomerEdit from "@components/secured/customers/Edit.vue";
import Cashier from "@components/secured/cashier/Main.vue";
import Orders from "@components/secured/orders/Main.vue";
import Products from "@components/secured/products/Main.vue";
import ProductListMobile from "@components/secured/products/ProductListMobile.vue";
import ProductCreate from "@components/secured/products/ProductCreate.vue";
import ProductEdit from "@components/secured/products/ProductEdit.vue";
import Inventory from "@components/secured/products/Inventory.vue";
import StockInception from "@components/secured/products/StockInception.vue";
import Reports from "@components/secured/Reports.vue";
import Settings from "@components/secured/settings/Main.vue";
import Payment from "@components/secured/Payment.vue";
import { createRouter, createWebHistory } from "vue-router";
import I18n from '@src/plugins/i18n';

const routes = [
    {
        path: "/",
        component: Login,
        meta: { title_key: 'pos.login_form.title' },
    },
    {
        path: "/:pathMatch(.*)*",
        component: PageNotFound,
    },
    {
        path: "/home",
        component: MainLayout,
        meta: {
            requiresAuth: true,
            title_key: 'pos.home.title'
        },
        children: [
            {
                path: "/dashboard",
                component: Dashboard,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.dashboard.title'
                },
            },
            {
                path: "/home",
                component: Home,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.home.title'
                },
            },
            {
                path: "/customers",
                component: Customers,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.customers.title'
                },
            },
            {
                path: "/customers/create",
                component: CustomerCreate,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.customers.create.title'
                },
            },
            {
                path: "/customers/edit/:id/:type",
                component: CustomerEdit,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.customers.edit.title'
                },
            },
            {
                path: "/cashier/:tab?",
                component: Cashier,
                props: true,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.cashier.title'
                },
            },
            {
                path: "/orders/:tab?",
                component: Orders,
                props: true,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.orders.title'
                }
            },
            {
                path: "/products/create",
                component: ProductCreate,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.products.create.title'
                },
            },
            {
                path: "/products/edit/:id",
                component: ProductEdit,
                props: true,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.products.edit.title'
                },
            },
            {
                path: "/products/:tab?",
                component: Products,
                props: true,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.products.title'
                },
            },
            {
                path: "/inventory",
                component: Inventory,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.inventory.title'
                },
            },
            {
                path: "/products/list",
                component: ProductListMobile,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.products.list.title'
                },
            },
            {
                path: "/products/inception",
                component: StockInception,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.products.inception.title'
                },
            },
            {
                path: "/reports",
                component: Reports,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.reports.title'
                },
            },
            {
                path: "/settings",
                component: Settings,
                props: true,
                meta: { requiresAuth: true, title_key: 'pos.settings.title' },
            },
            {
                path: "/settings/profile",
                component: Settings,
                props: { tab: 'profile' },
                meta: { requiresAuth: true, title_key: 'pos.settings.profile.title' },
            },
            {
                path: "/settings/sync",
                component: Settings,
                props: { tab: 'sync' },
                meta: { requiresAuth: true, title_key: 'pos.settings.sync.title' },
            },
            {
                path: "/settings/shortcuts",
                component: Settings,
                props: { tab: 'shortcuts' },
                meta: { requiresAuth: true, title_key: 'pos.settings.shortcuts.title' },
            },
            {
                path: "/settings/regional",
                component: Settings,
                props: { tab: 'regional' },
                meta: { requiresAuth: true, title_key: 'pos.settings.regional.title' },
            },
            {
                path: "/settings/hardware",
                component: Settings,
                props: { tab: 'hardware' },
                meta: { requiresAuth: true, title_key: 'pos.settings.hardware.title' },
            },
            {
                path: "/settings/receipt",
                component: Settings,
                props: { tab: 'receipt' },
                meta: { requiresAuth: true, title_key: 'pos.settings.receipt.title' },
            },
            {
                path: "/settings/display",
                component: Settings,
                props: { tab: 'display' },
                meta: { requiresAuth: true, title_key: 'pos.settings.customer_display.title' },
            },
            {
                path: "/settings/training",
                component: Settings,
                props: { tab: 'training' },
                meta: { requiresAuth: true, title_key: 'pos.settings.training.title' },
            },
            {
                path: "/settings/sounds",
                component: Settings,
                props: { tab: 'sounds' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.sounds' },
            },
            {
                path: "/settings/basic",
                component: Settings,
                props: { tab: 'basic' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.basic' },
            },
            {
                path: "/settings/tax",
                component: Settings,
                props: { tab: 'tax' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.tax' },
            },
            {
                path: "/settings/timezones",
                component: Settings,
                props: { tab: 'timezones' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.timezones' },
            },
            {
                path: "/settings/service-charge",
                component: Settings,
                props: { tab: 'service_charge' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.service_charge' },
            },
            {
                path: "/settings/discount",
                component: Settings,
                props: { tab: 'discount' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.discount' },
            },
            {
                path: "/settings/payment-methods",
                component: Settings,
                props: { tab: 'payment' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.payment' },
            },
            {
                path: "/settings/x-z-report",
                component: Settings,
                props: { tab: 'x_z_report' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.x_z_report' },
            },
            {
                path: "/settings/restrictions",
                component: Settings,
                props: { tab: 'restrictions' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.restrictions' },
            },
            {
                path: "/settings/faq",
                component: Settings,
                props: { tab: 'faq' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.faq' },
            },
            {
                path: "/settings/contact",
                component: Settings,
                props: { tab: 'contact' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.contact' },
            },
            {
                path: "/settings/feedback",
                component: Settings,
                props: { tab: 'feedback' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.feedback' },
            },
            {
                path: "/settings/peripherals",
                component: Settings,
                props: { tab: 'peripherals' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.peripherals' },
            },
            {
                path: "/settings/terms",
                component: Settings,
                props: { tab: 'terms' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.terms' },
            },
            {
                path: "/settings/privacy",
                component: Settings,
                props: { tab: 'privacy' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.privacy' },
            },
            {
                path: "/settings/account-deletion",
                component: Settings,
                props: { tab: 'account_deletion' },
                meta: { requiresAuth: true, title_key: 'pos.settings.groups.items.account_deletion' },
            },
            {
                path: "/payment/:id",
                component: Payment,
                meta: {
                    requiresAuth: true,
                    title_key: 'pos.payment.title'
                },
            },
        ],
    },
];

const baseMeta = document.querySelector('meta[name="base-url"]');

const base = baseMeta
    ? baseMeta.getAttribute('content')
    : window.location.origin;

const url = new URL(base);

let basePath = url.pathname;

if (basePath === '/') {
    basePath = '';
}

const router = createRouter({
    history: createWebHistory(`${basePath}/pos/`),
    routes,
});

router.beforeEach((to, from, next) => {
    if (to?.meta?.title_key) {
        document.title = I18n.global.t(to.meta.title_key);
    }

    const accessToken = localStorage.getItem('accessToken');

    if (
        to.matched.some(record => record.meta.requiresAuth)
        && ! accessToken
    ) {
        next('/');
    } else if (
        to.path === '/'
        && accessToken
    ) {
        next('/dashboard');
    } else {
        next();
    }
});

export default router;
