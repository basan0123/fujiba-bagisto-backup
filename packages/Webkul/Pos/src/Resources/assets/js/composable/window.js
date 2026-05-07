import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

/**
 * Use window width composable
 */
export function useWindowWidth() {
    const windowWidth = ref(window.innerWidth);

    /**
     * Update window width
     */
    const updateWindowWidth = () => {
        windowWidth.value = window.innerWidth;
    };

    /**
     * Check if the device is mobile or tab
     */
    const isMobileOrTab = computed(() => windowWidth.value <= 1024);

    /**
     * Add event listener to update window width
     */
    onMounted(() => {
        window.addEventListener('resize', updateWindowWidth);
    });

    /**
     * Remove event listener on component unmount
     * to avoid memory leaks
     */
    onBeforeUnmount(() => {
        window.removeEventListener('resize', updateWindowWidth);
    });

    return {
        isMobileOrTab,
        windowWidth
    };
}
