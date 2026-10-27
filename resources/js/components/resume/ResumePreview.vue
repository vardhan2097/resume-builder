<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { preview } from '@/routes/resumes';
import type { ResumeData } from '@/types/resume';

const props = defineProps<{
    resumeId: number;
    template: string;
    color: string;
    photoKey?: string | null;
    content: ResumeData;
}>();

const PAGE_WIDTH = 794;
const PAGE_HEIGHT = 1123;

const html = ref('');
const loading = ref(false);
const scale = ref(1);
const frameHeight = ref(PAGE_HEIGHT);
const wrapper = ref<HTMLElement | null>(null);

let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | null = null;
let observer: ResizeObserver | null = null;

function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function load() {
    controller?.abort();
    controller = new AbortController();
    loading.value = true;

    try {
        const response = await fetch(preview(props.resumeId).url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'text/html',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                template: props.template,
                color: props.color,
                data: props.content,
            }),
            signal: controller.signal,
        });

        if (response.ok) {
            html.value = await response.text();
        }
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
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
