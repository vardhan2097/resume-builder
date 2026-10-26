<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\UpdateResumeRequest;
use App\Services\ResumeRenderer;
use Illuminate\Http\Response as HttpResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ResumeController extends Controller
{
    public function index(Request $request): Response
    {
        $resumes = $request->user()
            ->resumes()
            ->latest('updated_at')
            ->get(['id', 'title', 'template', 'updated_at']);

        return Inertia::render('resumes/Index', [
            'resumes' => $resumes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resume = $request->user()->resumes()->create([
            'title' => 'Untitled Resume',
        ]);

        return redirect()->route('resumes.edit', $resume);
    }

    public function edit(Resume $resume): Response
    {
        Gate::authorize('update', $resume);

        return Inertia::render('resumes/Edit', [
            'resume' => [
                'id' => $resume->id,
                'title' => $resume->title,
                'template' => $resume->template,
                'color' => $resume->color,
                'data' => array_replace(Resume::defaultData(), $resume->data ?? []),
            ],
            'templates' => config('resume.templates'),
        ]);
    }

    public function update(UpdateResumeRequest $request, Resume $resume): RedirectResponse
    {
        $resume->update($request->validated());

        return back();
    }

    public function destroy(Resume $resume): RedirectResponse
    {
        Gate::authorize('delete', $resume);

        $resume->delete();

        return redirect()->route('resumes.index');
    }

    public function preview(Request $request, Resume $resume, ResumeRenderer $renderer): HttpResponse
    {
        Gate::authorize('update', $resume);

        return response($renderer->render(
            $request->input('template'),
            $request->input('color'),
            $request->input('data', []),
        ));
    }

    public function pdf(Resume $resume, ResumeRenderer $renderer)
    {
        Gate::authorize('update', $resume);

        $html = $renderer->render($resume->template, $resume->color, $resume->data, pdf: true);

        $name = $resume->data['personal']['full_name'] ?? '' ?: $resume->title;
        $filename = Str::slug($name).'-resume.pdf';

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->download($filename);
    }

}
