<script setup lang="ts">
import FieldTextarea from '@/components/resume/FieldTextarea.vue';
import RepeatableSection from '@/components/resume/RepeatableSection.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EducationItem } from '@/types/resume';

const items = defineModel<EducationItem[]>({ required: true });

const blank = () => ({
    degree: '',
    institution: '',
    location: '',
    start_date: '',
    end_date: '',
    score: '',
    description: '',
});
</script>

<template>
    <RepeatableSection v-model="items" title="Education" :make-item="blank">
        <template #default="{ item }">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Degree / Course</Label>
                    <Input
                        v-model="item.degree"
                        placeholder="e.g. B.Sc. Computer Science"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Institution</Label>
                    <Input v-model="item.institution" />
                </div>
                <div class="grid gap-2">
                    <Label>Location</Label>
                    <Input v-model="item.location" />
                </div>
                <div class="grid gap-2">
                    <Label>Score</Label>
                    <Input
                        v-model="item.score"
                        placeholder="e.g. CGPA 8.2 / 75%"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Start</Label>
                    <Input type="month" v-model="item.start_date" />
                </div>
                <div class="grid gap-2">
                    <Label>End</Label>
                    <Input type="month" v-model="item.end_date" />
                </div>
            </div>
            <div class="grid gap-2">
                <Label>Details (optional, one point per line)</Label>
                <FieldTextarea v-model="item.description" />
            </div>
        </template>
    </RepeatableSection>
</template>
