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
     * Check if the device is mobile or tab.
     *
     * Only true phones (<768px) get the slide-up cart drawer. Every iPad
     * (≥768px, incl. iPad Air portrait at 820px) shows the inline side
     * cart — same layout as desktop, just narrower. Kept in sync with the
     * `.padding-right` media query in css/shop.css (also @768px) so the
     * grid reserves the exact space the fixed cart occupies.
     */
    const isMobileOrTab = computed(() => windowWidth.value < 768);

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
