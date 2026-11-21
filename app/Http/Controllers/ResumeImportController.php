<?php

namespace App\Http\Controllers;

use App\Actions\ParseUploadedCv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResumeImportController extends Controller
{
    public function store(Request $request, ParseUploadedCv $parse): RedirectResponse
    {
        $validated = $request->validate([
            'template' => ['sometimes', 'string', Rule::in(array_keys(config('resume.templates')))],
        ]);

        $resume = $request->user()->resumes()->create([
            'title' => 'Imported '.now()->format('d M Y'),
            'data' => $parse($request),
            ...$validated,
            'color' => isset($validated['template'])
                ? config('resume.templates.'.$validated['template'].'.palettes.0', config('resume.default_color'))
                : config('resume.default_color'),
        ]);

        return redirect()->route('resumes.edit', $resume);
    }
}
