<?php

namespace App\Services;

use App\Models\Resume;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $html = $this->renderer->render(
            $resume->template,
            $resume->color,
            $resume->data,
            pdf: true,
            photoPath: $resume->photo_path,
        );

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->download($this->filename($resume, 'pdf'));
    }

    public function docx(Resume $resume): BinaryFileResponse
    {
        return response()
            ->download($this->docx->build($resume), $this->filename($resume, 'docx'))
            ->deleteFileAfterSend(true);
    }

    private function filename(Resume $resume, string $extension): string
    {
        $name = ($resume->data['personal']['full_name'] ?? '') ?: ($resume->title ?: 'my');

        return (Str::slug($name) ?: 'my').'-resume.'.$extension;
    }
}
