<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { preview } from '@/actions/App/Http/Controllers/GuestResumeController';
import { Button } from '@/components/ui/button';
import PreviewLoading from '@/components/resume/PreviewLoading.vue';
import { post } from '@/lib/http';
import { sampleResume } from '@/lib/sampleResume';
import type { ResumeData, TemplateMeta } from '@/types/resume';

const props = defineProps<{
    templates: Record<string, TemplateMeta>;
    defaults: ResumeData;
}>();
const emit = defineEmits<{ select: [template: string] }>();
const previews = ref<Record<string, string>>({});
const failed = ref<string[]>([]);
const selectedTemplate = ref<string | null>(null);
const previewContainer = ref<HTMLElement | null>(null);
const previewWidth = ref(0);
const zoom = ref(1);
const scale = computed(
    () => Math.min(previewWidth.value / 794, 1) * zoom.value,
);
let resizeObserver: ResizeObserver | undefined;

watch(previewContainer, (element) => {
    resizeObserver?.disconnect();
    if (!element) return;

    resizeObserver = new ResizeObserver(([entry]) => {
        previewWidth.value = entry.contentRect.width;
    });
    resizeObserver.observe(element);
});

onUnmounted(() => resizeObserver?.disconnect());

function viewTemplate(template: string) {
    zoom.value = 1;
    selectedTemplate.value = template;
}

const sample = sampleResume(props.defaults);

onMounted(async () => {
    await Promise.all(
        Object.entries(props.templates).map(async ([key, meta]) => {
            try {
                const response = await post(preview().url, {
                    template: key,
                    color: meta.palettes[0] ?? '#0f766e',
                    data: sample,
                });
                previews.value[key] = await response.text();
            } catch {
                failed.value.push(key);
            }
        }),
    );
});
</script>

<template>
    <section class="mx-auto w-full max-w-7xl px-4 py-8">
        <template v-if="selectedTemplate">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <Button
                        type="button"
                        variant="ghost"
                        class="-ml-3"
                        @click="selectedTemplate = null"
                        >← Back to templates</Button
                    >
                    <h1 class="mt-3 text-2xl font-semibold sm:text-3xl">
                        {{ templates[selectedTemplate].name }}
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Sample resume
                    </p>
                </div>
                <Button type="button" @click="emit('select', selectedTemplate)"
                    >Use this template</Button
                >
            </div>
            <div class="mt-6 rounded-xl border bg-slate-100 p-3 sm:p-6">
                <div
                    class="mb-4 flex flex-wrap items-center justify-center gap-2"
                    aria-label="Preview zoom controls"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="zoom <= 1"
                        @click="zoom = Math.max(1, zoom - 0.5)"
                        >Zoom out</Button
                    >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="zoom = 1"
                        >Fit to screen</Button
                    >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="zoom >= 2"
                        @click="zoom = Math.min(2, zoom + 0.5)"
                        >Zoom in</Button
                    >
                </div>
                <div class="overflow-x-auto">
                    <div ref="previewContainer" class="w-full">
                        <div
                            v-if="previews[selectedTemplate] && previewWidth"
                            class="mx-auto"
                            :style="{
                                width: `${794 * scale}px`,
                                height: `${1600 * scale}px`,
                            }"
                        >
                            <iframe
                                :srcdoc="previews[selectedTemplate]"
                                sandbox=""
                                :title="`${templates[selectedTemplate].name} sample resume`"
                                class="block h-[1600px] w-[794px] origin-top-left border-0 bg-white shadow-sm"
                                :style="{ transform: `scale(${scale})` }"
                            />
                        </div>
                        <p
                            v-else-if="failed.includes(selectedTemplate)"
                            class="p-6 text-center text-sm text-muted-foreground"
                        >
                            Preview unavailable. Refresh the page to try again.
                        </p>
                        <PreviewLoading
                            v-else-if="!previews[selectedTemplate]"
                        />
                    </div>
                </div>
            </div>
        </template>
        <template v-else>
            <h1 class="text-2xl font-semibold sm:text-3xl">
                Choose a resume template
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Explore each design using sample information, then choose one to
                start your resume.
            </p>
            <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="(meta, key) in templates"
                    :key="key"
                    class="overflow-hidden rounded-xl border bg-card"
                >
                    <button
                        type="button"
                        class="block w-full text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        :aria-label="`View ${meta.name} template details`"
                        @click="viewTemplate(key)"
                    >
                        <div
                            class="relative h-[400px] overflow-hidden bg-slate-100"
                            aria-hidden="true"
                        >
                            <iframe
                                v-if="previews[key]"
                                :srcdoc="previews[key]"
                                sandbox=""
                                tabindex="-1"
                                :title="`${meta.name} sample resume`"
                                class="pointer-events-none absolute top-0 left-1/2 h-[1100px] w-[794px] origin-top -translate-x-1/2 scale-[0.36] border-0 bg-white"
                            />
                            <p
                                v-else-if="failed.includes(key)"
                                class="p-6 text-center text-sm text-muted-foreground"
                            >
                                Preview unavailable
                            </p>
                            <PreviewLoading v-else class="h-full" />
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 p-4"
                        >
                            <h2 class="font-semibold">{{ meta.name }}</h2>
                            <span class="text-sm font-medium text-primary"
                                >View template →</span
                            >
                        </div>
                    </button>
                </article>
            </div>
        </template>
    </section>
</template>
