<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResumeController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function ()
{
    Route::get('dashboard', fn () => redirect()->route('resumes.index'))->name('dashboard');

    Route::resource('resumes', ResumeController::class)
        ->only(['index', 'store', 'edit', 'destroy']);
});

require __DIR__.'/settings.php';
