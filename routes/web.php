<?php

use App\Http\Controllers\GuestResumeController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeImportController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Not "/build": that is Vite's public/build asset folder, which the web server would serve instead.
Route::get('builder', [GuestResumeController::class, 'show'])->name('build');

Route::post('builder/preview', [GuestResumeController::class, 'preview'])
    ->middleware('throttle:guest-preview')
    ->name('build.preview');

Route::middleware('throttle:guest-export')->group(function () {
    Route::post('builder/pdf', [GuestResumeController::class, 'pdf'])->name('build.pdf');
    Route::post('builder/docx', [GuestResumeController::class, 'docx'])->name('build.docx');
});

Route::post('builder/import', [GuestResumeController::class, 'import'])
    ->middleware('throttle:guest-import')
    ->name('build.import');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => redirect()->route('resumes.index'))->name('dashboard');

    Route::resource('resumes', ResumeController::class)
        ->only(['index', 'store', 'edit', 'update', 'destroy']);

    Route::post('resumes/{resume}/preview', [ResumeController::class, 'preview'])
        ->name('resumes.preview');

    Route::get('resumes/{resume}/pdf', [ResumeController::class, 'pdf'])
        ->name('resumes.pdf');

    Route::get('resumes/{resume}/docx', [ResumeController::class, 'docx'])
        ->name('resumes.docx');

    Route::post('resumes/{resume}/photo', [ResumeController::class, 'uploadPhoto'])
        ->name('resumes.photo.upload');

    Route::get('resumes/{resume}/photo', [ResumeController::class, 'showPhoto'])
        ->name('resumes.photo.show');

    Route::get('resumes/{resume}/photo/original', [ResumeController::class, 'showOriginalPhoto'])
        ->name('resumes.photo.original');

    Route::put('resumes/{resume}/photo/frame', [ResumeController::class, 'framePhoto'])
        ->name('resumes.photo.frame');

    Route::delete('resumes/{resume}/photo', [ResumeController::class, 'deletePhoto'])
        ->name('resumes.photo.delete');

    Route::post('resumes/claim', [ResumeController::class, 'claim'])
        ->name('resumes.claim');

    Route::post('resumes/import', [ResumeImportController::class, 'store'])
        ->name('resumes.import');
});

require __DIR__.'/settings.php';
