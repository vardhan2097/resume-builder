<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeImportController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function ()
{
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

    Route::post('resumes/import', [ResumeImportController::class, 'store'])
        ->name('resumes.import');
});

require __DIR__.'/settings.php';
