import type { ResumeData } from '@/types/resume';

export function sampleResume(defaults: ResumeData): ResumeData {
    return {
        ...defaults,
        personal: {
            full_name: 'Aarav Sharma',
            job_title: 'Product Designer',
            email: 'aarav@example.com',
            phone: '+91 98765 43210',
            location: 'Pune, India',
            website: 'https://example.com',
            linkedin: 'https://linkedin.com/in/example',
            github: 'https://github.com/example',
            summary:
                'Product designer with six years of experience creating accessible digital experiences and working with cross-functional teams.',
        },
        experience: [
            {
                id: 'sample-job',
                job_title: 'Senior Product Designer',
                company: 'Northstar Studio',
                location: 'Pune',
                start_date: '2021-01',
                end_date: '',
                current: true,
                description:
                    'Led the redesign of a customer platform, improving task completion by 25%.\nPartnered with engineers and researchers to deliver accessible features.',
            },
        ],
        education: [
            {
                id: 'sample-education',
                degree: 'Bachelor of Design',
                institution: 'Pune University',
                location: 'Pune',
                start_date: '2014-06',
                end_date: '2018-05',
                score: '',
                description: '',
            },
        ],
        skills: [
            { id: 'sample-skill-1', name: 'User research', level: 4 },
            { id: 'sample-skill-2', name: 'Interaction design', level: 5 },
        ],
        tools: [{ id: 'sample-tool', name: 'Figma', level: 5 }],
        projects: [
            {
                id: 'sample-project',
                name: 'Customer onboarding',
                duration: '2023',
                link: '',
                description:
                    'Designed a simpler onboarding journey for new customers.',
            },
        ],
        certifications: [
            {
                id: 'sample-certification',
                name: 'UX Design Certificate',
                issuer: 'Design Institute',
                date: '2022-05',
            },
        ],
        languages: [
            { id: 'sample-language', name: 'English', level: 5 },
            { id: 'sample-language-2', name: 'Hindi', level: 5 },
        ],
        hobbies: ['Photography', 'Cycling'],
        references: [
            {
                id: 'sample-reference',
                name: 'Maya Rao',
                position: 'Design Director',
                company: 'Northstar Studio',
                phone: '',
                email: 'maya@example.com',
            },
        ],
    };
}

function fillEmptyFields<T extends object>(item: T, example: T): T {
    return Object.fromEntries(
        Object.entries(item).map(([key, value]) => [
            key,
            typeof value === 'string' && !value.trim()
                ? ((example as Record<string, unknown>)[key] ?? value)
                : value,
        ]),
    ) as T;
}

function withExampleItems<T extends object>(items: T[], examples: T[]): T[] {
    return items.length
        ? items.map((item) => fillEmptyFields(item, examples[0]))
        : examples;
}

/** Build a separate preview payload without changing the user's resume. */
export function resumeWithExamples(data: ResumeData): ResumeData {
    const example = sampleResume(data);

    return {
        ...data,
        personal: fillEmptyFields(data.personal, example.personal),
        experience: withExampleItems(data.experience, example.experience),
        education: withExampleItems(data.education, example.education),
        skills: withExampleItems(data.skills, example.skills),
        tools: withExampleItems(data.tools, example.tools),
        languages: withExampleItems(data.languages, example.languages),
        projects: withExampleItems(data.projects, example.projects),
        certifications: withExampleItems(
            data.certifications,
            example.certifications,
        ),
        hobbies: data.hobbies.length ? data.hobbies : example.hobbies,
        references: withExampleItems(data.references, example.references),
    };
}
