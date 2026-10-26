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
use App\Services\ResumeDocxBuilder;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

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
                'photo_url' => $resume->photo_path
                    ? route('resumes.photo.show', $resume).'?v='.md5($resume->photo_path)
                    : null,
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
            photoPath: $resume->photo_path,
        ));
    }

    public function pdf(Resume $resume, ResumeRenderer $renderer)
    {
        Gate::authorize('update', $resume);

        $html = $renderer->render($resume->template, $resume->color, $resume->data, pdf: true, photoPath: $resume->photo_path,);

        $name = $resume->data['personal']['full_name'] ?? '' ?: $resume->title;
        $filename = Str::slug($name).'-resume.pdf';

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->download($filename);
    }


    public function docx(Resume $resume, ResumeDocxBuilder $builder)
    {
        Gate::authorize('update', $resume);

        $path = $builder->build($resume);

        $name = $resume->data['personal']['full_name'] ?? '' ?: $resume->title;

        return response()
            ->download($path, Str::slug($name).'-resume.docx')
            ->deleteFileAfterSend(true);
    }

    public function uploadPhoto(Request $request, Resume $resume): RedirectResponse
    {
        Gate::authorize('update', $resume);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $image = (new ImageManager(new Driver()))
            ->read($request->file('photo')->getRealPath())
            ->cover(400, 400);

        $path = 'photos/'.$resume->id.'-'.Str::random(16).'.jpg';

        Storage::disk('local')->put($path, (string) $image->toJpeg(85));

        if ($resume->photo_path) {
            Storage::disk('local')->delete($resume->photo_path);
        }

        $resume->update(['photo_path' => $path]);

        return back();
    }

    public function showPhoto(Resume $resume)
    {
        Gate::authorize('update', $resume);

        abort_unless($resume->photo_path && Storage::disk('local')->exists($resume->photo_path), 404);

        return Storage::disk('local')->response($resume->photo_path);
    }

    public function deletePhoto(Resume $resume): RedirectResponse
    {
        Gate::authorize('update', $resume);

        if ($resume->photo_path) {
            Storage::disk('local')->delete($resume->photo_path);
            $resume->update(['photo_path' => null]);
        }

        return back();
    }
}
