import { ref } from 'vue';

const isLoading = ref(false);

/**
 * A composable function to manage a global loading state.
 * It provides a reactive property `isLoading` and methods to show and hide the loader.
 */
export default function useGlobalLoader() {
    return {
        isLoading,
        showLoader: () => (isLoading.value = true),
        hideLoader: () => (isLoading.value = false),
    };
}
