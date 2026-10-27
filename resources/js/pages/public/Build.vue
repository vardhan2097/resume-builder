<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    docx,
    importMethod as importCv,
    pdf,
    preview,
} from '@/actions/App/Http/Controllers/GuestResumeController';
import ContentSections from '@/components/resume/ContentSections.vue';
import DesignSection from '@/components/resume/DesignSection.vue';
import ResumePreview from '@/components/resume/ResumePreview.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    hasContent,
    useGuestResume,
    withDefaults,
} from '@/composables/useGuestResume';
import { downloadPost, HttpError, post } from '@/lib/http';
import type { RequestErrors } from '@/lib/http';
import { login, register } from '@/routes';
import type { GuestResume, ResumeData, TemplateMeta } from '@/types/resume';

const props = defineProps<{
    templates: Record<string, TemplateMeta>;
    defaults: GuestResume;
}>();

const { resume, reset } = useGuestResume(props.defaults);

const busy = ref<'pdf' | 'docx' | 'import' | null>(null);
const errors = ref<RequestErrors>({});
const message = ref('');
const cvInput = ref<HTMLInputElement | null>(null);

function showError(error: unknown) {
    if (error instanceof HttpError) {
        errors.value = error.errors;
        message.value = Object.keys(error.errors).length ? '' : error.message;
    } else {
        console.error(error);
        message.value = 'Something went wrong. Please try again.';
    }
}

async function download(kind: 'pdf' | 'docx') {
    busy.value = kind;
    errors.value = {};
    message.value = '';

    try {
        await downloadPost(kind === 'pdf' ? pdf().url : docx().url, resume);
    } catch (error) {
        showError(error);
    } finally {
        busy.value = null;
    }
}

async function importResume(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    if (
        hasContent(resume.data) &&
        !confirm('Replace what you have typed with the imported CV?')
    ) {
        return;
    }

    busy.value = 'import';
    errors.value = {};
    message.value = '';

    try {
        const body = new FormData();
        body.append('cv', file);

        const response = await post(importCv().url, body);
        const { data } = (await response.json()) as { data: ResumeData };

        resume.data = withDefaults(data, props.defaults.data);
    } catch (error) {
        showError(error);
    } finally {
        busy.value = null;
    }
}

function startOver() {
    if (confirm('Clear everything and start a new resume?')) {
        reset();
    }
}
</script>

<template>
    <Head title="Build your resume" />

    <div class="mx-auto grid max-w-7xl gap-6 p-4 lg:grid-cols-2">
        <div class="flex flex-col gap-6">
            <div
                class="rounded-xl border border-primary/30 bg-primary/5 p-4 text-sm"
            >
                <p class="font-medium">You're building without an account.</p>
                <p class="text-muted-foreground">
                    Your work is kept in this browser only.
                    <Link :href="register()" class="underline">Sign up</Link>
                    or
                    <Link :href="login()" class="underline">log in</Link>
                    to save it to your account — we'll bring this resume with
                    you.
                </p>
            </div>

            <div
                class="sticky top-0 z-10 flex flex-wrap items-center gap-3 rounded-xl border bg-background p-3"
            >
                <Input
                    v-model="resume.title"
                    class="min-w-40 flex-1 font-medium"
                />
                <Button
                    type="button"
                    variant="outline"
                    :disabled="busy !== null"
                    @click="download('pdf')"
                >
                    {{ busy === 'pdf' ? 'Preparing…' : 'PDF' }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="busy !== null"
                    @click="download('docx')"
                >
                    {{ busy === 'docx' ? 'Preparing…' : 'Word' }}
                </Button>
                <Button as-child>
                    <Link :href="register()">Sign up to save</Link>
                </Button>
            </div>

            <div
                v-if="message || Object.keys(errors).length"
                class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700"
            >
                <p v-if="message">{{ message }}</p>
                <template v-else>
                    <p class="mb-1 font-medium">Please fix these:</p>
                    <ul class="list-disc pl-5">
                        <li v-for="(text, field) in errors" :key="field">
                            {{ text }}
                        </li>
                    </ul>
                </template>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="busy !== null"
                    @click="cvInput?.click()"
                >
                    {{
                        busy === 'import'
                            ? 'Reading your CV…'
                            : 'Import from an existing CV'
                    }}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="startOver"
                >
                    Start over
                </Button>
                <input
                    ref="cvInput"
                    type="file"
                    accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    class="hidden"
                    @change="importResume"
                />
            </div>

            <DesignSection
                v-model:template="resume.template"
                v-model:color="resume.color"
                :templates="templates"
            />

            <section
                v-if="templates[resume.template]?.photo"
                class="flex items-center gap-4 rounded-xl border p-5"
            >
                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-muted text-2xl"
                    aria-hidden="true"
                >
                    📷
                </div>
                <div>
                    <h2 class="text-lg font-semibold">Photo</h2>
                    <p class="text-sm text-muted-foreground">
                        <Link :href="register()" class="underline"
                            >Sign up</Link
                        >
                        to add a photo. Until then your initials are shown.
                    </p>
                </div>
            </section>

            <ContentSections v-model="resume.data" />
        </div>

        <div class="hidden lg:block">
            <div class="sticky top-4">
                <ResumePreview
                    :url="preview().url"
                    :template="resume.template"
                    :color="resume.color"
                    :content="resume.data"
                />
            </div>
        </div>
    </div>
</template>
