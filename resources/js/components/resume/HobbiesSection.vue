<script setup lang="ts">
import { ref } from 'vue';
import { Input } from '@/components/ui/input';

const hobbies = defineModel<string[]>({ required: true });
const draft = ref('');

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

        <div class="mb-3 flex flex-wrap gap-2">
            <span
                v-for="(hobby, index) in hobbies"
                :key="hobby"
                class="inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1 text-sm"
            >
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
