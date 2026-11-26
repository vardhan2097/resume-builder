<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    getDocument,
    GlobalWorkerOptions,
    type PDFDocumentProxy,
    type RenderTask,
} from 'pdfjs-dist/legacy/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url';

import { HttpError, post } from '@/lib/http';
import { resumeWithExamples } from '@/lib/sampleResume';
import type { ResumeData } from '@/types/resume';

GlobalWorkerOptions.workerSrc = workerUrl;

const props = defineProps<{
    url: string;
    template: string;
    color: string;
    photoKey?: string | null;
    content: ResumeData;
}>();

const preview = ref<HTMLElement | null>(null);
const hasPreview = ref(false);
let pdf: PDFDocumentProxy | null = null;
let renderTask: RenderTask | null = null;
let renderRevision = 0;
let observer: ResizeObserver | null = null;
let disposed = false;

async function renderPages() {
    const container = preview.value;
    const document = pdf;
    if (!container || !document || !container.clientWidth) return;
    const current = ++renderRevision;
    renderTask?.cancel();
    try {
        for (let number = 1; number <= document.numPages; number++) {
            const page = await document.getPage(number);
            if (current !== renderRevision) return;
            const base = page.getViewport({ scale: 1 });
            const scale = (container.clientWidth / base.width) * zoom.value;
            const viewport = page.getViewport({ scale });
            const ratio = Math.min(window.devicePixelRatio || 1, 2);
            const canvas = window.document.createElement('canvas');
            canvas.width = Math.floor(viewport.width * ratio);
            canvas.height = Math.floor(viewport.height * ratio);
            canvas.style.width = `${Math.floor(viewport.width)}px`;
            canvas.style.height = `${Math.floor(viewport.height)}px`;
            canvas.style.margin = '0 auto 12px';
            canvas.setAttribute('aria-label', `Resume preview page ${number}`);
            const context = canvas.getContext('2d');
            if (!context) throw new Error('Canvas unavailable');
            renderTask = page.render({
                canvasContext: context,
                viewport,
                transform: [ratio, 0, 0, ratio, 0, 0],
            });
            await renderTask.promise;
            if (current !== renderRevision) return;
            if (number === 1) {
                container.replaceChildren(canvas);
                hasPreview.value = true;
                notice.value = '';
            } else {
                container.appendChild(canvas);
            }
        }
    } catch (error) {
        if (current !== renderRevision) return;
        notice.value = 'Could not render the PDF preview. Please try again.';
    }
}
const pages = ref(0);
const loading = ref(false);
const notice = ref('');
const zoom = ref(1);
let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | null = null;
let revision = 0;
let inFlight = false;
let pending = false;

async function load() {
    if (inFlight) {
        pending = true;
        return;
    }
    inFlight = true;
    pending = false;
    const request = new AbortController();
    controller = request;
    const current = ++revision;
    loading.value = true;

    try {
        const response = await post(
            props.url,
            {
                template: props.template,
                color: props.color,
                data: resumeWithExamples(props.content),
                format: 'pdf',
            },
            { signal: request.signal },
        );
        const data = await response.arrayBuffer();
        if (request.signal.aborted || current !== revision) return;

        const next = await getDocument({ data }).promise;
        if (request.signal.aborted || current !== revision || disposed) {
            await next.destroy();
            return;
        }
        const previous = pdf;
        ++renderRevision;
        renderTask?.cancel();
        pdf = next;
        pages.value = next.numPages;
        await renderPages();
        await previous?.destroy();
    } catch (error) {
        if (request.signal.aborted || current !== revision) return;
        notice.value =
            error instanceof HttpError
                ? error.message
                : 'Could not update the PDF preview. Please try again.';
    } finally {
        inFlight = false;
        if (pending && !disposed) {
            void load();
        } else if (current === revision) {
            loading.value = false;
        }
    }
}

watch(
    () => [props.template, props.color, props.content, props.photoKey],
    () => {
        ++revision;
        clearTimeout(timer);
        timer = setTimeout(load, 400);
    },
    { deep: true },
);
watch(zoom, renderPages);
onMounted(() => {
    observer = new ResizeObserver(() => {
        void renderPages();
    });
    if (preview.value) observer.observe(preview.value);
    void load();
});
onBeforeUnmount(() => {
    clearTimeout(timer);
    controller?.abort();
    disposed = true;
    ++revision;
    ++renderRevision;
    renderTask?.cancel();
    observer?.disconnect();
    void pdf?.destroy();
    preview.value?.replaceChildren();
});
</script>

<template>
    <section
        class="flex h-full min-h-0 w-full flex-col overflow-hidden rounded-xl border bg-muted"
    >
        <div
            class="flex shrink-0 items-center justify-between gap-3 border-b bg-background px-3 py-2 text-sm"
            aria-live="polite"
        >
            <span class="font-medium"
                >PDF preview<span v-if="pages">
                    · {{ pages }} {{ pages === 1 ? 'page' : 'pages' }}</span
                ></span
            >
            <button
                type="button"
                class="text-xs underline"
                :disabled="loading"
                @click="load"
            >
                {{ loading ? 'Updating…' : 'Refresh' }}
            </button>
        </div>
        <p class="shrink-0 px-3 py-2 text-xs text-muted-foreground">
            Empty fields show examples in this preview. Downloads contain your
            information only. Use “Start on new page” to adjust page breaks.
        </p>
        <div
            class="flex shrink-0 items-center justify-end gap-2 px-3 pb-2 text-sm"
            aria-label="Preview zoom controls"
        >
            <button
                type="button"
                class="rounded border px-3 py-1 disabled:opacity-40"
                aria-label="Zoom out"
                :disabled="zoom <= 1"
                @click="zoom = Math.max(1, zoom - 0.5)"
            >
                −
            </button>
            <button
                type="button"
                class="rounded border px-3 py-1"
                @click="zoom = 1"
            >
                Fit width
            </button>
            <button
                type="button"
                class="rounded border px-3 py-1 disabled:opacity-40"
                aria-label="Zoom in"
                :disabled="zoom >= 2"
                @click="zoom = Math.min(2, zoom + 0.5)"
            >
                +
            </button>
        </div>
        <p
            v-if="notice"
            class="shrink-0 bg-amber-100 px-3 py-2 text-sm text-amber-900"
        >
            {{ notice }}
        </p>
        <div
            ref="preview"
            class="min-h-0 flex-1 overflow-auto"
            aria-label="Resume preview"
        />
        <p
            v-if="!hasPreview"
            class="p-6 text-center text-sm text-muted-foreground"
            role="status"
        >
            {{
                loading ? 'Preparing PDF preview…' : 'PDF preview unavailable.'
            }}
        </p>
    </section>
</template>
