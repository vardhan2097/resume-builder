<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Personal } from '@/types/resume';
import { Button } from '@/components/ui/button';

defineProps<{ summaryHidden: boolean; summaryIsHidden?: boolean }>();
const emit = defineEmits<{ hideSummary: [] }>();

const personal = defineModel<Personal>({ required: true });

const examples: Personal = {
    full_name: 'Aarav Sharma',
    job_title: 'Product Designer',
    email: 'aarav@example.com',
    phone: '+91 98765 43210',
    location: 'Pune, India',
    nationality: 'Indian',
    date_of_birth: '1995-05-15',
    website: 'https://example.com',
    linkedin: 'https://linkedin.com/in/example',
    github: 'https://github.com/example',
    summary:
        'Product designer with experience creating accessible digital experiences.',
};

const fields: { key: keyof Personal; label: string; type: string }[] = [
    { key: 'full_name', label: 'Full name', type: 'text' },
    { key: 'job_title', label: 'Job title', type: 'text' },
    { key: 'email', label: 'Email', type: 'email' },
    { key: 'phone', label: 'Phone', type: 'tel' },
    { key: 'location', label: 'Location', type: 'text' },
    { key: 'nationality', label: 'Nationality', type: 'text' },
    { key: 'date_of_birth', label: 'Date of birth', type: 'date' },
    { key: 'website', label: 'Website', type: 'url' },
    { key: 'linkedin', label: 'LinkedIn URL', type: 'url' },
    { key: 'github', label: 'GitHub URL', type: 'url' },
];
</script>

<template>
    <section class="rounded-xl border p-5">
        <h2 class="mb-4 text-lg font-semibold">Personal details</h2>

        <div class="grid gap-4 sm:grid-cols-2">
            <div v-for="field in fields" :key="field.key" class="grid gap-2">
                <Label :for="field.key">{{ field.label }}</Label>
                <Input
                    :id="field.key"
                    :type="field.type"
                    :data-field="`data.personal.${field.key}`"
                    :placeholder="examples[field.key]"
                    v-model="personal[field.key]"
                />
            </div>
        </div>

        <div v-if="!summaryHidden" class="mt-4 grid gap-2">
            <div class="flex items-center justify-between gap-2">
                <Label for="summary">Professional summary</Label>
                <Button
                    v-if="!summaryIsHidden"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="emit('hideSummary')"
                    >Hide summary</Button
                >
            </div>
            <p v-if="summaryIsHidden" class="text-sm text-muted-foreground">
                This section is hidden from your resume.
            </p>
            <textarea
                id="summary"
                data-field="data.personal.summary"
                v-model="personal.summary"
                :placeholder="examples.summary"
                rows="4"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            />
        </div>
    </section>
</template>
