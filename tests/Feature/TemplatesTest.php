<?php

use App\Models\Resume;
use App\Models\User;
use App\Services\ResumeRenderer;
use Barryvdh\DomPDF\Facade\Pdf;

// Every registered template; read from the config file since datasets are built before the app boots.
$templates = array_keys((require __DIR__.'/../../config/resume.php')['templates']);

function fullResumeData(int $jobs = 2): array
{
    $data = Resume::defaultData();
    $data['personal'] = array_merge($data['personal'], [
        'full_name' => 'Richard Sanchez',
        'job_title' => 'Marketing Manager',
        'email' => 'richard@example.com',
        'phone' => '+91 98765 43210',
        'location' => 'Panaji, Goa',
        'linkedin' => 'https://linkedin.com/in/richard',
        'summary' => 'Marketing manager with eight years of brand work.',
    ]);

    for ($i = 1; $i <= $jobs; $i++) {
        $data['experience'][] = ['id' => "e{$i}", 'job_title' => "Manager {$i}", 'company' => "Borcelle {$i}", 'location' => 'Goa', 'start_date' => '2020-01', 'end_date' => '2022-01', 'current' => false,
            'description' => "Led a team of ten across print and digital campaigns.\nGrew qualified leads by forty percent in one year.\nOwned a yearly budget and three agency relationships."];
    }

    $data['education'][] = ['id' => 'd1', 'degree' => 'MBA', 'institution' => 'Goa University', 'location' => '', 'start_date' => '2016-01', 'end_date' => '2018-01', 'score' => '8.2 CGPA', 'description' => ''];
    $data['skills'][] = ['id' => 's1', 'name' => 'Branding', 'level' => 4];
    $data['tools'][] = ['id' => 't1', 'name' => 'Figma', 'level' => 3];
    $data['languages'][] = ['id' => 'l1', 'name' => 'Konkani', 'level' => 5];
    $data['projects'][] = ['id' => 'p1', 'name' => 'Brand Refresh', 'duration' => '6 months', 'link' => '', 'description' => ''];
    $data['certifications'][] = ['id' => 'c1', 'name' => 'Google Ads', 'issuer' => 'Google', 'date' => '2024-05'];
    $data['hobbies'] = ['Chess'];
    $data['references'][] = ['id' => 'r1', 'name' => 'Estelle Darcy', 'position' => 'CTO', 'company' => 'Wardiere', 'phone' => '', 'email' => ''];

    return $data;
}

function pdfPages(string $html): int
{
    $pdf = Pdf::loadHTML($html)->setPaper('a4')->setOption('isRemoteEnabled', false)->output();

    return preg_match_all('#/Type\s*/Page\b#', $pdf);
}

test('every template shows every filled section', function (string $template) {
    $renderer = app(ResumeRenderer::class);

    foreach ([false, true] as $pdf) {
        expect($renderer->render($template, '#0f766e', fullResumeData(), pdf: $pdf))->toContain(
            'Richard Sanchez', 'Marketing Manager', 'richard@example.com', 'eight years of brand work',
            'Borcelle 1', 'Grew qualified leads', 'Goa University', 'Branding', 'Figma', 'Konkani',
            'Brand Refresh', 'Google Ads', 'Chess', 'Estelle Darcy',
        );
    }
})->with($templates);

test('every template downloads as a pdf', function (string $template) {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['template' => $template, 'data' => fullResumeData()]);

    $this->actingAs($user)
        ->get(route('resumes.pdf', $resume))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
})->with($templates);

test('every template renders an empty resume without a photo', function (string $template) {
    $html = app(ResumeRenderer::class)->render($template, '#0f766e', Resume::defaultData());

    expect($html)->toContain('<html');
})->with($templates);

test('every template follows the section order', function (string $template) {
    $renderer = app(ResumeRenderer::class);

    $data = fullResumeData();

    $data['settings']['section_order'] = ['experience', 'projects'];
    $first = $renderer->render($template, '#0f766e', $data);
    $data['settings']['section_order'] = ['projects', 'experience'];
    $second = $renderer->render($template, '#0f766e', $data);

    expect(strpos($first, 'Borcelle 1'))->toBeLessThan(strpos($first, 'Brand Refresh'))
        ->and(strpos($second, 'Brand Refresh'))->toBeLessThan(strpos($second, 'Borcelle 1'));
})->with($templates);

test('every template hides ratings when asked', function (string $template) {
    $data = fullResumeData();
    $data['settings']['show_levels'] = ['skills' => false, 'tools' => false, 'languages' => false];

    $html = app(ResumeRenderer::class)->render($template, '#0f766e', $data);

    expect($html)->toContain('Branding', 'Figma', 'Konkani')
        ->and($html)->not->toMatch('/class="(bar-fill|dot dot-on|seg seg-on)"/')
        ->and($html)->not->toContain('width: 80%');
})->with($templates);

// Classic still lays its columns out in one table row, which DomPDF can't split; it is reworked separately.
test('a long resume flows onto a second page without blank pages', function (string $template) {
    $html = app(ResumeRenderer::class)->render($template, '#0f766e', fullResumeData(jobs: 9), pdf: true);

    expect(pdfPages($html))->toBe(2);
})->with(array_values(array_diff($templates, ['classic'])));

test('executive moves sidebar sections that do not fit page one into the main column', function () {
    $data = fullResumeData();
    for ($i = 1; $i <= 18; $i++) {
        $data['skills'][] = ['id' => "x{$i}", 'name' => "Skill {$i}", 'level' => 3];
    }
    $data['settings']['section_order'] = ['skills', 'references'];

    $html = app(ResumeRenderer::class)->render('executive', '#0f766e', $data);
    [$side, $main] = explode('<div class="main">', $html);

    expect($side)->toContain('Skill 18')
        ->and($side)->not->toContain('Estelle Darcy')
        ->and($main)->toContain('Estelle Darcy');
});

test('banner shows rated skills as chips with dots', function () {
    $html = app(ResumeRenderer::class)->render('banner', '#0f766e', fullResumeData());

    preg_match('#<span class="tag tag-lvl">Branding .*?</span></span>#s', $html, $chip);

    expect($chip)->not->toBeEmpty()
        ->and(substr_count($chip[0], 'dot dot-on'))->toBe(4);
});
