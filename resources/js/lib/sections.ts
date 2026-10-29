import type { SectionKey } from '@/types/resume';

/** Default order and labels; keep in sync with Resume::SECTIONS. */
export const SECTION_LABELS: Record<SectionKey, string> = {
    experience: 'Work experience',
    education: 'Education',
    skills: 'Skills',
    tools: 'Software / Tools',
    projects: 'Projects',
    certifications: 'Certifications',
    languages: 'Languages',
    hobbies: 'Hobbies',
    references: 'References',
};

const SECTIONS = Object.keys(SECTION_LABELS) as SectionKey[];

/**
 * The saved order with unknown or repeated keys dropped and missing sections
 * appended, so the editor always shows every section exactly once.
 */
export function normaliseSectionOrder(saved: unknown): SectionKey[] {
    const known = (Array.isArray(saved) ? saved : []).filter(
        (key): key is SectionKey => SECTIONS.includes(key as SectionKey),
    );

    return [...new Set([...known, ...SECTIONS])];
}
