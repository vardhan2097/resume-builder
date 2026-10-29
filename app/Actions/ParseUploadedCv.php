<?php

namespace App\Actions;

use App\Exceptions\CvHasNoText;
use App\Models\Resume;
use App\Services\CvParser;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Validates an uploaded CV and parses it into resume data. The upload is only
 * read from PHP's temp file; nothing is stored.
 *
 * @phpstan-import-type ResumeData from Resume
 */
class ParseUploadedCv
{
    public function __construct(private CvParser $parser) {}

    /**
     * @return ResumeData
     *
     * @throws ValidationException
     */
    public function __invoke(Request $request): array
    {
        $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf,docx', 'max:5120'],
        ]);

        $file = $request->file('cv');

        try {
            return $this->parser->parseFile($file->getRealPath(), $file->extension());
        } catch (CvHasNoText) {
            throw ValidationException::withMessages([
                'cv' => 'We couldn\'t find any text in this file. Scanned PDFs (photos of paper) aren\'t supported — try a PDF exported from Word or Google Docs.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'cv' => 'We couldn\'t read this file. Please try another PDF or DOCX.',
            ]);
        }
    }
}
