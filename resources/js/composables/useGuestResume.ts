import { router } from '@inertiajs/vue3';
import { useEventListener, watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';
import { claim } from '@/actions/App/Http/Controllers/ResumeController';
import type { GuestResume, ResumeData } from '@/types/resume';

const STORAGE_KEY = 'resume-builder:guest-resume';
const VERSION = 1;

type Stored = { version: number; resume: GuestResume };

/** Deep copy that also works on Vue's reactive proxies (structuredClone doesn't). */
function copy<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

/** True once the user has typed anything worth keeping. */
export function hasContent(data: ResumeData): boolean {
    return Object.entries(data).some(([key, value]) =>
        key === 'personal'
            ? Object.values(value).some(
                  (field) => String(field ?? '').trim() !== '',
              )
            : Array.isArray(value) && value.length > 0,
    );
}

// localStorage can throw (private mode, blocked storage), so every access is guarded.
function read(): GuestResume | null {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        const stored = raw ? (JSON.parse(raw) as Stored) : null;

        return stored?.version === VERSION ? stored.resume : null;
    } catch {
        return null;
    }
}

function write(resume: GuestResume): void {
    if (!hasContent(resume.data)) {
        clearGuestResume();

        return;
    }

    try {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({ version: VERSION, resume } satisfies Stored),
        );
    } catch {
        // Storage full or unavailable: the editor still works, it just won't persist.
    }
}

export function clearGuestResume(): void {
    try {
        window.localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Nothing to clear.
    }
}

/** Fill in any keys missing from older saved data so the editor never sees undefined. */
export function withDefaults(
    data: Partial<ResumeData> | undefined,
    defaults: ResumeData,
): ResumeData {
    return {
        ...defaults,
        ...data,
        personal: { ...defaults.personal, ...data?.personal },
        settings: {
            ...defaults.settings,
            ...data?.settings,
            show_levels: {
                ...defaults.settings.show_levels,
                ...data?.settings?.show_levels,
            },
        },
    };
}

/**
 * Reactive guest resume, restored from localStorage and saved back to it
 * (debounced) whenever it changes.
 */
export function useGuestResume(defaults: GuestResume) {
    const stored = read();
    const initial = copy(defaults);

    const resume = reactive<GuestResume>({
        ...initial,
        ...stored,
        data: withDefaults(stored?.data, initial.data),
    });

    watchDebounced(resume, () => write(resume), { debounce: 500, deep: true });

    // Don't lose the last keystrokes still waiting on the debounce when the tab closes.
    useEventListener(window, 'pagehide', () => write(resume));

    function reset() {
        Object.assign(resume, copy(defaults));
        clearGuestResume();
    }

    return { resume, reset };
}

/**
 * Called once a user is logged in: if they built a resume as a guest, save it
 * to their account and open it in the editor.
 */
export function claimGuestResume(): void {
    const resume = read();

    if (!resume) {
        return;
    }

    router.post(claim().url, resume, {
        onSuccess: clearGuestResume,
        onError: () => {
            // Invalid or outdated data: don't retry on every page load, but keep a copy.
            try {
                window.localStorage.setItem(
                    `${STORAGE_KEY}:unclaimed`,
                    window.localStorage.getItem(STORAGE_KEY) ?? '',
                );
            } catch {
                // Ignore.
            }

            clearGuestResume();
        },
    });
}
