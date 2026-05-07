<template></template>

<script setup>
    import { inject, onMounted, onBeforeUnmount } from 'vue';
    import { useRouter } from 'vue-router';

    /**
     * General variables
     */
    const router = useRouter();
    const emitter = inject('emitter');

    /**
     * Keyboard Shortcuts
     */
    const menuShortcutMap = {
      '1': '/home',
      '2': '/customers',
      '3': '/cashier',
      '4': '/orders',
      '5': '/products',
      '6': '/reports',
      '7': '/settings',
    };

    /**
     * Handle keyboard shortcuts
     */
    const handle = (e) => {        
        const isHome = router.currentRoute.value.path === '/home';
        const isCustomers = router.currentRoute.value.path === '/customers'; 

        if (e.altKey && menuShortcutMap[e.key]) {
            e.preventDefault();

            router.push({ path: menuShortcutMap[e.key] });
        } else if (e.ctrlKey && !e.shiftKey) {
            switch (e.key) {
                case 'd':
                    e.preventDefault();

                    emitter.emit('toggle_dark_mode');

                    break;
                case 'e':
                    if (isCustomers) {
                        e.preventDefault();
                        
                        emitter.emit('edit_customer');
                    }

                    break;
                case 'h':
                    e.preventDefault();

                    router.push({ path: '/orders/hold' });

                    break;
                case 'b':
                    if (isHome) {
                        e.preventDefault();

                        emitter.emit('open_barcode_model')
                    }

                    break;
                case 'n':
                    if (isHome) {
                        e.preventDefault();

                        emitter.emit('open_product_create_model');
                    }

                    break;
                case 'Enter':
                    if (isHome) {
                        e.preventDefault();
                        
                        emitter.emit('checkout');
                    }

                    break;
                case 'Backspace':
                    if (
                        e.ctrlKey
                        && isHome
                    ) {
                        e.preventDefault();
                        
                        emitter.emit('remove_note');
                    }

                    break;
            }

        } else if (e.ctrlKey && e.shiftKey) {
            switch (e.key.toLowerCase()) {
                case 'c':
                    e.preventDefault();

                    router.push({ path: '/customers/create' });

                    break;
                case 'u':
                    e.preventDefault();
                    
                    emitter.emit('change_customer');

                    break;
                case 'n':
                    if (isHome) {
                        e.preventDefault();                        

                        emitter.emit('add_note');
                    }

                    break;
                case 'h':
                    if (isHome) {
                        e.preventDefault();
                        
                        emitter.emit('hold_order');
                    }

                    break;
            }
        } else if (
            e.key === 'Delete'
            && isCustomers
        ) {
            e.preventDefault();
            
            emitter.emit('delete_customer');
        }
    };

    /**
     * Register keyboard event listeners
     */
    onMounted(() => {
        window.addEventListener('keydown', handle);
    });

    /**
     * Unregister keyboard event listeners
     */
    onBeforeUnmount(() => {
        window.removeEventListener('keydown', handle);
    });
</script>