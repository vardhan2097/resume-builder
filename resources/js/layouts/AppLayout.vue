<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { claimGuestResume } from '@/composables/useGuestResume';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();

// Inertia keeps this layout mounted across page visits, so this runs once per
// full page load — i.e. right after logging in, registering or verifying email.
onMounted(() => {
    if (page.props.auth.user?.email_verified_at) {
        claimGuestResume();
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
