<?php

use App\Models\Resume;
use App\Models\User;
use App\Services\ResumeDocxBuilder;
use App\Services\ResumeRenderer;
use Smalot\PdfParser\Parser;

test('hidden sections remain saved but are omitted from every template and download', function () {
    $user = User::factory()->create();
    $data = Resume::defaultData();
    $data['personal']['full_name'] = 'Priya Mehta';
    $data['personal']['summary'] = 'Hidden summary content';
    $data['experience'] = [[
        'id' => 'work-1', 'job_title' => 'Private Role', 'company' => 'Hidden Company',
        'location' => '', 'start_date' => '', 'end_date' => '', 'current' => false,
        'description' => 'Hidden work details',
    ]];
    $data['skills'] = [['id' => 'skill-1', 'name' => 'Visible Skill', 'level' => 3]];
    $data['settings']['hidden_sections'] = ['experience', 'summary'];
    $resume = $user->resumes()->create(['title' => 'Test', 'data' => $data]);

    $this->actingAs($user)->put(route('resumes.update', $resume), [
        'title' => 'Test', 'template' => 'classic', 'color' => '#0f766e', 'data' => $data,
    ])->assertSessionHasNoErrors();

    $resume = $resume->fresh();
    expect($resume->data['experience'][0]['company'])->toBe('Hidden Company')
        ->and($resume->data['personal']['summary'])->toBe('Hidden summary content')
        ->and($resume->data['settings']['hidden_sections'])->toBe(['experience', 'summary']);

    $renderer = app(ResumeRenderer::class);
    foreach (array_keys(config('resume.templates')) as $template) {
        $html = $renderer->render($template, '#0f766e', $resume->data);
        expect($html)->toContain('Visible Skill')->not->toContain('Hidden Company', 'Hidden summary content');
    }

    $pdf = $renderer->document('classic', '#0f766e', $resume->data)->output();
    $pdfText = (new Parser)->parseContent($pdf)->getText();
    expect($pdfText)->toContain('Priya Mehta', 'Visible Skill')->not->toContain('Hidden Company', 'Hidden summary content', 'Aarav Sharma');

    $path = app(ResumeDocxBuilder::class)->build($resume);
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $document = $zip->getFromName('word/document.xml');
    $zip->close();
    unlink($path);
    expect($document)->toContain('Priya Mehta', 'Visible Skill')->not->toContain('Hidden Company', 'Hidden summary content', 'Aarav Sharma');

    $data['settings']['hidden_sections'] = [];
    expect($renderer->render('classic', '#0f766e', $data))->toContain('Hidden Company', 'Hidden summary content');
});

test('blank downloads do not insert a placeholder name', function () {
    $data = Resume::defaultData();
    $resume = new Resume(['title' => 'Test', 'template' => 'classic', 'color' => '#0f766e', 'data' => $data]);
    $renderer = app(ResumeRenderer::class);

    foreach (array_keys(config('resume.templates')) as $template) {
        expect($renderer->render($template, '#0f766e', $data))->not->toContain('Your Name', 'Aarav Sharma');
    }

    $pdf = $renderer->document('classic', '#0f766e', $data)->output();
    expect((new Parser)->parseContent($pdf)->getText())->not->toContain('Your Name', 'Aarav Sharma');

    $path = app(ResumeDocxBuilder::class)->build($resume);
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $document = $zip->getFromName('word/document.xml');
    $zip->close();
    unlink($path);
    expect($document)->not->toContain('Your Name', 'Aarav Sharma');
});

test('guest preview and downloads respect hidden sections', function () {
    $data = Resume::defaultData();
    $data['personal']['full_name'] = 'Priya Mehta';
    $data['personal']['summary'] = 'Private guest summary';
    $data['settings']['hidden_sections'] = ['summary'];
    $payload = ['title' => 'Guest', 'template' => 'banner', 'color' => '#0f766e', 'data' => $data];

    $this->postJson(route('build.preview'), $payload)->assertOk()->assertDontSee('Private guest summary');
    $pdf = $this->postJson(route('build.pdf'), $payload)->assertOk();
    expect((new Parser)->parseContent($pdf->getContent())->getText())
        ->toContain('PRIYA MEHTA')->not->toContain('Private guest summary', 'Aarav Sharma');

    $docx = $this->postJson(route('build.docx'), $payload)->assertOk();
    $zip = new ZipArchive;
    expect($zip->open($docx->baseResponse->getFile()->getPathname()))->toBeTrue();
    $document = $zip->getFromName('word/document.xml');
    $zip->close();
    expect($document)->toContain('Priya Mehta')->not->toContain('Private guest summary', 'Aarav Sharma');
});
