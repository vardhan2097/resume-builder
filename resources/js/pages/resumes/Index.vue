<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { destroy, edit, index, store } from '@/routes/resumes';
import { ref } from 'vue';
import { store as importCv } from '@/actions/App/Http/Controllers/ResumeImportController';
import TemplateGallery from '@/components/resume/TemplateGallery.vue';
import type { ResumeData, TemplateMeta } from '@/types/resume';

type ResumeItem = {
    id: number;
    title: string;
    template: string;
    updated_at: string;
};

defineProps<{
    resumes: ResumeItem[];
    templates: Record<string, TemplateMeta>;
    defaults: ResumeData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My Resumes', href: index() }],
    },
});

const choosingTemplate = ref(false);
const importing = ref(false);
const selectedImportTemplate = ref('');

function createResume(template: string) {
    if (importing.value) {
        selectedImportTemplate.value = template;
        choosingTemplate.value = false;
        chooseCv();
        return;
    }
    router.post(store().url, { template });
}

function deleteResume(resume: ResumeItem) {
    if (!confirm(`Delete "${resume.title}"? This cannot be undone.`)) {
        return;
    }

    router.delete(destroy(resume.id).url, { preserveScroll: true });
}

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const cvInput = ref<HTMLInputElement | null>(null);
const importForm = useForm<{ cv: File | null; template: string }>({
    cv: null,
    template: '',
});

function chooseCv() {
    cvInput.value?.click();
}

function importResume(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    importForm.cv = file;
    importForm.template = selectedImportTemplate.value;
    importForm.post(importCv().url, {
        onFinish: () => {
            importForm.reset();
            if (cvInput.value) {
                cvInput.value.value = '';
            }
        },
    });
}
</script>

<template>
    <Head title="My Resumes" />

    <input
        ref="cvInput"
        type="file"
        accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        class="hidden"
        @change="importResume"
    />
    <div v-if="choosingTemplate">
        <div class="mx-auto max-w-7xl px-4 pt-4">
            <Button variant="ghost" @click="choosingTemplate = false"
                >← My resumes</Button
            >
        </div>
        <TemplateGallery
            :templates="templates"
            :defaults="defaults"
            @select="createResume"
        />
    </div>
    <div v-else class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold">My Resumes</h1>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    :disabled="importForm.processing"
                    @click="
                        importing = true;
                        choosingTemplate = true;
                    "
                >
                    {{
                        importForm.processing ? 'Reading your CV…' : 'Import CV'
                    }}
                </Button>
                <Button
                    @click="
                        importing = false;
                        choosingTemplate = true;
                    "
                    >+ New Resume</Button
                >
            </div>
        </div>

        <p
            v-if="importForm.errors.cv"
            class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700"
        >
            {{ importForm.errors.cv }}
        </p>

        <div
            v-if="resumes.length === 0"
            class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
        >
            You don't have any resumes yet. Click
            <strong>New Resume</strong> to start, or
            <strong>Import CV</strong> to fill one from an existing file.
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="resume in resumes"
                :key="resume.id"
                class="flex flex-col justify-between gap-4 rounded-xl border p-5"
            >
                <div>
                    <h2 class="text-lg font-medium">{{ resume.title }}</h2>
                    <p class="text-sm text-muted-foreground">
                        Template: {{ resume.template }} · Updated
                        {{ formatDate(resume.updated_at) }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button as-child size="sm">
                        <Link :href="edit(resume.id).url">Edit</Link>
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        @click="deleteResume(resume)"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
