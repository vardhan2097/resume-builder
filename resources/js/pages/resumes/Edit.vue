<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/routes/resumes';

defineProps<{
    resume: { id: number; title: string; data: Record<string, unknown> };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My Resumes', href: index() }],
    },
});
</script>

<template>
    <Head :title="resume.title" />

    <div class="p-4">
        <h1 class="text-2xl font-semibold">{{ resume.title }}</h1>
        <pre class="mt-4 rounded bg-muted p-4 text-xs">{{ resume.data }}</pre>
    </div>
</template>