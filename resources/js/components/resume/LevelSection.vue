<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { LevelItem } from '@/types/resume';

defineProps<{
    title: string;
    placeholder?: string;
}>();

const items = defineModel<LevelItem[]>({ required: true });

function add() {
    items.value.push({ id: crypto.randomUUID(), name: '', level: 3 });
}

function remove(index: number) {
    items.value.splice(index, 1);
}
</script>

<template>
    <section class="rounded-xl border p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">{{ title }}</h2>
            <Button type="button" size="sm" variant="outline" @click="add">
                + Add
            </Button>
        </div>

        <p v-if="items.length === 0" class="text-sm text-muted-foreground">
            Nothing added yet.
        </p>

        <div
            v-for="(item, index) in items"
            :key="item.id"
            class="mb-2 flex items-center gap-2"
        >
            <Input v-model="item.name" :placeholder="placeholder" />
            <select
                v-model.number="item.level"
                class="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
            >
                <option v-for="n in 5" :key="n" :value="n">{{ n }} / 5</option>
            </select>
            <Button
                type="button"
                size="sm"
                variant="ghost"
                @click="remove(index)"
            >
                ✕
            </Button>
        </div>
    </section>
</template>
