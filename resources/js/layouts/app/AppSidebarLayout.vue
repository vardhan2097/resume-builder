<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import DeveloperCredit from '@/components/DeveloperCredit.vue';
import { Toaster } from '@/components/ui/sonner';
import { privacy } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
            <footer
                class="mt-auto border-t px-4 py-4 text-center text-sm text-muted-foreground"
            >
                <DeveloperCredit />
                <span aria-hidden="true"> · </span>
                <Link :href="privacy()" class="hover:text-foreground"
                    >Privacy policy</Link
                >
            </footer>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
