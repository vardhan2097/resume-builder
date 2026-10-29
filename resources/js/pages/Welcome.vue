<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { build, register } from '@/routes';
</script>

<template>
    <Head title="Free resume builder" />

    <section class="mx-auto max-w-3xl px-4 py-20 text-center">
        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
            A professional resume in minutes
        </h1>
        <p class="mx-auto mt-4 max-w-xl text-lg text-muted-foreground">
            Fill in your details, pick a template and download a PDF or Word
            file. No account needed to get started.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <Button as-child size="lg">
                <Link :href="build()">Build a resume — no signup needed</Link>
            </Button>
            <Button as-child size="lg" variant="outline">
                <Link :href="register()">Sign up to save</Link>
            </Button>
        </div>
    </section>
</template>
