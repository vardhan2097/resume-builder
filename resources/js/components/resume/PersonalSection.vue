<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Personal } from '@/types/resume';

const personal = defineModel<Personal>({ required: true });

const fields: { key: keyof Personal; label: string; type: string }[] = [
    { key: 'full_name', label: 'Full name', type: 'text' },
    { key: 'job_title', label: 'Job title', type: 'text' },
    { key: 'email', label: 'Email', type: 'email' },
    { key: 'phone', label: 'Phone', type: 'tel' },
    { key: 'location', label: 'Location', type: 'text' },
    { key: 'website', label: 'Website', type: 'url' },
    { key: 'linkedin', label: 'LinkedIn URL', type: 'url' },
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
                    v-model="personal[field.key]"
                />
            </div>
        </div>

        <div class="mt-4 grid gap-2">
            <Label for="summary">Professional summary</Label>
            <textarea
                id="summary"
                v-model="personal.summary"
                rows="4"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            />
        </div>
    </section>
</template>
