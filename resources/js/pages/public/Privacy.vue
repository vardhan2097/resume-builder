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
const LAST_UPDATED = '28 October 2026';
</script>

<template>
    <Head title="Privacy policy" />

    <article
        class="mx-auto max-w-3xl px-4 py-12 text-sm leading-relaxed [&_h2]:mt-10 [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-semibold [&_li]:mt-1 [&_p]:mt-3 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5"
    >
        <h1 class="text-3xl font-bold tracking-tight">Privacy policy</h1>
        <p class="text-muted-foreground">Last updated: {{ LAST_UPDATED }}</p>

        <p>
            This page explains, in plain language, what information this resume
            builder keeps about you, why, and how to get it removed. We aim to
            handle your data in line with India's Digital Personal Data
            Protection Act, 2023 (DPDP Act).
        </p>

        <h2>What we store</h2>
        <ul>
            <li>
                <strong>Your account:</strong> your name, email address and a
                securely hashed password (we can't see your password).
            </li>
            <li>
                <strong>Your resumes:</strong> everything you type into the
                editor, plus the template and colour you pick.
            </li>
            <li>
                <strong>Your photo</strong>, if you add one: a resized copy for
                your resume and a larger copy so you can re-frame it later.
                Photos are stored privately and are only shown to you and in the
                files you download.
            </li>
            <li>
                <strong>When you were last active</strong>, so we can delete
                accounts nobody uses any more (see below).
            </li>
        </ul>

        <h2>What we don't store</h2>
        <ul>
            <li>
                <strong>Uploaded CV files.</strong> When you import a CV we read
                the text, turn it into resume sections and then discard the
                file. Only the text you keep in your resume is saved.
            </li>
            <li>
                <strong>Resumes built without an account.</strong> If you use
                the
                <Link :href="build()" class="underline">builder</Link>
                without signing up, your resume stays in your own browser. Our
                server only uses it briefly to draw the preview or create your
                download, and doesn't save it.
            </li>
            <li>
                <strong>No ads or tracking.</strong> We don't use advertising or
                analytics cookies, and we don't sell or share your data.
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
            We use only the cookies the site needs to work: one to keep you
            logged in (plus one more if you tick “Remember me”), one to protect
            forms against forgery, and two that remember your light/dark
            appearance and whether the sidebar is open.
        </p>

        <h2>Where your data is kept</h2>
        <p>
            Your data is stored on servers rented from our web hosting provider
            (Hostinger). Our server may briefly record technical details such as
            your IP address to keep the service secure and to limit abuse (for
            example, too many requests in a minute).
        </p>

        <h2>How long we keep it</h2>
        <ul>
            <li>
                While your account is in use, we keep your resumes until you
                delete them.
            </li>
            <li>
                If you don't log in for {{ warnAfterDays }} days, we email you
                to say your account will be deleted in {{ graceDays }} days.
                Logging in at any time before then cancels this.
            </li>
            <li>
                After {{ deleteAfterDays }} days without activity (and at least
                {{ graceDays }} days after that email), your account, resumes
                and photos are permanently deleted.
            </li>
            <li>
                Our host's routine backups may hold a copy for a limited time
                before they are overwritten.
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
                account removes your resumes and photos straight away.
            </li>
            <li>
                <strong>Ask us</strong> for a summary of the data we hold about
                you, or raise a concern or complaint, using the contact below.
                Under the DPDP Act you may also approach the Data Protection
                Board of India if you are not satisfied with our response.
            </li>
        </ul>

        <h2>Children</h2>
        <p>
            This service is meant for people aged 18 and over. Please don't
            create an account if you are younger.
        </p>

        <h2>Contact</h2>
        <p>
            For privacy questions, data requests or complaints, email
            <a :href="`mailto:${contactEmail}`" class="underline">{{
                contactEmail
            }}</a
            >. We'll reply as soon as we can.
        </p>

        <h2>Changes to this policy</h2>
        <p>
            If we change how we handle your data, we'll update this page and the
            date at the top.
        </p>
    </article>
</template>
