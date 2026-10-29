<?php

namespace App\Http\Controllers;

use App\Actions\ParseUploadedCv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResumeImportController extends Controller
{
    public function store(Request $request, ParseUploadedCv $parse): RedirectResponse
    {
        $resume = $request->user()->resumes()->create([
            'title' => 'Imported '.now()->format('d M Y'),
            'data' => $parse($request),
        ]);

        return redirect()->route('resumes.edit', $resume);
    }
}
