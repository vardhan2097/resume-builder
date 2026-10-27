<?php

namespace App\Http\Controllers;

use App\Actions\ParseUploadedCv;
use App\Models\Resume;
use App\Services\ResumeDownloads;
use App\Services\ResumeRenderer;
use App\Support\ResumeRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Resume building without an account. Guest work lives in the browser
 * (localStorage); these endpoints only render what they are sent and never
 * write to the database or disk.
 */
class GuestResumeController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('resumes.index');
        }

        return Inertia::render('public/Build', [
            'templates' => config('resume.templates'),
            'defaults' => [
                'title' => 'My Resume',
                'template' => config('resume.default_template'),
                'color' => config('resume.default_color'),
                'data' => Resume::defaultData(),
            ],
        ]);
    }

    public function preview(Request $request, ResumeRenderer $renderer): HttpResponse
    {
        $validated = $request->validate(ResumeRules::content(strict: false));

        return response($renderer->render($validated['template'], $validated['color'], $validated['data']));
    }

    public function pdf(Request $request, ResumeDownloads $downloads): SymfonyResponse
    {
        return $downloads->pdf($this->unsavedResume($request));
    }

    public function docx(Request $request, ResumeDownloads $downloads): SymfonyResponse
    {
        return $downloads->docx($this->unsavedResume($request));
    }

    public function import(Request $request, ParseUploadedCv $parse): JsonResponse
    {
        return response()->json(['data' => $parse($request)]);
    }

    private function unsavedResume(Request $request): Resume
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            ...ResumeRules::content(),
        ]);

        return new Resume($validated);
    }
}
