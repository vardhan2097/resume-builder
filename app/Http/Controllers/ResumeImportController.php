<?php

namespace App\Http\Controllers;

use App\Exceptions\CvHasNoText;
use App\Services\CvParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResumeImportController extends Controller
{
    public function store(Request $request, CvParser $parser): RedirectResponse
    {
        $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf,docx', 'max:5120'],
        ]);

        $file = $request->file('cv');

        try {
            $data = $parser->parseFile($file->getRealPath(), $file->extension());
        } catch (CvHasNoText) {
            return back()->withErrors([
                'cv' => 'We couldn\'t find any text in this file. Scanned PDFs (photos of paper) aren\'t supported — try a PDF exported from Word or Google Docs.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'cv' => 'We couldn\'t read this file. Please try another PDF or DOCX.',
            ]);
        }

        $resume = $request->user()->resumes()->create([
            'title' => 'Imported '.now()->format('d M Y'),
            'data' => $data,
        ]);

        return redirect()->route('resumes.edit', $resume);
    }
}