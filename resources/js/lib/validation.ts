import { nextTick } from 'vue';
import type { RequestErrors } from '@/lib/http';

export async function highlightErrors(errors: RequestErrors): Promise<void> {
    await nextTick();
    document
        .querySelectorAll<HTMLElement>('[data-field][aria-invalid="true"]')
        .forEach((element) => {
            element.removeAttribute('aria-invalid');
        });
    document
        .querySelectorAll<HTMLElement>('[data-field]')
        .forEach((element) => {
            if (element.dataset.field && errors[element.dataset.field]) {
                element.setAttribute('aria-invalid', 'true');
                let parent = element.parentElement;
                while (parent) {
                    if (parent instanceof HTMLDetailsElement)
                        parent.open = true;
                    parent = parent.parentElement;
                }
            }
        });
    for (const field of Object.keys(errors)) {
        const section = field.startsWith('data.') ? field.split('.')[1] : '';
        document
            .querySelectorAll<HTMLDetailsElement>('details[data-section]')
            .forEach((details) => {
                if (details.dataset.section === section) details.open = true;
            });
    }
}

export async function focusError(field: string): Promise<void> {
    await nextTick();
    const target = [
        ...document.querySelectorAll<HTMLElement>('[data-field]'),
    ].find((element) => element.dataset.field === field);
    const section = field.startsWith('data.') ? field.split('.')[1] : '';
    const fallback = [
        ...document.querySelectorAll<HTMLElement>('[data-section]'),
    ].find((element) => element.dataset.section === section);
    const element = target ?? fallback?.querySelector<HTMLElement>('summary');
    if (!element) return;

    let parent = element.parentElement;
    while (parent) {
        if (parent instanceof HTMLDetailsElement) parent.open = true;
        parent = parent.parentElement;
    }
    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    element.focus({ preventScroll: true });
}
