<script setup lang="ts">
import RepeatableSection from '@/components/resume/RepeatableSection.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ReferenceItem } from '@/types/resume';

const items = defineModel<ReferenceItem[]>({ required: true });

const blank = () => ({ name: '', position: '', company: '', phone: '', email: '' });
</script>

<template>
    <RepeatableSection
        v-model="items"
        title="References"
        :make-item="blank"
        empty-text="Leave empty if references are available on request."
    >
        <template #default="{ item }">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Name</Label>
                    <Input v-model="item.name" />
                </div>
                <div class="grid gap-2">
                    <Label>Position</Label>
                    <Input v-model="item.position" />
                </div>
                <div class="grid gap-2">
                    <Label>Company</Label>
                    <Input v-model="item.company" />
                </div>
                <div class="grid gap-2">
                    <Label>Phone</Label>
                    <Input type="tel" v-model="item.phone" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label>Email</Label>
                    <Input type="email" v-model="item.email" />
                </div>
            </div>
        </template>
    </RepeatableSection>
</template>