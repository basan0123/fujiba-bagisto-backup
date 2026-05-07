/**
 * This plugin provides a reactive property to track network status
 */
import { ref } from 'vue';
import I18n from '@src/plugins/i18n';

export default {
    install(app) {
        /**
         * Reactive property to track network status
         */
        const isOnline = ref(navigator.onLine);

        const emitter = app._context.provides.emitter;

        const t = I18n.global.t;

        /**
         * Update network status
         */
        const updateNetworkStatus = () => {
            isOnline.value = navigator.onLine;

            emitter && emitter.emit('add_flash', {
                type: isOnline.value
                    ? 'success'
                    : 'warning',
                message: navigator.onLine
                    ? t('pos.common.flash_messages.online_mode')
                    : t('pos.common.flash_messages.offline_mode'),
            });
        }

        /**
         * Listen for network status changes
         * and update the reactive property accordingly
         */
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);

        /**
         * Add the reactive property to the app instance
         * so that it can be accessed globally
         */
        app.provide('isOnline', isOnline);
    }
};
