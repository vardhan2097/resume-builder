<script setup lang="ts">
import { computed } from 'vue';
import CertificationsSection from '@/components/resume/CertificationsSection.vue';
import EducationSection from '@/components/resume/EducationSection.vue';
import ExperienceSection from '@/components/resume/ExperienceSection.vue';
import HobbiesSection from '@/components/resume/HobbiesSection.vue';
import LevelSection from '@/components/resume/LevelSection.vue';
import PersonalSection from '@/components/resume/PersonalSection.vue';
import ProjectsSection from '@/components/resume/ProjectsSection.vue';
import ReferencesSection from '@/components/resume/ReferencesSection.vue';
import { normaliseSectionOrder, SECTION_LABELS } from '@/lib/sections';
import { Button } from '@/components/ui/button';
import type { HiddenSectionKey, ResumeData } from '@/types/resume';

/** Every content section of the editor, shared by the account and guest editors. */
const content = defineModel<ResumeData>({ required: true });

// The editor lists sections in the same order as the resume.
const order = computed(() =>
    normaliseSectionOrder(content.value.settings.section_order),
);

const hiddenSections = computed(
    () => content.value.settings.hidden_sections ?? [],
);

function hideSection(key: HiddenSectionKey) {
    content.value.settings.hidden_sections = [...hiddenSections.value, key];
}

function restoreSection(key: HiddenSectionKey) {
    content.value.settings.hidden_sections = hiddenSections.value.filter(
        (section) => section !== key,
    );
}
</script>

<template>
    <PersonalSection
        v-model="content.personal"
        :summary-hidden="hiddenSections.includes('summary')"
        @hide-summary="hideSection('summary')"
    />

    <section v-if="hiddenSections.length" class="rounded-xl border p-5">
        <h2 class="mb-3 text-lg font-semibold">Hidden sections</h2>
        <div class="flex flex-wrap gap-2">
            <Button
                v-for="key in hiddenSections"
                :key="key"
                type="button"
                variant="outline"
                size="sm"
                @click="restoreSection(key)"
            >
                Restore
                {{ key === 'summary' ? 'Summary' : SECTION_LABELS[key] }}
            </Button>
        </div>
    </section>

    <template v-for="key in order" :key="key">
        <div v-if="!hiddenSections.includes(key)" class="relative">
            <div class="mb-2 flex justify-end">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="hideSection(key)"
                    >Hide {{ SECTION_LABELS[key] }}</Button
                >
            </div>
            <ExperienceSection
                v-if="key === 'experience'"
                v-model="content.experience"
            />
            <EducationSection
                v-else-if="key === 'education'"
                v-model="content.education"
            />
            <LevelSection
                v-else-if="key === 'skills'"
                v-model="content.skills"
                v-model:show-levels="content.settings.show_levels.skills"
                :max="40"
                title="Skills"
                placeholder="e.g. Laravel"
            />
            <LevelSection
                v-else-if="key === 'tools'"
                v-model="content.tools"
                v-model:show-levels="content.settings.show_levels.tools"
                :max="30"
                title="Software / Tools"
                placeholder="e.g. VS Code"
            />
            <ProjectsSection
                v-else-if="key === 'projects'"
                v-model="content.projects"
            />
            <CertificationsSection
                v-else-if="key === 'certifications'"
                v-model="content.certifications"
            />
            <LevelSection
                v-else-if="key === 'languages'"
                v-model="content.languages"
                v-model:show-levels="content.settings.show_levels.languages"
                :max="15"
                title="Languages"
                placeholder="e.g. English"
            />
            <HobbiesSection
                v-else-if="key === 'hobbies'"
                v-model="content.hobbies"
            />
            <ReferencesSection
                v-else-if="key === 'references'"
                v-model="content.references"
            />
        </div>
    </template>
</template>
