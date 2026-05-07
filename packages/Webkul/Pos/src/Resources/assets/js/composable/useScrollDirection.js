import { ref, onMounted, onBeforeUnmount } from 'vue';

/**
 * Tracks scroll direction with a minimum delta threshold.
 * Returns `isScrollingDown` ref (true = user scrolling down, false = scrolling up).
 */
export function useScrollDirection(threshold = 10) {
    const isScrollingDown = ref(false);

    let lastScrollY = 0;
    let ticking = false;

    function onScroll() {
        if (ticking) return;

        ticking = true;

        requestAnimationFrame(() => {
            const currentY = window.scrollY;
            const delta = currentY - lastScrollY;

            if (Math.abs(delta) >= threshold) {
                isScrollingDown.value = delta > 0;
                lastScrollY = currentY;
            }

            ticking = false;
        });
    }

    onMounted(() => {
        lastScrollY = window.scrollY;
        window.addEventListener('scroll', onScroll, { passive: true });
    });

    onBeforeUnmount(() => {
        window.removeEventListener('scroll', onScroll);
    });

    return { isScrollingDown };
}
