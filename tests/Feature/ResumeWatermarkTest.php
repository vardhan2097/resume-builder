<?php

use App\Models\Resume;
use App\Services\ResumeDocxBuilder;
use App\Support\ResumeWatermark;
use Smalot\PdfParser\Parser;

test('pdf previews and downloads brand every page with MiResume', function (string $template) {
    config(['app.name' => 'Career & Co', 'app.url' => 'https://resume.example.com/builder']);
    $data = Resume::defaultData();
    $data['personal']['full_name'] = 'Watermark Person';
    $data['experience'] = [['id' => 'job', 'job_title' => 'Engineer', 'company' => 'Example']];
    $data['projects'] = [['id' => 'project', 'name' => 'Second Page Project']];
    $data['settings']['page_break_before'] = ['projects'];
    $payload = ['title' => 'Watermark', 'template' => $template, 'color' => '#0f766e', 'data' => $data];

    foreach (['build.preview', 'build.pdf'] as $route) {
        $response = $this->postJson(route($route), $payload + ['format' => 'pdf'])->assertOk();
        $pages = (new Parser)->parseContent($response->getContent())->getPages();
        expect($pages)->toHaveCount(2);
        foreach ($pages as $page) {
            expect($page->getText())->toContain('Crafted with MiResume', 'Your story. Your next chapter.')->not->toContain('Career & Co', 'resume.example.com');
        }
        expect($pages[0]->getText())->toContain($template === 'classic' ? 'Watermark Person' : 'WATERMARK PERSON', 'Example')
            ->and($pages[1]->getText())->toContain('Second Page Project');
    }
})->with(['classic', 'executive', 'banner']);

test('word exports repeat MiResume in a footer', function () {
    config(['app.name' => 'Career & <Co>', 'app.url' => 'https://resume.example.com']);
    $resume = new Resume(['template' => 'classic', 'color' => '#7c3aed', 'data' => Resume::defaultData()]);
    $path = app(ResumeDocxBuilder::class)->build($resume);
    try {
        $zip = new ZipArchive;
        $zip->open($path);
        $footer = $zip->getFromName('word/footer1.xml');
        $document = $zip->getFromName('word/document.xml');
        $zip->close();

        $xml = new DOMDocument;
        expect($xml->loadXML($footer))->toBeTrue()
            ->and($xml->textContent)->toBe('Crafted with MiResumeYour story. Your next chapter.')
            ->and($footer)->toContain('w:val="7c3aed"')
            ->and($document)->toContain('w:footerReference w:type="default"');
    } finally {
        unlink($path);
    }
});

test('watermark stays MiResume regardless of application settings', function () {
    config(['app.name' => 'Other Project', 'app.url' => 'https://other.example.com']);
    expect(ResumeWatermark::text())->toBe('Crafted with MiResume');
});
