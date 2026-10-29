<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateResumeRequest;
use App\Models\Resume;
use App\Services\ResumeDownloads;
use App\Services\ResumeRenderer;
use App\Support\ResumeRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Save a resume built in guest mode (sent from the browser's localStorage) to the account.
     */
    public function claim(Request $request): RedirectResponse
    {
        $resume = $request->user()->resumes()->create(
            $request->validate(ResumeRules::withTitle())
        );

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
                'data' => Resume::withDefaults($resume->data),
                'photo_url' => $resume->photo_path
                    ? route('resumes.photo.show', $resume).'?v='.md5($resume->photo_path)
                    : null,
                'photo_original_url' => $resume->photo_original_path
                    ? route('resumes.photo.original', $resume).'?v='.md5($resume->photo_original_path)
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

    public function pdf(Resume $resume, ResumeDownloads $downloads): SymfonyResponse
    {
        Gate::authorize('update', $resume);

        return $downloads->pdf($resume);
    }

    public function docx(Resume $resume, ResumeDownloads $downloads): BinaryFileResponse
    {
        Gate::authorize('update', $resume);

        return $downloads->docx($resume);
    }

    public function uploadPhoto(Request $request, Resume $resume): RedirectResponse
    {
        Gate::authorize('update', $resume);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ...self::cropRules(),
        ]);

        // Keep a bounded-size original so the user can re-frame later without re-uploading.
        $original = (string) $this->images()
            ->read($request->file('photo')->getRealPath())
            ->scaleDown(1600, 1600)
            ->toJpeg(90);

        $originalPath = 'photos/'.$resume->id.'-'.Str::random(16).'-original.jpg';
        Storage::disk('local')->put($originalPath, $original);

        if ($resume->photo_original_path) {
            Storage::disk('local')->delete($resume->photo_original_path);
        }

        $resume->update(['photo_original_path' => $originalPath]);

        $this->storeFramedPhoto($resume, $original, $this->crop($request));

        return back();
    }

    public function framePhoto(Request $request, Resume $resume): RedirectResponse
    {
        Gate::authorize('update', $resume);

        $request->validate([
            ...self::cropRules(),
            'crop' => ['required', 'array'],
        ]);

        abort_unless($resume->photo_original_path && Storage::disk('local')->exists($resume->photo_original_path), 404);

        $this->storeFramedPhoto($resume, Storage::disk('local')->get($resume->photo_original_path), $this->crop($request));

        return back();
    }

    public function showPhoto(Resume $resume): StreamedResponse
    {
        Gate::authorize('update', $resume);

        abort_unless($resume->photo_path && Storage::disk('local')->exists($resume->photo_path), 404);

        return Storage::disk('local')->response($resume->photo_path);
    }

    public function showOriginalPhoto(Resume $resume): StreamedResponse
    {
        Gate::authorize('update', $resume);

        abort_unless($resume->photo_original_path && Storage::disk('local')->exists($resume->photo_original_path), 404);

        return Storage::disk('local')->response($resume->photo_original_path);
    }

    public function deletePhoto(Resume $resume): RedirectResponse
    {
        Gate::authorize('update', $resume);

        Storage::disk('local')->delete(array_filter([$resume->photo_path, $resume->photo_original_path]));
        $resume->update(['photo_path' => null, 'photo_original_path' => null]);

        return back();
    }

    /**
     * Crop is a square given as fractions of the source image: x/y of the top-left
     * corner (relative to width/height) and size (relative to width).
     *
     * @return array<string, list<string>>
     */
    private static function cropRules(): array
    {
        return [
            'crop' => ['nullable', 'array'],
            'crop.x' => ['required_with:crop', 'numeric', 'between:0,1'],
            'crop.y' => ['required_with:crop', 'numeric', 'between:0,1'],
            'crop.size' => ['required_with:crop', 'numeric', 'gt:0', 'max:1'],
        ];
    }

    /**
     * The validated crop (see cropRules()) as numbers, or null when none was sent.
     *
     * @return array{x: float, y: float, size: float}|null
     */
    private function crop(Request $request): ?array
    {
        if (! $request->filled('crop')) {
            return null;
        }

        return [
            'x' => $request->float('crop.x'),
            'y' => $request->float('crop.y'),
            'size' => $request->float('crop.size'),
        ];
    }

    /**
     * @param  array{x: float, y: float, size: float}|null  $crop
     */
    private function storeFramedPhoto(Resume $resume, string $source, ?array $crop): void
    {
        $image = $this->images()->read($source);

        if ($crop) {
            $width = $image->width();
            $height = $image->height();

            $size = max(1, min((int) round($crop['size'] * $width), $width, $height));
            $x = min((int) round($crop['x'] * $width), $width - $size);
            $y = min((int) round($crop['y'] * $height), $height - $size);

            $image->crop($size, $size, $x, $y);
        }

        $image->cover(400, 400);

        $path = 'photos/'.$resume->id.'-'.Str::random(16).'.jpg';
        Storage::disk('local')->put($path, (string) $image->toJpeg(85));

        if ($resume->photo_path) {
            Storage::disk('local')->delete($resume->photo_path);
        }

        $resume->update(['photo_path' => $path]);
    }

    private function images(): ImageManager
    {
        return new ImageManager(new Driver);
    }
}
