<script setup lang="ts">
import CertificationsSection from '@/components/resume/CertificationsSection.vue';
import EducationSection from '@/components/resume/EducationSection.vue';
import ExperienceSection from '@/components/resume/ExperienceSection.vue';
import HobbiesSection from '@/components/resume/HobbiesSection.vue';
import LevelSection from '@/components/resume/LevelSection.vue';
import PersonalSection from '@/components/resume/PersonalSection.vue';
import ProjectsSection from '@/components/resume/ProjectsSection.vue';
import ReferencesSection from '@/components/resume/ReferencesSection.vue';
import type { ResumeData } from '@/types/resume';

/** Every content section of the editor, shared by the account and guest editors. */
const content = defineModel<ResumeData>({ required: true });
</script>

<template>
    <PersonalSection v-model="content.personal" />
    <ExperienceSection v-model="content.experience" />
    <EducationSection v-model="content.education" />
    <LevelSection
        v-model="content.skills"
        v-model:show-levels="content.settings.show_levels.skills"
        :max="40"
        title="Skills"
        placeholder="e.g. Laravel"
    />
    <LevelSection
        v-model="content.tools"
        v-model:show-levels="content.settings.show_levels.tools"
        :max="30"
        title="Software / Tools"
        placeholder="e.g. VS Code"
    />
    <ProjectsSection v-model="content.projects" />
    <CertificationsSection v-model="content.certifications" />
    <LevelSection
        v-model="content.languages"
        v-model:show-levels="content.settings.show_levels.languages"
        :max="15"
        title="Languages"
        placeholder="e.g. English"
    />
    <HobbiesSection v-model="content.hobbies" />
    <ReferencesSection v-model="content.references" />
</template>
