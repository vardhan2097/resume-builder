<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { HttpError, post } from '@/lib/http';
import type { ResumeData } from '@/types/resume';

const props = defineProps<{
    /** Preview endpoint: the resume's own route, or the guest one. */
    url: string;
    template: string;
    color: string;
    photoKey?: string | null;
    content: ResumeData;
}>();

const PAGE_WIDTH = 794;
const PAGE_HEIGHT = 1123;

const html = ref('');
const loading = ref(false);
const notice = ref('');
const scale = ref(1);
const frameHeight = ref(PAGE_HEIGHT);
const wrapper = ref<HTMLElement | null>(null);

let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | null = null;
let observer: ResizeObserver | null = null;

async function load() {
    controller?.abort();
    controller = new AbortController();
    loading.value = true;

    try {
        const response = await post(
            props.url,
            {
                template: props.template,
                color: props.color,
                data: props.content,
            },
            { signal: controller.signal },
        );

        html.value = await response.text();
        notice.value = '';
    } catch (error) {
        if (error instanceof HttpError) {
            // Keep showing the last good preview.
            notice.value =
                error.status === 422
                    ? 'Preview paused: ' + error.message
                    : error.message;
        } else if ((error as Error).name !== 'AbortError') {
            console.error(error);
        }
    } finally {
        loading.value = false;
    }
}

function onFrameLoad(event: Event) {
    const frame = event.target as HTMLIFrameElement;
    const contentHeight =
        frame.contentDocument?.body.scrollHeight ?? PAGE_HEIGHT;
    frameHeight.value = Math.max(PAGE_HEIGHT, contentHeight);
}

watch(
    () => [props.template, props.color, props.content, props.photoKey],
    () => {
        clearTimeout(timer);
        timer = setTimeout(load, 400);
    },
    { deep: true },
);

onMounted(() => {
    load();

    observer = new ResizeObserver(([entry]) => {
        scale.value = Math.min(1, entry.contentRect.width / PAGE_WIDTH);
    });

    if (wrapper.value) {
        observer.observe(wrapper.value);
    }
});

onBeforeUnmount(() => {
    clearTimeout(timer);
    controller?.abort();
    observer?.disconnect();
});
</script>

<template>
    <div
        ref="wrapper"
        class="relative w-full overflow-hidden rounded-xl border bg-white"
    >
        <div
            v-if="loading"
            class="absolute top-2 right-2 z-10 rounded bg-black/60 px-2 py-1 text-xs text-white"
        >
            Updating…
        </div>

        <div
            v-if="notice"
            class="absolute inset-x-2 top-2 z-10 rounded bg-amber-100 px-3 py-2 text-xs text-amber-900"
        >
            {{ notice }}
        </div>

        <div :style="{ height: `${frameHeight * scale}px` }">
            <iframe
                :srcdoc="html"
                title="Resume preview"
                class="origin-top-left border-0"
                :style="{
                    width: `${PAGE_WIDTH}px`,
                    height: `${frameHeight}px`,
                    transform: `scale(${scale})`,
                }"
                @load="onFrameLoad"
            />
        </div>
    </div>
</template>
