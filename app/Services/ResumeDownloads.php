<?php

namespace App\Services;

use App\Models\Resume;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds the PDF / DOCX download responses. Works for saved resumes and for
 * unsaved (guest) Resume instances alike, since it only reads attributes.
 */
class ResumeDownloads
{
    public function __construct(
        private ResumeRenderer $renderer,
        private ResumeDocxBuilder $docx,
    ) {}

    public function pdf(Resume $resume): Response
    {
        return $this->renderer->document(
            $resume->template,
            $resume->color,
            $resume->data,
            photoPath: $resume->photo_path,
        )->download($this->filename($resume, 'pdf'));
    }

    public function docx(Resume $resume): BinaryFileResponse
    {
        return response()
            ->download($this->docx->build($resume), $this->filename($resume, 'docx'))
            ->deleteFileAfterSend(true);
    }

    private function filename(Resume $resume, string $extension): string
    {
        $name = trim((string) ($resume->data['personal']['full_name'] ?? '')) ?: ($resume->title ?: 'my');
        $template = array_key_exists((string) $resume->template, config('resume.templates'))
            ? $resume->template
            : config('resume.default_template');

        return (Str::slug($name) ?: 'my').'_'.Str::slug($template).'.'.$extension;
    }
}
