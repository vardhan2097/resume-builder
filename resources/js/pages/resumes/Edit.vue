<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useEventListener, watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import { FileText } from '@lucide/vue';
import ContentSections from '@/components/resume/ContentSections.vue';
import DesignSection from '@/components/resume/DesignSection.vue';
import SectionOrder from '@/components/resume/SectionOrder.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { docx, index, pdf, preview, update } from '@/routes/resumes';
import ResumePreview from '@/components/resume/ResumePreview.vue';
import TemplateGallery from '@/components/resume/TemplateGallery.vue';
import type { Resume, TemplateMeta } from '@/types/resume';
import PhotoSection from '@/components/resume/PhotoSection.vue';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';

const props = defineProps<{
    resume: Resume;
    templates: Record<string, TemplateMeta>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My Resumes', href: index() }],
    },
});

const form = useForm({
    title: props.resume.title,
    template: props.resume.template,
    color: props.resume.color,
    content: props.resume.data,
});

// Autosave this long after the last change.
const AUTOSAVE_DELAY = 2000;

const lastSavedAt = ref<Date | null>(null);
const saveFailed = ref(false);
const comparingTemplates = ref(false);
const mobilePreviewOpen = ref(false);

function selectTemplate(template: string) {
    form.template = template;
    comparingTemplates.value = false;
}

// A save requested while another is in flight runs once that one finishes.
let queued = false;
let afterSave: (() => void) | undefined;

function save(onSaved?: () => void) {
    if (onSaved) {
        afterSave = onSaved;
    }

    if (form.processing) {
        queued = true;

        return;
    }

    // What is actually being sent. Typing that happens during the request is
    // compared against this, so it still counts as unsaved afterwards.
    const sent = JSON.parse(JSON.stringify(form.data())) as ReturnType<
        typeof form.data
    >;

    form.transform(({ content, ...rest }) => ({ ...rest, data: content })).put(
        update(props.resume.id).url,
        {
            preserveScroll: true,
            // Keep this component (and anything typed meanwhile) instead of
            // re-creating it from the server's props.
            preserveState: true,
            onSuccess: () => {
                form.defaults(sent);
                lastSavedAt.value = new Date();
                saveFailed.value = false;

                const callback = afterSave;
                afterSave = undefined;
                callback?.();
            },
            onError: () => {
                saveFailed.value = true;
                afterSave = undefined;
            },
            onFinish: () => {
                if (queued) {
                    queued = false;

                    if (form.isDirty) {
                        save();
                    }
                }
            },
        },
    );
}

watchDebounced(
    () => form.data(),
    () => {
        if (form.isDirty) {
            save();
        }
    },
    { debounce: AUTOSAVE_DELAY, deep: true },
);

useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if (event.key === 'Escape') mobilePreviewOpen.value = false;
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        save();
    }
});

useUnsavedChangesGuard(() => form.isDirty);

const savedTime = computed(() =>
    lastSavedAt.value?.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    }),
);

function download(kind: 'pdf' | 'docx') {
    const url =
        kind === 'pdf' ? pdf(props.resume.id).url : docx(props.resume.id).url;

    if (!form.isDirty && !form.processing) {
        window.location.href = url;

        return;
    }

    save(() => {
        window.location.href = url;
    });
}
</script>

<template>
    <Head :title="form.title || 'Edit resume'" />

    <TemplateGallery
        v-if="comparingTemplates"
        :templates="templates"
        :defaults="form.content"
        :content="form.content"
        :color="form.color"
        :preview-url="preview(resume.id).url"
        can-cancel
        @cancel="comparingTemplates = false"
        @select="selectTemplate"
    />
    <div
        v-else
        class="grid gap-6 p-4 lg:h-[calc(100dvh-5rem)] lg:min-h-0 lg:grid-cols-2 lg:overflow-hidden"
    >
        <form
            class="flex flex-col gap-6 lg:min-h-0 lg:overflow-y-auto lg:overscroll-contain lg:pr-2"
            @submit.prevent="save()"
        >
            <div
                class="sticky top-0 z-10 flex flex-wrap items-center gap-3 rounded-xl border bg-background p-3"
            >
                <Input
                    v-model="form.title"
                    class="min-w-40 flex-1 font-medium"
                />
                <span
                    class="text-sm whitespace-nowrap"
                    :class="{
                        'text-muted-foreground': form.processing,
                        'text-red-600': !form.processing && saveFailed,
                        'text-amber-600':
                            !form.processing && !saveFailed && form.isDirty,
                        'text-green-600':
                            !form.processing && !saveFailed && !form.isDirty,
                    }"
                    aria-live="polite"
                >
                    <template v-if="form.processing">Saving…</template>
                    <template v-else-if="saveFailed"
                        >Changes not saved</template
                    >
                    <template v-else-if="form.isDirty"
                        >Unsaved changes</template
                    >
                    <template v-else-if="savedTime"
                        >Saved {{ savedTime }} ✓</template
                    >
                    <template v-else>All changes saved</template>
                </span>
                <Button
                    type="submit"
                    :disabled="form.processing || !form.isDirty"
                    title="Save (Ctrl+S)"
                >
                    Save
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="download('pdf')"
                >
                    Download PDF
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="download('docx')"
                >
                    Download Word
                </Button>
            </div>

            <div
                v-if="Object.keys(form.errors).length"
                class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700"
            >
                <p class="mb-1 font-medium">
                    Your changes aren't saved yet. Please fix these:
                </p>
                <ul class="list-disc pl-5">
                    <li v-for="(message, field) in form.errors" :key="field">
                        {{ message }}
                    </li>
                </ul>
            </div>

            <DesignSection
                v-model:template="form.template"
                v-model:color="form.color"
                :templates="templates"
                @change-template="comparingTemplates = true"
            />

            <SectionOrder
                v-model="form.content.settings.section_order"
                v-model:page-breaks="form.content.settings.page_break_before"
            />

            <PhotoSection
                v-if="templates[form.template]?.photo"
                :resume-id="resume.id"
                :photo-url="resume.photo_url"
                :photo-original-url="resume.photo_original_url"
                :initials="
                    (form.content.personal.full_name || 'CV')
                        .split(' ')
                        .map((w) => w[0])
                        .slice(0, 2)
                        .join('')
                        .toUpperCase()
                "
            />

            <ContentSections v-model="form.content" />
            <div
                class="sticky bottom-4 z-30 self-end rounded-full border bg-background p-1 shadow-lg lg:hidden"
            >
                <Button
                    type="button"
                    class="rounded-full px-5"
                    :aria-expanded="mobilePreviewOpen"
                    aria-controls="mobile-resume-preview"
                    @click="mobilePreviewOpen = true"
                >
                    <FileText class="h-4 w-4" aria-hidden="true" /> Preview
                    resume
                </Button>
            </div>
        </form>

        <div
            id="mobile-resume-preview"
            :class="
                mobilePreviewOpen
                    ? 'visible scale-100 opacity-100'
                    : 'pointer-events-none invisible scale-[0.12] opacity-0 lg:pointer-events-auto lg:visible lg:scale-100 lg:opacity-100'
            "
            class="fixed inset-0 z-50 flex origin-bottom-right flex-col gap-3 bg-background p-4 motion-safe:transition-[scale,opacity,visibility] motion-safe:duration-300 motion-safe:ease-out lg:static lg:z-auto lg:block lg:h-auto lg:min-h-0 lg:overflow-y-auto lg:overscroll-contain lg:bg-transparent lg:p-0 lg:transition-none"
            tabindex="0"
            :role="mobilePreviewOpen ? 'dialog' : 'region'"
            :aria-modal="mobilePreviewOpen || undefined"
            aria-label="Resume preview"
        >
            <Button
                v-if="mobilePreviewOpen"
                type="button"
                variant="outline"
                class="self-start lg:hidden"
                @click="mobilePreviewOpen = false"
                >← Back to editing</Button
            >
            <div class="min-h-0 flex-1 lg:h-full">
                <ResumePreview
                    :url="preview(resume.id).url"
                    :template="form.template"
                    :color="form.color"
                    :content="form.content"
                    :photo-key="resume.photo_url"
                />
            </div>
        </div>
    </div>
</template>
