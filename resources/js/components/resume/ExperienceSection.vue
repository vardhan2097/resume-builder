<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExperienceItem } from '@/types/resume';

const items = defineModel<ExperienceItem[]>({ required: true });

function add() {
    items.value.push({
        id: crypto.randomUUID(),
        job_title: '',
        company: '',
        location: '',
        start_date: '',
        end_date: '',
        current: false,
        description: '',
    });
}

function remove(index: number) {
    items.value.splice(index, 1);
}
</script>

<template>
    <section class="rounded-xl border p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Work experience</h2>
            <Button type="button" size="sm" variant="outline" @click="add">
                + Add
            </Button>
        </div>

        <p v-if="items.length === 0" class="text-sm text-muted-foreground">
            No experience added yet.
        </p>

        <div
            v-for="(item, index) in items"
            :key="item.id"
            class="mb-4 grid gap-4 rounded-lg border p-4 last:mb-0"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Job title</Label>
                    <Input v-model="item.job_title" />
                </div>
                <div class="grid gap-2">
                    <Label>Company</Label>
                    <Input v-model="item.company" />
                </div>
                <div class="grid gap-2">
                    <Label>Location</Label>
                    <Input v-model="item.location" />
                </div>
                <div class="grid gap-2">
                    <Label>Start date</Label>
                    <Input type="month" v-model="item.start_date" />
                </div>
                <div class="grid gap-2">
                    <Label>End date</Label>
                    <Input
                        type="month"
                        v-model="item.end_date"
                        :disabled="item.current"
                    />
                </div>
                <label class="flex items-center gap-2 self-end text-sm">
                    <input type="checkbox" v-model="item.current" />
                    I currently work here
                </label>
            </div>

            <div class="grid gap-2">
                <Label>Description (one point per line)</Label>
                <textarea
                    v-model="item.description"
                    rows="4"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                />
            </div>

            <div class="flex justify-end">
                <Button
                    type="button"
                    size="sm"
                    variant="destructive"
                    @click="remove(index)"
                >
                    Remove
                </Button>
            </div>
        </div>
    </section>
</template>