<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

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
            'resume' => $resume,
        ]);
    }

    public function destroy(Resume $resume): RedirectResponse
    {
        Gate::authorize('delete', $resume);

        $resume->delete();

        return redirect()->route('resumes.index');
    }

}
