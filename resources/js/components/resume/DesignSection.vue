<script setup lang="ts">
import type { TemplateMeta } from '@/types/resume';

defineProps<{
    templates: Record<string, TemplateMeta>;
}>();

const template = defineModel<string>('template', { required: true });
const color = defineModel<string>('color', { required: true });
</script>

<template>
    <section class="rounded-xl border p-5">
        <h2 class="mb-4 text-lg font-semibold">Design</h2>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <label class="text-sm font-medium">Template</label>
                <select
                    v-model="template"
                    class="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                >
                    <option
                        v-for="(meta, key) in templates"
                        :key="key"
                        :value="key"
                    >
                        {{ meta.name }}
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium">Colour</label>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="swatch in templates[template]?.palettes ?? []"
                        :key="swatch"
                        type="button"
                        class="h-8 w-8 rounded-full border-2"
                        :class="
                            color === swatch
                                ? 'border-foreground'
                                : 'border-transparent'
                        "
                        :style="{ backgroundColor: swatch }"
                        :aria-label="`Use colour ${swatch}`"
                        @click="color = swatch"
                    />
                    <input
                        v-model="color"
                        type="color"
                        class="h-8 w-10 cursor-pointer rounded border"
                        aria-label="Custom colour"
                    />
                </div>
            </div>
        </div>
    </section>
</template>
