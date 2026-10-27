<script setup lang="ts" generic="T extends { id: string }">
import { Button } from '@/components/ui/button';

const props = defineProps<{
    title: string;
    makeItem: () => Omit<T, 'id'>;
    emptyText?: string;
}>();

const items = defineModel<T[]>({ required: true });

function add() {
    items.value.push({ id: crypto.randomUUID(), ...props.makeItem() } as T);
}

function remove(index: number) {
    items.value.splice(index, 1);
}

function move(index: number, direction: -1 | 1) {
    const target = index + direction;

    if (target < 0 || target >= items.value.length) {
        return;
    }

    const [item] = items.value.splice(index, 1);
    items.value.splice(target, 0, item);
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
            {{ emptyText ?? 'Nothing added yet.' }}
        </p>

        <div
            v-for="(item, index) in items"
            :key="item.id"
            class="mb-4 grid gap-4 rounded-lg border p-4 last:mb-0"
        >
            <slot :item="item" :index="index" />

            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    :disabled="index === 0"
                    aria-label="Move up"
                    @click="move(index, -1)"
                >
                    ↑
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    :disabled="index === items.length - 1"
                    aria-label="Move down"
                    @click="move(index, 1)"
                >
                    ↓
                </Button>
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