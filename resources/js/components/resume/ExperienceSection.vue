<script setup lang="ts">
import FieldTextarea from '@/components/resume/FieldTextarea.vue';
import RepeatableSection from '@/components/resume/RepeatableSection.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExperienceItem } from '@/types/resume';

const items = defineModel<ExperienceItem[]>({ required: true });

const blank = () => ({
    job_title: '',
    company: '',
    location: '',
    start_date: '',
    end_date: '',
    current: false,
    description: '',
});
</script>

<template>
    <RepeatableSection
        v-model="items"
        title="Work experience"
        :make-item="blank"
        empty-text="No experience added yet."
    >
        <template #default="{ item, index }">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Job title</Label>
                    <Input
                        v-model="item.job_title"
                        :data-field="`data.experience.${index}.job_title`"
                        placeholder="e.g. Product Designer"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Company</Label>
                    <Input
                        v-model="item.company"
                        :data-field="`data.experience.${index}.company`"
                        placeholder="e.g. Northstar Studio"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Location</Label>
                    <Input
                        v-model="item.location"
                        :data-field="`data.experience.${index}.location`"
                        placeholder="e.g. Pune"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Start date</Label>
                    <Input
                        type="month"
                        v-model="item.start_date"
                        :data-field="`data.experience.${index}.start_date`"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>End date</Label>
                    <Input
                        type="month"
                        v-model="item.end_date"
                        :data-field="`data.experience.${index}.end_date`"
                        :disabled="item.current"
                    />
                </div>
                <label class="flex items-center gap-2 self-end text-sm">
                    <input
                        type="checkbox"
                        v-model="item.current"
                        :data-field="`data.experience.${index}.current`"
                    />
                    I currently work here
                </label>
            </div>

            <div class="grid gap-2">
                <Label>Description (one point per line)</Label>
                <FieldTextarea
                    v-model="item.description"
                    :data-field="`data.experience.${index}.description`"
                    :rows="4"
                    placeholder="Describe your responsibilities and achievements"
                />
            </div>
        </template>
    </RepeatableSection>
</template>
