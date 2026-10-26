<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResumeController;

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
});

require __DIR__.'/settings.php';
