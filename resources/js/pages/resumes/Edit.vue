<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ExperienceSection from '@/components/resume/ExperienceSection.vue';
import LevelSection from '@/components/resume/LevelSection.vue';
import PersonalSection from '@/components/resume/PersonalSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { docx, index, pdf, update } from '@/routes/resumes';
import ResumePreview from '@/components/resume/ResumePreview.vue';
import type { Resume, TemplateMeta } from '@/types/resume';
import PhotoSection from '@/components/resume/PhotoSection.vue';
import CertificationsSection from '@/components/resume/CertificationsSection.vue';
import EducationSection from '@/components/resume/EducationSection.vue';
import HobbiesSection from '@/components/resume/HobbiesSection.vue';
import ProjectsSection from '@/components/resume/ProjectsSection.vue';
import ReferencesSection from '@/components/resume/ReferencesSection.vue';

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

            <section class="rounded-xl border p-5">
                <h2 class="mb-4 text-lg font-semibold">Design</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Template</label>
                        <select
                            v-model="form.template"
                            class="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                        >
                            <option
                                v-for="(meta, key) in templates"
                                :key="key"
                                :value="key"
                            >
                                {{ meta.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Colour</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                v-for="swatch in templates[form.template]
                                    ?.palettes ?? []"
                                :key="swatch"
                                type="button"
                                class="h-8 w-8 rounded-full border-2"
                                :class="
                                    form.color === swatch
                                        ? 'border-foreground'
                                        : 'border-transparent'
                                "
                                :style="{ backgroundColor: swatch }"
                                :aria-label="`Use colour ${swatch}`"
                                @click="form.color = swatch"
                            />
                            <input
                                v-model="form.color"
                                type="color"
                                class="h-8 w-10 cursor-pointer rounded border"
                                aria-label="Custom colour"
                            />
                        </div>
                    </div>
                </div>
            </section>

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

            <PersonalSection v-model="form.content.personal" />
            <ExperienceSection v-model="form.content.experience" />
            <EducationSection v-model="form.content.education" />
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
            <ProjectsSection v-model="form.content.projects" />
            <CertificationsSection v-model="form.content.certifications" />
            <LevelSection
                v-model="form.content.languages"
                title="Languages"
                placeholder="e.g. English"
            />
            <HobbiesSection v-model="form.content.hobbies" />
            <ReferencesSection v-model="form.content.references" />
        </form>

        <div class="hidden lg:block">
            <div class="sticky top-4">
                <ResumePreview
                    :resume-id="resume.id"
                    :template="form.template"
                    :color="form.color"
                    :content="form.content"
                    :photo-key="resume.photo_url"
                />
            </div>
        </div>
    </div>
</template>
