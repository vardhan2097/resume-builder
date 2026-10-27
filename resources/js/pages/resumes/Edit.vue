<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useEventListener, watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import ContentSections from '@/components/resume/ContentSections.vue';
import DesignSection from '@/components/resume/DesignSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { docx, index, pdf, preview, update } from '@/routes/resumes';
import ResumePreview from '@/components/resume/ResumePreview.vue';
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

    <div class="grid gap-6 p-4 lg:grid-cols-2">
        <form class="flex flex-col gap-6" @submit.prevent="save()">
            <div
                class="sticky top-0 z-10 flex items-center gap-3 rounded-xl border bg-background p-3"
            >
                <Input v-model="form.title" class="font-medium" />
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
                    <template v-else-if="saveFailed">Not saved</template>
                    <template v-else-if="form.isDirty">Unsaved</template>
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
                    PDF
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="download('docx')"
                >
                    Word
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
        </form>

        <div class="hidden lg:block">
            <div class="sticky top-4">
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
