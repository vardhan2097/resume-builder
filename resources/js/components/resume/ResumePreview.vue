<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { HttpError, post } from '@/lib/http';
import type { ResumeData } from '@/types/resume';

const props = defineProps<{
    url: string;
    template: string;
    color: string;
    photoKey?: string | null;
    content: ResumeData;
}>();

const pdfUrl = ref('');
const pages = ref(0);
const loading = ref(false);
const notice = ref('');
let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | null = null;
let revision = 0;

async function load() {
    controller?.abort();
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
                data: props.content,
                format: 'pdf',
            },
            { signal: request.signal },
        );
        const blob = await response.blob();
        if (request.signal.aborted || current !== revision) return;

        const previous = pdfUrl.value;
        pdfUrl.value = URL.createObjectURL(blob);
        pages.value = Number(response.headers.get('X-PDF-Pages') ?? 0);
        notice.value = '';
        if (previous) URL.revokeObjectURL(previous);
    } catch (error) {
        if (request.signal.aborted || current !== revision) return;
        notice.value =
            error instanceof HttpError
                ? error.message
                : 'Could not update the PDF preview. Please try again.';
    } finally {
        if (current === revision) loading.value = false;
    }
}

watch(
    () => [props.template, props.color, props.content, props.photoKey],
    () => {
        controller?.abort();
        clearTimeout(timer);
        timer = setTimeout(load, 1000);
    },
    { deep: true },
);
onMounted(load);
onBeforeUnmount(() => {
    clearTimeout(timer);
    controller?.abort();
    if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value);
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
            This is the actual PDF layout. Use “Start on new page” in the form
            to adjust page breaks.
        </p>
        <p
            v-if="notice"
            class="shrink-0 bg-amber-100 px-3 py-2 text-sm text-amber-900"
        >
            {{ notice }}
        </p>
        <iframe
            v-if="pdfUrl"
            :src="`${pdfUrl}#view=FitH&navpanes=0`"
            title="PDF preview with pages and print controls"
            class="min-h-0 w-full flex-1 border-0"
        />
        <p v-else class="p-6 text-center text-sm text-muted-foreground">
            {{
                loading ? 'Preparing PDF preview…' : 'PDF preview unavailable.'
            }}
        </p>
    </section>
</template>
