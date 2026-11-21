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
            }
        });
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

    element.closest('details')?.setAttribute('open', '');
    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    element.focus({ preventScroll: true });
}
