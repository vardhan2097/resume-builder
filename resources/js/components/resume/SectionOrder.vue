<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { normaliseSectionOrder, SECTION_LABELS } from '@/lib/sections';
import type { SectionKey } from '@/types/resume';

const order = defineModel<SectionKey[]>({ required: true });

const sections = computed(() => normaliseSectionOrder(order.value));

function move(index: number, direction: -1 | 1) {
    const target = index + direction;
    const next = [...sections.value];

    if (target < 0 || target >= next.length) {
        return;
    }

    [next[index], next[target]] = [next[target], next[index]];
    order.value = next;
}
</script>

<template>
    <section class="rounded-xl border p-5">
        <h2 class="text-lg font-semibold">Section order</h2>
        <p class="mb-4 text-sm text-muted-foreground">
            Move sections up or down on your resume. In two-column templates the
            order applies within each column.
        </p>

        <ol class="grid gap-1">
            <li
                v-for="(key, index) in sections"
                :key="key"
                class="flex items-center justify-between rounded-md border px-3 py-1.5 text-sm"
            >
                <span>
                    <span class="mr-2 text-muted-foreground"
                        >{{ index + 1 }}.</span
                    >{{ SECTION_LABELS[key] }}
                </span>
                <span class="flex gap-1">
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :disabled="index === 0"
                        :aria-label="`Move ${SECTION_LABELS[key]} up`"
                        @click="move(index, -1)"
                    >
                        ↑
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :disabled="index === sections.length - 1"
                        :aria-label="`Move ${SECTION_LABELS[key]} down`"
                        @click="move(index, 1)"
                    >
                        ↓
                    </Button>
                </span>
            </li>
        </ol>
    </section>
</template>
