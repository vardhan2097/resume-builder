export type Personal = {
    full_name: string;
    job_title: string;
    email: string;
    phone: string;
    location: string;
    website: string;
    linkedin: string;
    summary: string;
};

export type ExperienceItem = {
    id: string;
    job_title: string;
    company: string;
    location: string;
    start_date: string;
    end_date: string;
    current: boolean;
    description: string;
};

export type EducationItem = {
    id: string;
    degree: string;
    institution: string;
    location: string;
    start_date: string;
    end_date: string;
    score: string;
    description: string;
};

export type LevelItem = {
    id: string;
    name: string;
    level: number;
};

export type ProjectItem = {
    id: string;
    name: string;
    duration: string;
    link: string;
    description: string;
};

export type CertificationItem = {
    id: string;
    name: string;
    issuer: string;
    date: string;
};

export type ReferenceItem = {
    id: string;
    name: string;
    position: string;
    company: string;
    phone: string;
    email: string;
};

export type ResumeData = {
    personal: Personal;
    experience: ExperienceItem[];
    education: EducationItem[];
    skills: LevelItem[];
    tools: LevelItem[];
    languages: LevelItem[];
    projects: ProjectItem[];
    certifications: CertificationItem[];
    hobbies: string[];
    references: ReferenceItem[];
};

export type Resume = {
    id: number;
    title: string;
    template: string;
    color: string;
    data: ResumeData;
};