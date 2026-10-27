<script setup lang="ts">
defineProps<{
    rows?: number;
    placeholder?: string;
}>();

const model = defineModel<string | null>();
</script>

<template>
    <textarea
        v-model="model"
        :rows="rows ?? 3"
        :placeholder="placeholder"
        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
    />
</template>