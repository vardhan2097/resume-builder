<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { home, login, privacy, register } from '@/routes';
import { index as myResumes } from '@/routes/resumes';

const page = usePage();
// On public pages there may be no logged-in user, despite the shared prop type.
const user = computed(() => page.props.auth.user ?? null);
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4"
            >
                <Link :href="home()" class="flex items-center">
                    <AppLogo />
                </Link>

                <nav class="flex items-center gap-2">
                    <Button v-if="user" as-child size="sm">
                        <Link :href="myResumes()">My Resumes</Link>
                    </Button>
                    <template v-else>
                        <Button as-child size="sm" variant="ghost">
                            <Link :href="login()">Log in</Link>
                        </Button>
                        <Button as-child size="sm">
                            <Link :href="register()">Sign up</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-6 text-sm text-muted-foreground"
            >
                <span
                    >© {{ new Date().getFullYear() }}
                    {{ page.props.name }}</span
                >
                <Link :href="privacy()" class="hover:text-foreground"
                    >Privacy policy</Link
                >
            </div>
        </footer>
    </div>
</template>
