<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { destroy, edit, index, store } from '@/routes/resumes';
import { ref } from 'vue';
import { store as importCv } from '@/actions/App/Http/Controllers/ResumeImportController';

type ResumeItem = {
    id: number;
    title: string;
    template: string;
    updated_at: string;
};

defineProps<{
    resumes: ResumeItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My Resumes', href: index() }],
    },
});

function createResume() {
    router.post(store().url);
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
const importForm = useForm<{ cv: File | null }>({ cv: null });

function chooseCv() {
    cvInput.value?.click();
}

function importResume(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    importForm.cv = file;
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

    <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">My Resumes</h1>
    <div class="flex gap-2">
        <Button
            variant="outline"
            :disabled="importForm.processing"
            @click="chooseCv"
        >
            {{ importForm.processing ? 'Reading your CV…' : 'Import CV' }}
        </Button>
        <Button @click="createResume">+ New Resume</Button>
    </div>
    <input
        ref="cvInput"
        type="file"
        accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        class="hidden"
        @change="importResume"
    />
    </div>

    <p
        v-if="importForm.errors.cv"
        class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700"
    >
        {{ importForm.errors.cv }}
    </p>
</template>