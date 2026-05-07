import { createApp, h } from 'vue';

/**
 * Import the main App component
 */
import App from '@src/App.vue';
import Router from '@src/router';

/**
 * Import the plugins
 */
import I18n from '@src/plugins/i18n';
import Emitter from '@src/plugins/emitter';
import Network from '@src/plugins/network';
import flatpickr from '@src/plugins/flatpickr';
import VeeValidate from '@src/plugins/vee-validate';
import apolloClient from '@src/plugins/apollo-client';

/**
 * Import the global components
 */
import Button from '@components/secured/common/Button.vue';
import Cart from '@components/secured/common/Cart.vue';
import Drawer from '@components/secured/common/Drawer.vue';
import Modal from '@components/secured/common/Modal.vue';
import ControlGroup from '@components/shared/control-group/Main.vue';
import Label from '@components/shared/control-group/Label.vue';
import Field from '@components/shared/control-group/Field.vue';
import Error from '@components/shared/control-group/Error.vue';
import Tab from '@components/secured/common/Tab.vue';
import Pagination from '@components/secured/common/Pagination.vue';

/**
 * Create the app
 */
const app = createApp({
    render: () => h(App),
});

/**
 * Register the plugins
 */
[
    Emitter,
    Router,
    I18n,
    Network,
    flatpickr,
    VeeValidate,
    apolloClient,
].forEach(plugin => app.use(plugin));

/**
 * Register the global components
 */
app.component('ControlGroup', ControlGroup)
    .component('Label', Label)
    .component('Field', Field)
    .component('Error', Error)
    .component('Button', Button)
    .component('Cart', Cart)
    .component('Drawer', Drawer)
    .component('Modal', Modal)
    .component('Tab', Tab)
    .component('Pagination', Pagination);

/**
 * Mount the app
 */
app.mount('#app');

// FUJIBA 2026-05-06: pagehide beacon-revoke removed. Reloading or closing the
// cashier tab no longer kills the customer-display URL — the display stays
// connected and the same /pos/display/{token} URL stays valid forever
// (revocation is now manual-only via Settings → Customer Display).

