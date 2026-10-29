<?php

namespace App\Services;

use App\Models\Resume;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Factory as ViewFactory;

class ResumeRenderer
{
    public function __construct(private ViewFactory $views) {}

    public function render(
        ?string $template,
        ?string $color,
        mixed $data,
        bool $pdf = false,
        ?string $photoPath = null,
    ): string {
        $templates = config('resume.templates');

        if (! array_key_exists((string) $template, $templates)) {
            $template = config('resume.default_template');
        }

        if (! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color)) {
            $color = config('resume.default_color');
        }

        $data = Resume::withDefaults($data);

        $photo = null;

        if (($templates[$template]['photo'] ?? false) && $photoPath && Storage::disk('local')->exists($photoPath)) {
            $photo = 'data:image/jpeg;base64,'.base64_encode(Storage::disk('local')->get($photoPath));
        }

        // The template name comes from config (checked above), not a literal view name,
        // so resolve its file explicitly; the finder throws if the file is missing.
        $file = $this->views->getFinder()->find("resumes.templates.{$template}");

        return $this->views->file($file, [
            'color' => $color,
            'data' => $data,
            'pdf' => $pdf,
            'photo' => $photo,
        ])->render();
    }
}
