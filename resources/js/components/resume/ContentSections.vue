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
import { normaliseSectionOrder } from '@/lib/sections';
import type { ResumeData } from '@/types/resume';

/** Every content section of the editor, shared by the account and guest editors. */
const content = defineModel<ResumeData>({ required: true });

// The editor lists sections in the same order as the resume.
const order = computed(() =>
    normaliseSectionOrder(content.value.settings.section_order),
);
</script>

<template>
    <PersonalSection v-model="content.personal" />

    <template v-for="key in order" :key="key">
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
    </template>
</template>
