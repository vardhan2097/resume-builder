<script setup lang="ts">
import { ref } from 'vue';
import { useDragSort } from '@/composables/useDragSort';
import { Input } from '@/components/ui/input';

const hobbies = defineModel<string[]>({ required: true });
const draft = ref('');
const list = ref<HTMLElement | null>(null);
const { dragging, onPointerDown, onKeydown } = useDragSort(hobbies, list);

function add() {
    const value = draft.value.trim();

    if (value && !hobbies.value.includes(value) && hobbies.value.length < 20) {
        hobbies.value.push(value);
    }

    draft.value = '';
}

function remove(index: number) {
    hobbies.value.splice(index, 1);
}
</script>

<template>
    <section class="rounded-xl border p-5">
        <h2 class="mb-4 text-lg font-semibold">Hobbies</h2>

        <div ref="list" class="mb-3 flex flex-wrap gap-2">
            <span
                v-for="(hobby, index) in hobbies"
                :key="hobby"
                :data-sort-index="index"
                class="inline-flex items-center gap-2 rounded-full bg-muted py-1 pr-3 pl-3 text-sm"
                :class="{
                    'opacity-60 ring-2 ring-primary': dragging === index,
                }"
            >
                <button
                    type="button"
                    data-sort-handle
                    class="-ml-1 cursor-grab touch-none text-muted-foreground select-none hover:text-foreground active:cursor-grabbing"
                    :aria-label="`Reorder ${hobby}: drag, or use the arrow keys`"
                    @pointerdown="onPointerDown(index, $event)"
                    @keydown="onKeydown(index, $event)"
                >
                    ⠿
                </button>
                {{ hobby }}
                <button
                    type="button"
                    class="text-muted-foreground hover:text-foreground"
                    :aria-label="`Remove ${hobby}`"
                    @click="remove(index)"
                >
                    ✕
                </button>
            </span>
        </div>

        <Input
            v-model="draft"
            placeholder="Type a hobby and press Enter"
            @keydown.enter.prevent="add"
            @blur="add"
        />
    </section>
</template>
