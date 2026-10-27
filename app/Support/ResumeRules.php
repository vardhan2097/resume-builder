<?php

namespace App\Support;

/**
 * Validation rules for a resume's editable fields, shared by the editor
 * (UpdateResumeRequest), the guest endpoints and the guest claim.
 */
class ResumeRules
{
    /**
     * @param  bool  $strict  false relaxes format checks (email, url, hex colour) but keeps the
     *                        shape and size limits, for previews of half-typed data.
     * @return array<string, array<int, string>>
     */
    public static function content(bool $strict = true): array
    {
        $rules = [
            'template' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],

            'data' => ['required', 'array:personal,experience,education,skills,tools,projects,languages,certifications,hobbies,references,settings'],

            'data.personal' => ['required', 'array:full_name,job_title,email,phone,location,website,linkedin,github,summary'],
            'data.personal.full_name' => ['nullable', 'string', 'max:100'],
            'data.personal.job_title' => ['nullable', 'string', 'max:100'],
            'data.personal.email' => ['nullable', 'email', 'max:150'],
            'data.personal.phone' => ['nullable', 'string', 'max:30'],
            'data.personal.location' => ['nullable', 'string', 'max:150'],
            'data.personal.website' => ['nullable', 'url', 'max:200'],
            'data.personal.linkedin' => ['nullable', 'url', 'max:200'],
            'data.personal.github' => ['nullable', 'url', 'max:200'],
            'data.personal.summary' => ['nullable', 'string', 'max:2000'],

            'data.experience' => ['present', 'array', 'max:20'],
            'data.experience.*.id' => ['required', 'string', 'max:50'],
            'data.experience.*.job_title' => ['nullable', 'string', 'max:100'],
            'data.experience.*.company' => ['nullable', 'string', 'max:150'],
            'data.experience.*.location' => ['nullable', 'string', 'max:150'],
            'data.experience.*.start_date' => ['nullable', 'string', 'max:20'],
            'data.experience.*.end_date' => ['nullable', 'string', 'max:20'],
            'data.experience.*.current' => ['boolean'],
            'data.experience.*.description' => ['nullable', 'string', 'max:3000'],

            'data.education' => ['present', 'array', 'max:10'],
            'data.education.*.id' => ['required', 'string', 'max:50'],
            'data.education.*.degree' => ['nullable', 'string', 'max:150'],
            'data.education.*.institution' => ['nullable', 'string', 'max:150'],
            'data.education.*.location' => ['nullable', 'string', 'max:150'],
            'data.education.*.start_date' => ['nullable', 'string', 'max:20'],
            'data.education.*.end_date' => ['nullable', 'string', 'max:20'],
            'data.education.*.score' => ['nullable', 'string', 'max:50'],
            'data.education.*.description' => ['nullable', 'string', 'max:1000'],

            'data.skills' => ['present', 'array', 'max:40'],
            'data.skills.*.id' => ['required', 'string', 'max:50'],
            'data.skills.*.name' => ['nullable', 'string', 'max:60'],
            'data.skills.*.level' => ['required', 'integer', 'between:1,5'],

            'data.tools' => ['present', 'array', 'max:30'],
            'data.tools.*.id' => ['required', 'string', 'max:50'],
            'data.tools.*.name' => ['nullable', 'string', 'max:60'],
            'data.tools.*.level' => ['required', 'integer', 'between:1,5'],

            'data.languages' => ['present', 'array', 'max:15'],
            'data.languages.*.id' => ['required', 'string', 'max:50'],
            'data.languages.*.name' => ['nullable', 'string', 'max:60'],
            'data.languages.*.level' => ['required', 'integer', 'between:1,5'],

            'data.projects' => ['present', 'array', 'max:15'],
            'data.projects.*.id' => ['required', 'string', 'max:50'],
            'data.projects.*.name' => ['nullable', 'string', 'max:150'],
            'data.projects.*.duration' => ['nullable', 'string', 'max:50'],
            'data.projects.*.link' => ['nullable', 'url', 'max:200'],
            'data.projects.*.description' => ['nullable', 'string', 'max:2000'],

            'data.certifications' => ['present', 'array', 'max:20'],
            'data.certifications.*.id' => ['required', 'string', 'max:50'],
            'data.certifications.*.name' => ['nullable', 'string', 'max:150'],
            'data.certifications.*.issuer' => ['nullable', 'string', 'max:150'],
            'data.certifications.*.date' => ['nullable', 'string', 'max:20'],

            'data.hobbies' => ['present', 'array', 'max:20'],
            'data.hobbies.*' => ['nullable', 'string', 'max:50'],

            'data.references' => ['present', 'array', 'max:5'],
            'data.references.*.id' => ['required', 'string', 'max:50'],
            'data.references.*.name' => ['nullable', 'string', 'max:100'],
            'data.references.*.position' => ['nullable', 'string', 'max:100'],
            'data.references.*.company' => ['nullable', 'string', 'max:150'],
            'data.references.*.phone' => ['nullable', 'string', 'max:30'],
            'data.references.*.email' => ['nullable', 'email', 'max:150'],

            // Optional so data saved before settings existed is still accepted.
            'data.settings' => ['sometimes', 'array:show_levels'],
            'data.settings.show_levels' => ['sometimes', 'array:skills,tools,languages'],
            'data.settings.show_levels.*' => ['boolean'],
        ];

        if ($strict) {
            return $rules;
        }

        return array_map(fn (array $field) => array_values(array_map(
            fn (string $rule) => in_array($rule, ['email', 'url'], true) || str_starts_with($rule, 'regex:') ? 'string' : $rule,
            $field,
        )), $rules);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function withTitle(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            ...self::content(),
        ];
    }
}
