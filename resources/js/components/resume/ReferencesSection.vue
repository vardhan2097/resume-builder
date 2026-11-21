<script setup lang="ts">
import RepeatableSection from '@/components/resume/RepeatableSection.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ReferenceItem } from '@/types/resume';

const items = defineModel<ReferenceItem[]>({ required: true });

const blank = () => ({
    name: '',
    position: '',
    company: '',
    phone: '',
    email: '',
});
</script>

<template>
    <RepeatableSection
        v-model="items"
        title="References"
        :make-item="blank"
        empty-text="Leave empty if references are available on request."
    >
        <template #default="{ item, index }">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Name</Label>
                    <Input
                        v-model="item.name"
                        :data-field="`data.references.${index}.name`"
                        placeholder="e.g. Maya Rao"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Position</Label>
                    <Input
                        v-model="item.position"
                        :data-field="`data.references.${index}.position`"
                        placeholder="e.g. Design Director"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Company</Label>
                    <Input
                        v-model="item.company"
                        :data-field="`data.references.${index}.company`"
                        placeholder="e.g. Northstar Studio"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Phone</Label>
                    <Input
                        type="tel"
                        v-model="item.phone"
                        :data-field="`data.references.${index}.phone`"
                        placeholder="e.g. +91 98765 43210"
                    />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label>Email</Label>
                    <Input
                        type="email"
                        v-model="item.email"
                        :data-field="`data.references.${index}.email`"
                        placeholder="e.g. maya@example.com"
                    />
                </div>
            </div>
        </template>
    </RepeatableSection>
</template>
