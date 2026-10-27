<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import PhotoCropDialog from '@/components/resume/PhotoCropDialog.vue';
import type { PhotoCrop } from '@/components/resume/PhotoCropDialog.vue';
import { Button } from '@/components/ui/button';
import photo from '@/routes/resumes/photo';

const props = defineProps<{
    resumeId: number;
    photoUrl: string | null;
    photoOriginalUrl: string | null;
    initials: string;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);
const framing = ref(false);
let objectUrl: string | null = null;

const form = useForm<{ photo: File | null; crop: PhotoCrop | null }>({
    photo: null,
    crop: null,
});

function choose() {
    fileInput.value?.click();
}

function releaseObjectUrl() {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
}

function resetInput() {
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function pick(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    releaseObjectUrl();
    objectUrl = URL.createObjectURL(file);
    form.photo = file;
    cropSrc.value = objectUrl;
    cropOpen.value = true;
}

function adjust() {
    form.photo = null;
    cropSrc.value = props.photoOriginalUrl;
    cropOpen.value = true;
}

function close() {
    cropOpen.value = false;
    form.reset();
    resetInput();
    releaseObjectUrl();
}

function save(crop: PhotoCrop) {
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: close,
        onError: close,
    };

    if (form.photo) {
        form.crop = crop;
        form.post(photo.upload(props.resumeId).url, options);

        return;
    }

    framing.value = true;
    router.put(photo.frame(props.resumeId).url, { crop }, {
        ...options,
        onFinish: () => (framing.value = false),
    });
}

function remove() {
    if (!confirm('Remove your photo?')) {
        return;
    }

    router.delete(photo.delete(props.resumeId).url, {
        preserveScroll: true,
        preserveState: true,
    });
}

onBeforeUnmount(releaseObjectUrl);
</script>

<template>
    <section class="rounded-xl border p-5">
        <h2 class="mb-4 text-lg font-semibold">Photo</h2>

        <div class="flex items-center gap-4">
            <img
                v-if="photoUrl"
                :src="photoUrl"
                alt="Your photo"
                class="h-20 w-20 rounded-full object-cover"
            />
            <div
                v-else
                class="flex h-20 w-20 items-center justify-center rounded-full bg-muted text-xl font-semibold"
            >
                {{ initials }}
            </div>

            <div class="flex flex-col gap-2">
                <div class="flex gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="form.processing"
                        @click="choose"
                    >
                        {{ form.processing ? 'Uploading…' : photoUrl ? 'Change' : 'Upload' }}
                    </Button>
                    <Button
                        v-if="photoUrl && photoOriginalUrl"
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="adjust"
                    >
                        Adjust
                    </Button>
                    <Button
                        v-if="photoUrl"
                        type="button"
                        size="sm"
                        variant="ghost"
                        @click="remove"
                    >
                        Remove
                    </Button>
                </div>
                <p class="text-xs text-muted-foreground">JPG, PNG or WebP, up to 5MB.</p>
                <p v-if="form.errors.photo" class="text-xs text-red-600">
                    {{ form.errors.photo }}
                </p>
            </div>
        </div>

        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="pick"
        />

        <PhotoCropDialog
            v-model:open="cropOpen"
            :src="cropSrc"
            :processing="form.processing || framing"
            @confirm="save"
            @cancel="close"
        />
    </section>
</template>
