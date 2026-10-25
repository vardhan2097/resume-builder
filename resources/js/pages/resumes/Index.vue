<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { destroy, edit, index, store } from '@/routes/resumes';

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
</script>

<template>
    <Head title="My Resumes" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">My Resumes</h1>
            <Button @click="createResume">+ New Resume</Button>
        </div>

        <div
            v-if="resumes.length === 0"
            class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
        >
            You don't have any resumes yet. Click
            <strong>New Resume</strong> to start.
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