<script setup lang="ts">
import type { RequestErrors } from '@/lib/http';

const props = defineProps<{ errors: RequestErrors }>();
const emit = defineEmits<{ select: [field: string] }>();

const labels: Record<string, string> = {
    full_name: 'Full name',
    job_title: 'Job title',
    email: 'Email',
    phone: 'Phone',
    location: 'Location',
    website: 'Website',
    linkedin: 'LinkedIn URL',
    github: 'GitHub URL',
    summary: 'Professional summary',
    company: 'Company',
    description: 'Description',
    start_date: 'Start date',
    end_date: 'End date',
    current: 'Current position',
    degree: 'Degree',
    institution: 'Institution',
    score: 'Score',
    name: 'Name',
    duration: 'Duration',
    link: 'Link',
    issuer: 'Issuer',
    date: 'Date',
    position: 'Position',
    title: 'Resume title',
    template: 'Template',
    color: 'Colour',
};
const sections: Record<string, string> = {
    experience: 'Work experience',
    education: 'Education',
    projects: 'Project',
    certifications: 'Certification',
    references: 'Reference',
    skills: 'Skills',
    tools: 'Software / Tools',
    languages: 'Languages',
    hobbies: 'Hobbies',
};

function label(field: string): string {
    const parts = field.split('.');
    const last = parts[parts.length - 1];
    const name = labels[last] ?? last.replaceAll('_', ' ');
    if (parts[0] !== 'data' || parts[1] === 'personal') return name;
    const section = sections[parts[1]] ?? parts[1];
    const index = Number(parts[2]);
    return Number.isInteger(index) && parts[2] !== undefined
        ? `${section} ${index + 1} — ${name}`
        : `${section} — ${name}`;
}

function message(field: string): string {
    const last = field.split('.').at(-1) ?? field;
    const fieldName = labels[last] ?? last.replaceAll('_', ' ');
    return props.errors[field].replace(
        /data\.[\w.]+/g,
        fieldName.toLowerCase(),
    );
}
</script>

<template>
    <div
        role="alert"
        class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-100"
    >
        <p class="mb-2 font-semibold">Please check the following fields</p>
        <ul class="space-y-2">
            <li v-for="(text, field) in errors" :key="field">
                <button
                    type="button"
                    class="text-left underline underline-offset-2"
                    @click="emit('select', field)"
                >
                    <span class="font-medium">{{ label(field) }}</span> —
                    {{ message(field) }}
                </button>
            </li>
        </ul>
    </div>
</template>
