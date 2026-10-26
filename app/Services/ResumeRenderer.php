<?php

namespace App\Services;

use App\Models\Resume;

class ResumeRenderer
{
    public function render(?string $template, ?string $color, mixed $data, bool $pdf = false): string
    {
        $templates = config('resume.templates');

        if (! array_key_exists((string) $template, $templates)) {
            $template = config('resume.default_template');
        }

        if (! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color)) {
            $color = config('resume.default_color');
        }

        $defaults = Resume::defaultData();
        $data = array_replace($defaults, (array) $data);
        $data['personal'] = array_replace($defaults['personal'], (array) ($data['personal'] ?? []));

        return view("resumes.templates.{$template}", [
            'color' => $color,
            'data' => $data,
            'pdf' => $pdf,
        ])->render();
    }
}