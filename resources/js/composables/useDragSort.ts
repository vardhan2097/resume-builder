import { nextTick, onBeforeUnmount, ref } from 'vue';
import type { Ref } from 'vue';

/**
 * Reorder a list by dragging a handle (mouse or touch, via pointer events) or
 * with the arrow keys while the handle is focused.
 *
 * Each item element needs `data-sort-index="<index>"` and each handle
 * `data-sort-handle`; both must live inside `container`.
 */
export function useDragSort<T>(
    items: Ref<T[]>,
    container: Ref<HTMLElement | null>,
) {
    const dragging = ref<number | null>(null);

    function moveItem(from: number, to: number) {
        const [item] = items.value.splice(from, 1);
        items.value.splice(to, 0, item);
    }

    function onPointerMove(event: PointerEvent) {
        if (dragging.value === null) {
            return;
        }

        const target = document
            .elementFromPoint(event.clientX, event.clientY)
            ?.closest<HTMLElement>('[data-sort-index]');

        if (!target || !container.value?.contains(target)) {
            return;
        }

        const index = Number(target.dataset.sortIndex);

        if (!Number.isNaN(index) && index !== dragging.value) {
            moveItem(dragging.value, index);
            dragging.value = index;
        }
    }

    function stop() {
        dragging.value = null;
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', stop);
        window.removeEventListener('pointercancel', stop);
    }

    // Listen on window rather than using pointer capture: reordering moves the
    // dragged element in the DOM, which would release the capture mid-drag.
    function onPointerDown(index: number, event: PointerEvent) {
        event.preventDefault();
        dragging.value = index;
        window.addEventListener('pointermove', onPointerMove);
        window.addEventListener('pointerup', stop);
        window.addEventListener('pointercancel', stop);
    }

    async function onKeydown(index: number, event: KeyboardEvent) {
        const step = {
            ArrowLeft: -1,
            ArrowUp: -1,
            ArrowRight: 1,
            ArrowDown: 1,
        }[event.key];
        const target = index + (step ?? 0);

        if (!step || target < 0 || target >= items.value.length) {
            return;
        }

        event.preventDefault();
        moveItem(index, target);

        // Moving the element in the DOM drops focus; put it back on the moved handle.
        await nextTick();
        container.value
            ?.querySelectorAll<HTMLElement>('[data-sort-handle]')
            [target]?.focus();
    }

    onBeforeUnmount(stop);

    return { dragging, onPointerDown, onKeydown };
}
