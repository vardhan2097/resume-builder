<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { build } from '@/routes';
import { edit as profileSettings } from '@/routes/profile';

const props = defineProps<{
    contactEmail: string;
    warnAfterDays: number;
    deleteAfterDays: number;
}>();

const graceDays = computed(() => props.deleteAfterDays - props.warnAfterDays);
const hasContact = computed(
    () => !!props.contactEmail && !props.contactEmail.endsWith('@example.com'),
);
const LAST_UPDATED = '22 November 2026';
</script>

<template>
    <Head title="Privacy policy" />

    <article
        class="mx-auto max-w-3xl px-4 py-12 text-sm leading-relaxed [&_h2]:mt-10 [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-semibold [&_li]:mt-1 [&_p]:mt-3 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5"
    >
        <h1 class="text-3xl font-bold tracking-tight">Privacy policy</h1>
        <p class="text-muted-foreground">Last updated: {{ LAST_UPDATED }}</p>

        <p>
            This page explains what information miResume.in uses, why we use it,
            and the choices you have about it.
        </p>

        <h2>What we store</h2>
        <ul>
            <li>
                <strong>Your account:</strong> your name, email address and a
                securely hashed password (we can't see your password).
            </li>
            <li>
                <strong>Your resumes:</strong> the information you enter, such
                as your contact details, work history, education and references,
                plus your chosen template and settings. Nationality and date of
                birth are optional.
            </li>
            <li>
                <strong>Your photo</strong>, if you add one: a resized copy for
                your resume and a larger copy so you can re-frame it later.
                Photos are stored privately for use in your previews and
                downloads.
            </li>
            <li>
                <strong>When you were last active</strong>, so we can delete
                accounts nobody uses any more (see below).
            </li>
        </ul>

        <h2>Guest resumes and uploaded files</h2>
        <ul>
            <li>
                <strong>Uploaded CV files.</strong> When you import a CV we read
                the text from a temporary upload and turn it into resume
                sections. The uploaded file is not kept as part of your saved
                resume. The extracted information is handled like other resume
                information you enter.
            </li>
            <li>
                <strong>Resumes built without an account.</strong> If you use
                the
                <Link :href="build()" class="underline">builder</Link>
                without signing up, your draft is saved in your browser's local
                storage. It is sent to our server to create previews and
                downloads. If you sign up or log in, the draft may be saved to
                your account. You can clear a guest draft with “Start over” or
                your browser's site-data controls.
            </li>
            <li>
                <strong>Downloads.</strong> We create PDF and Word files using
                the information in your resume. Word files are temporarily
                created on the server for delivery. Copies you download are
                stored on your device under your control.
            </li>
        </ul>

        <h2>Why we use it</h2>
        <p>
            Only to run the service: to let you log in, save and edit your
            resumes, create your PDF and Word downloads, and send you account
            emails (email verification, password resets and the inactivity
            warning described below). We use the email address you give us for
            these messages only — no newsletters or marketing.
        </p>

        <h2>Cookies</h2>
        <p>
            We use cookies for sessions, login, security, appearance and sidebar
            preferences. Guest drafts are stored separately in your browser's
            local storage. The application does not include advertising or
            analytics cookies.
        </p>

        <h2>Where your data is kept</h2>
        <p>
            Account data is stored on our hosting infrastructure. Hosting and
            email providers may process information needed to operate the
            service and deliver account emails. Technical details such as your
            IP address may be processed for security, troubleshooting and
            limiting abuse. We do not sell your personal data.
        </p>

        <h2>How long we keep it</h2>
        <ul>
            <li>
                While your account is in use, we keep your resumes until you
                delete them.
            </li>
            <li>
                After {{ warnAfterDays }} days without account activity, we aim
                to email you a deletion warning. Using your account before
                deletion cancels the warning.
            </li>
            <li>
                After at least {{ deleteAfterDays }} days without activity and
                {{ graceDays }} days after the warning, the scheduled cleanup
                can delete your account, resumes and photos. Timing depends on
                the cleanup schedule and successfully sending the warning.
            </li>
            <li>
                Backups and technical logs, where maintained, may retain
                information after deletion according to their retention cycles.
            </li>
        </ul>

        <h2>Your rights and choices</h2>
        <ul>
            <li>
                <strong>See and correct</strong> your information at any time in
                the editor and in your
                <Link :href="profileSettings()" class="underline"
                    >profile settings</Link
                >.
            </li>
            <li>
                <strong>Delete</strong> any resume from My Resumes, or delete
                your whole account from your profile settings. Deleting your
                account removes its saved resumes and photos. It does not remove
                files downloaded to your device or drafts in browser storage.
            </li>
            <li>
                <strong>Ask us</strong> for a summary of the data we hold about
                you, or raise a concern or complaint, using the contact below.
                You may also have rights under applicable privacy law.
            </li>
        </ul>

        <h2>Children</h2>
        <p>
            This service is meant for people aged 18 and over. Please don't
            create an account if you are younger.
        </p>

        <h2>Contact</h2>
        <p>
            <template v-if="hasContact">
                For privacy questions, data requests or complaints, email
                <a :href="`mailto:${contactEmail}`" class="underline">{{
                    contactEmail
                }}</a
                >. We'll reply as soon as we can.
            </template>
            <template v-else>
                A privacy contact has not yet been configured.
            </template>
        </p>

        <h2>Changes to this policy</h2>
        <p>
            If we change how we handle your data, we'll update this page and the
            date at the top.
        </p>
    </article>
</template>
