<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ContentSections from '@/components/resume/ContentSections.vue';
import DesignSection from '@/components/resume/DesignSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { docx, index, pdf, preview, update } from '@/routes/resumes';
import ResumePreview from '@/components/resume/ResumePreview.vue';
import type { Resume, TemplateMeta } from '@/types/resume';
import PhotoSection from '@/components/resume/PhotoSection.vue';

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

function save(onSaved?: () => void) {
    form.transform(({ content, ...rest }) => ({ ...rest, data: content })).put(
        update(props.resume.id).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                form.defaults();
                onSaved?.();
            },
        },
    );
}

function download(kind: 'pdf' | 'docx') {
    const url =
        kind === 'pdf' ? pdf(props.resume.id).url : docx(props.resume.id).url;

    if (!form.isDirty) {
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
                    v-if="form.recentlySuccessful"
                    class="text-sm whitespace-nowrap text-green-600"
                >
                    Saved ✓
                </span>
                <span
                    v-else-if="form.isDirty"
                    class="text-sm whitespace-nowrap text-amber-600"
                >
                    Unsaved
                </span>
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
                <p class="mb-1 font-medium">Please fix these:</p>
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
