<script setup lang="ts">
import RepeatableSection from '@/components/resume/RepeatableSection.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CertificationItem } from '@/types/resume';

const items = defineModel<CertificationItem[]>({ required: true });

const blank = () => ({ name: '', issuer: '', date: '' });
</script>

<template>
    <RepeatableSection
        v-model="items"
        title="Certifications"
        :make-item="blank"
    >
        <template #default="{ item }">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label>Name</Label>
                    <Input
                        v-model="item.name"
                        placeholder="e.g. AWS Solutions Architect"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Issued by</Label>
                    <Input v-model="item.issuer" />
                </div>
                <div class="grid gap-2">
                    <Label>Date</Label>
                    <Input type="month" v-model="item.date" />
                </div>
            </div>
        </template>
    </RepeatableSection>
</template>
