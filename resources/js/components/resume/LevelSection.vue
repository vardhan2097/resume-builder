<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { Input } from '@/components/ui/input';
import type { LevelItem } from '@/types/resume';

const props = defineProps<{
    title: string;
    placeholder?: string;
    /** Matches the server's max count for this section. */
    max?: number;
}>();

const items = defineModel<LevelItem[]>({ required: true });
const showLevels = defineModel<boolean>('showLevels', { default: true });

const draft = ref('');
const DEFAULT_LEVEL = 3;

/** Add what was typed; a comma-separated paste adds several at once. */
function add() {
    const names = draft.value
        .split(',')
        .map((name) => name.trim())
        .filter(Boolean);

    for (const name of names) {
        const exists = items.value.some(
            (item) => item.name.trim().toLowerCase() === name.toLowerCase(),
        );

        if (!exists && items.value.length < (props.max ?? Infinity)) {
            items.value.push({
                id: crypto.randomUUID(),
                name,
                level: DEFAULT_LEVEL,
            });
        }
    }

    draft.value = '';
}

function remove(index: number) {
    items.value.splice(index, 1);
}

/** A pasted list ("PHP, Laravel, MySQL") is added straight away; a single word isn't. */
async function onPaste() {
    await nextTick();

    if (draft.value.includes(',')) {
        add();
    }
}
</script>

<template>
    <section class="rounded-xl border p-5">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="text-lg font-semibold">{{ title }}</h2>
            <label
                class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground"
            >
                <input
                    v-model="showLevels"
                    type="checkbox"
                    class="h-4 w-4 accent-primary"
                />
                Show ratings
            </label>
        </div>

        <div v-if="items.length" class="mb-3 flex flex-wrap gap-2">
            <span
                v-for="(item, index) in items"
                :key="item.id"
                class="inline-flex items-center gap-2 rounded-full bg-muted py-1 pr-2 pl-3 text-sm"
            >
                {{ item.name || 'Untitled' }}

                <!-- Hidden, not cleared: turning ratings back on restores them. -->
                <span
                    v-show="showLevels"
                    class="flex items-center gap-0.5"
                    role="radiogroup"
                    :aria-label="`${item.name} rating`"
                >
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        role="radio"
                        :aria-checked="item.level === n"
                        :aria-label="`${n} of 5`"
                        class="h-3 w-3 rounded-full border border-primary/60 transition-colors"
                        :class="
                            n <= item.level ? 'bg-primary' : 'bg-transparent'
                        "
                        @click="item.level = n"
                    />
                </span>

                <button
                    type="button"
                    class="text-muted-foreground hover:text-foreground"
                    :aria-label="`Remove ${item.name}`"
                    @click="remove(index)"
                >
                    ✕
                </button>
            </span>
        </div>

        <Input
            v-model="draft"
            :placeholder="`${placeholder ?? 'Type a name'}, then press Enter`"
            @keydown.enter.prevent="add"
            @blur="add"
            @paste="onPaste"
        />
    </section>
</template>
