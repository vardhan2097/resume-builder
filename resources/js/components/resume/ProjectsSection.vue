<script setup lang="ts">
import FieldTextarea from '@/components/resume/FieldTextarea.vue';
import RepeatableSection from '@/components/resume/RepeatableSection.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ProjectItem } from '@/types/resume';

const items = defineModel<ProjectItem[]>({ required: true });

const blank = () => ({ name: '', duration: '', link: '', description: '' });
const linkPlaceholder = 'https://';
</script>

<template>
    <RepeatableSection v-model="items" title="Projects" :make-item="blank">
        <template #default="{ item }">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Project name</Label>
                    <Input v-model="item.name" />
                </div>
                <div class="grid gap-2">
                    <Label>Duration</Label>
                    <Input v-model="item.duration" placeholder="e.g. 3 months" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label>Link</Label>
                    <Input type="url" v-model="item.link" :placeholder="linkPlaceholder" />
                </div>
            </div>
            <div class="grid gap-2">
                <Label>Description (one point per line)</Label>
                <FieldTextarea v-model="item.description" :rows="4" />
            </div>
        </template>
    </RepeatableSection>
</template>