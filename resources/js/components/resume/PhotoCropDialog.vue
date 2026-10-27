<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type PhotoCrop = { x: number; y: number; size: number };

const props = defineProps<{
    src: string | null;
    processing?: boolean;
}>();

const emit = defineEmits<{
    confirm: [crop: PhotoCrop];
    cancel: [];
}>();

const open = defineModel<boolean>('open', { required: true });

// Size of the square viewport in CSS pixels.
const FRAME = 280;
const MAX_ZOOM = 4;

const natural = reactive({ width: 0, height: 0 });
const offset = reactive({ x: 0, y: 0 });
const zoom = ref(1);

let drag: { pointerId: number; startX: number; startY: number; originX: number; originY: number } | null = null;

const baseScale = computed(() =>
    natural.width && natural.height ? FRAME / Math.min(natural.width, natural.height) : 1,
);
const scale = computed(() => baseScale.value * zoom.value);

const imageStyle = computed(() => ({
    width: `${natural.width * scale.value}px`,
    height: `${natural.height * scale.value}px`,
    transform: `translate(${offset.x}px, ${offset.y}px)`,
}));

function clamp(value: number, min: number, max: number) {
    return Math.min(max, Math.max(min, value));
}

function clampOffset() {
    offset.x = clamp(offset.x, FRAME - natural.width * scale.value, 0);
    offset.y = clamp(offset.y, FRAME - natural.height * scale.value, 0);
}

function onLoad(event: Event) {
    const img = event.target as HTMLImageElement;

    natural.width = img.naturalWidth;
    natural.height = img.naturalHeight;
    zoom.value = 1;

    // Centre horizontally; for portraits start near the top so faces aren't cut off.
    offset.x = (FRAME - natural.width * scale.value) / 2;
    offset.y = natural.height > natural.width ? (FRAME - natural.height * scale.value) * 0.2 : (FRAME - natural.height * scale.value) / 2;
    clampOffset();
}

function setZoom(next: number) {
    // Zoom around the frame centre so the subject stays put.
    const centreX = (FRAME / 2 - offset.x) / scale.value;
    const centreY = (FRAME / 2 - offset.y) / scale.value;

    zoom.value = clamp(next, 1, MAX_ZOOM);
    offset.x = FRAME / 2 - centreX * scale.value;
    offset.y = FRAME / 2 - centreY * scale.value;
    clampOffset();
}

function onPointerDown(event: PointerEvent) {
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
    drag = { pointerId: event.pointerId, startX: event.clientX, startY: event.clientY, originX: offset.x, originY: offset.y };
}

function onPointerMove(event: PointerEvent) {
    if (!drag || drag.pointerId !== event.pointerId) {
        return;
    }

    offset.x = drag.originX + event.clientX - drag.startX;
    offset.y = drag.originY + event.clientY - drag.startY;
    clampOffset();
}

function onPointerUp(event: PointerEvent) {
    if (drag?.pointerId === event.pointerId) {
        drag = null;
    }
}

function onWheel(event: WheelEvent) {
    setZoom(zoom.value * (event.deltaY < 0 ? 1.1 : 1 / 1.1));
}

function confirm() {
    if (!natural.width) {
        return;
    }

    emit('confirm', {
        x: clamp(-offset.x / scale.value / natural.width, 0, 1),
        y: clamp(-offset.y / scale.value / natural.height, 0, 1),
        size: clamp(FRAME / scale.value / natural.width, 0, 1),
    });
}

function onOpenChange(value: boolean) {
    open.value = value;

    if (!value) {
        emit('cancel');
    }
}

watch(
    () => props.src,
    () => {
        natural.width = 0;
        natural.height = 0;
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Frame your photo</DialogTitle>
                <DialogDescription>Drag to reposition, use the slider or scroll to zoom.</DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-4">
                <div
                    class="relative cursor-grab touch-none overflow-hidden rounded-lg bg-muted select-none active:cursor-grabbing"
                    :style="{ width: `${FRAME}px`, height: `${FRAME}px` }"
                    @pointerdown="onPointerDown"
                    @pointermove="onPointerMove"
                    @pointerup="onPointerUp"
                    @pointercancel="onPointerUp"
                    @wheel.prevent="onWheel"
                >
                    <img
                        v-if="src"
                        :src="src"
                        alt=""
                        draggable="false"
                        class="pointer-events-none absolute top-0 left-0 max-w-none origin-top-left"
                        :style="imageStyle"
                        @load="onLoad"
                    />
                    <div class="pointer-events-none absolute inset-0 rounded-full shadow-[0_0_0_9999px_rgba(0,0,0,0.45)] ring-2 ring-white/80" />
                </div>

                <label class="flex w-full items-center gap-3 text-sm">
                    <span class="text-muted-foreground">Zoom</span>
                    <input
                        type="range"
                        class="flex-1"
                        min="1"
                        :max="MAX_ZOOM"
                        step="0.01"
                        :value="zoom"
                        @input="setZoom(Number(($event.target as HTMLInputElement).value))"
                    />
                </label>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="onOpenChange(false)">Cancel</Button>
                <Button type="button" :disabled="processing || !natural.width" @click="confirm">
                    {{ processing ? 'Saving…' : 'Save photo' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
