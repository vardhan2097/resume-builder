<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ExperienceSection from '@/components/resume/ExperienceSection.vue';
import LevelSection from '@/components/resume/LevelSection.vue';
import PersonalSection from '@/components/resume/PersonalSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, update } from '@/routes/resumes';
import type { Resume } from '@/types/resume';

const props = defineProps<{
    resume: Resume;
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

function save() {
    form
        .transform(({ content, ...rest }) => ({ ...rest, data: content }))
        .put(update(props.resume.id).url, {
            preserveScroll: true,
            onSuccess: () => form.defaults(),
        });
}
</script>

<template>
    <Head :title="form.title || 'Edit resume'" />

    <div class="grid gap-6 p-4 lg:grid-cols-2">
        <form class="flex flex-col gap-6" @submit.prevent="save">
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
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving…' : 'Save' }}
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

            <PersonalSection v-model="form.content.personal" />
            <ExperienceSection v-model="form.content.experience" />
            <LevelSection
                v-model="form.content.skills"
                title="Skills"
                placeholder="e.g. Laravel"
            />
            <LevelSection
                v-model="form.content.tools"
                title="Software / Tools"
                placeholder="e.g. VS Code"
            />
            <LevelSection
                v-model="form.content.languages"
                title="Languages"
                placeholder="e.g. English"
            />
        </form>

        <div
            class="hidden rounded-xl border p-6 text-muted-foreground lg:block"
        >
            Live preview comes in Step 8.
        </div>
    </div>
</template>