import { router } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Ask before leaving the page while there are unsaved changes: both browser
 * navigation (closing the tab, reload, typing a URL) and Inertia visits
 * (sidebar links, logout).
 */
export function useUnsavedChangesGuard(
    isDirty: () => boolean,
    message = 'You have unsaved changes. Leave without saving?',
) {
    // Browsers show their own generic text here; a custom message is ignored.
    useEventListener(window, 'beforeunload', (event: BeforeUnloadEvent) => {
        if (isDirty()) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    let stop: (() => void) | undefined;

    onMounted(() => {
        stop = router.on('before', (event) => {
            // Saves and photo upload/delete keep the page (preserveState: true);
            // anything else replaces it and would drop the edits.
            if (event.detail.visit.preserveState === true || !isDirty()) {
                return;
            }

            if (!window.confirm(message)) {
                return false;
            }
        });
    });

    onBeforeUnmount(() => stop?.());
}
