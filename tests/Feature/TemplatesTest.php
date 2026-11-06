<?php

use App\Models\Resume;
use App\Models\User;
use App\Services\ResumeRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Smalot\PdfParser\Parser;

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

test('a long resume flows onto a second page without blank pages', function (string $template) {
    $html = app(ResumeRenderer::class)->render($template, '#0f766e', fullResumeData(jobs: 9), pdf: true);

    expect(pdfPages($html))->toBe(2);
})->with($templates);

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

test('every template keeps the summary in preview and downloaded pdf', function (string $template) {
    $data = fullResumeData();
    $data['personal']['summary'] = "Builds reliable applications.\nWorks with growing teams.\n\nAvailable for new opportunities.";
    $renderer = app(ResumeRenderer::class);

    foreach ([false, true] as $forPdf) {
        $html = $renderer->render($template, '#0f766e', $data, pdf: $forPdf);
        expect($html)->toContain('Builds reliable applications.', 'Works with growing teams.', 'Available for new opportunities.');
    }

    $pdf = Pdf::loadHTML($renderer->render($template, '#0f766e', $data, pdf: true))
        ->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    $text = (new Parser)->parseContent($pdf)->getText();

    expect($text)->toContain('Builds reliable applications.', 'Works with growing teams.', 'Available for new opportunities.');
})->with($templates);

test('classic starts content below the header and has no empty continuation pages', function () {
    $html = app(ResumeRenderer::class)->render('classic', '#0f766e', fullResumeData(jobs: 9), pdf: true);
    $pdf = Pdf::loadHTML($html)->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    $pages = (new Parser)->parseContent($pdf)->getPages();

    expect($pages)->toHaveCount(2)
        ->and($pages[0]->getText())->toContain('Richard Sanchez', 'Borcelle 1');

    foreach ($pages as $page) {
        expect(trim($page->getText()))->not->toBeEmpty();
    }
});

test('classic keeps oversized sidebar content in the left column across pages', function () {
    $data = fullResumeData();
    for ($i = 1; $i <= 40; $i++) {
        $data['skills'][] = ['id' => "extra{$i}", 'name' => "Additional skill {$i}", 'level' => 3];
    }

    $html = app(ResumeRenderer::class)->render('classic', '#0f766e', $data, pdf: true);
    $pdf = Pdf::loadHTML($html)->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    $document = (new Parser)->parseContent($pdf);
    expect($document->getText())->toContain('Additional skill 40', 'Goa University', 'Google Ads', 'Chess');

    $found = [];
    foreach ($document->getPages() as $page) {
        foreach ($page->getDataTm() as [$matrix, $text]) {
            foreach (['Additional skill 40', 'Google Ads', 'Konkani', 'Chess'] as $label) {
                if (str_contains($text, $label)) {
                    expect((float) $matrix[4])->toBeLessThan(190)
                        ->and((float) $matrix[5])->toBeGreaterThan(20)
                        ->and((float) $matrix[5])->toBeLessThan(822);
                    $found[$label] = true;
                }
            }
        }
    }
    expect(array_keys($found))->toContain('Additional skill 40', 'Google Ads', 'Konkani', 'Chess');
});

test('manual entry breaks start the selected company on a new page', function (string $template) {
    $data = fullResumeData();
    $data['experience'][1]['page_break_before'] = true;
    $html = app(ResumeRenderer::class)->render($template, '#0f766e', $data, pdf: true);
    $pdf = Pdf::loadHTML($html)->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    $pages = (new Parser)->parseContent($pdf)->getPages();

    expect($pages)->toHaveCount(2)
        ->and($pages[0]->getText())->toContain('Borcelle 1')->not->toContain('Borcelle 2')
        ->and($pages[1]->getText())->toContain('Borcelle 2');
})->with($templates);

test('manual section breaks start projects on a new page', function (string $template) {
    $data = fullResumeData();
    $data['settings']['page_break_before'] = ['projects'];
    $html = app(ResumeRenderer::class)->render($template, '#0f766e', $data, pdf: true);
    $pdf = Pdf::loadHTML($html)->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    $pages = (new Parser)->parseContent($pdf)->getPages();

    expect($pages)->toHaveCount(2)
        ->and($pages[0]->getText())->not->toContain('Brand Refresh')
        ->and($pages[1]->getText())->toContain('Brand Refresh');
})->with($templates);

test('short education entries keep the degree and institution on the same page', function (string $template) {
    $data = fullResumeData(jobs: 6);
    $data['education'] = [];
    for ($i = 0; $i < 10; $i++) {
        $data['education'][] = ['id' => "degree{$i}", 'degree' => "Course Number {$i}", 'institution' => "School Number {$i}",
            'start_date' => '2015-01', 'end_date' => '2017-01', 'score' => '', 'location' => '', 'description' => "Studied computer science.\nCompleted practical coursework.\nWorked on team assignments."];
    }
    $html = app(ResumeRenderer::class)->render($template, '#0f766e', $data, pdf: true);
    $pdf = Pdf::loadHTML($html)->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    $pages = (new Parser)->parseContent($pdf)->getPages();
    $found = [];
    foreach ($pages as $page) {
        $text = $page->getText();
        for ($i = 0; $i < 10; $i++) {
            if (str_contains($text, "Course Number {$i}")) {
                expect($text)->toContain("School Number {$i}");
                $found[] = $i;
            }
        }
    }
    expect($found)->toHaveCount(10);
})->with($templates);

test('every template preserves all content within printable A4 bounds', function (string $template, string $scenario) {
    $data = fullResumeData(jobs: $scenario === 'many jobs' ? 18 : 2);
    if ($scenario === 'long entry') {
        $data['experience'][0]['description'] = implode("\n", array_map(fn ($i) => "Unique bullet {$i} assembly.", range(1, 100)));
    }
    if (in_array($scenario, ['long sidebar', 'unrated sidebar', 'wrapped sidebar'])) {
        $data['skills'] = array_map(fn ($i) => ['id' => "skill{$i}", 'name' => "Unique skill {$i} assembly testing and quality assurance work", 'level' => 3], range(1, 40));
    }
    if ($scenario === 'unrated sidebar') {
        $data['settings']['show_levels']['skills'] = false;
    }
    if ($scenario === 'wrapped sidebar') {
        $data['skills'] = array_slice($data['skills'], 0, 18);
        foreach ($data['skills'] as &$skill) {
            $skill['name'] = substr($skill['name'], 0, strpos($skill['name'], 'assembly')).'assembly testing quality assurance workflows';
        }
        unset($skill);
    }
    $html = app(ResumeRenderer::class)->render($template, '#0f766e', $data, pdf: true);
    $bytes = Pdf::loadHTML($html)->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    $document = (new Parser)->parseContent($bytes);
    $text = $document->getText();
    expect($text)->toContain('Goa University', 'Google Ads', 'Chess', 'Estelle Darcy');
    if ($scenario === 'long entry') {
        foreach (range(1, 100) as $i) {
            expect($text)->toContain("Unique bullet {$i} ");
        }
    }
    if (in_array($scenario, ['long sidebar', 'unrated sidebar', 'wrapped sidebar'])) {
        foreach (range(1, $scenario === 'wrapped sidebar' ? 18 : 40) as $i) {
            expect(preg_replace('/\s+/', ' ', $text))->toContain("Unique skill {$i} ");
        }
    }
    foreach ($document->getPages() as $page) {
        expect(trim($page->getText()))->not->toBeEmpty();
        foreach ($page->getDataTm() as [$matrix, $label]) {
            if (trim($label) === '') {
                continue;
            }
            expect((float) $matrix[4], $label.' horizontal position')->toBeGreaterThanOrEqual(20)->toBeLessThan(575)
                ->and((float) $matrix[5], $label.' vertical position')->toBeGreaterThan(20)->toBeLessThan(822);
        }
    }
})->with($templates)->with(['many jobs', 'long entry', 'long sidebar', 'unrated sidebar', 'wrapped sidebar']);

test('ordinary sidebar content starts on page one without a manual break', function (string $template) {
    $data = fullResumeData();
    $data['skills'] = array_map(fn ($i) => ['id' => "s{$i}", 'name' => "Assembly and quality testing skill {$i}", 'level' => 3], range(1, 10));
    $bytes = Pdf::loadHTML(app(ResumeRenderer::class)->render($template, '#0f766e', $data, pdf: true))->setPaper('a4')->output();
    $pages = (new Parser)->parseContent($bytes)->getPages();
    expect($pages[0]->getText())->toContain('Assembly and quality testing skill 1');
})->with($templates);

test('manual sidebar breaks deliberately leave skills off the first page', function (string $template) {
    $data = fullResumeData();
    $data['settings']['page_break_before'] = ['skills'];
    $bytes = Pdf::loadHTML(app(ResumeRenderer::class)->render($template, '#0f766e', $data, pdf: true))->setPaper('a4')->output();
    $pages = (new Parser)->parseContent($bytes)->getPages();
    expect($pages)->toHaveCount(2)
        ->and($pages[0]->getText())->not->toContain('Branding')
        ->and($pages[1]->getText())->toContain('Branding');
})->with($templates);

test('PDF preview and download have matching printable pages for every template', function (string $template) {
    $data = fullResumeData(jobs: 9);
    $payload = ['title' => 'Print check', 'template' => $template, 'color' => '#0f766e', 'data' => $data];
    $preview = $this->postJson(route('build.preview'), $payload + ['format' => 'pdf'])
        ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $download = $this->postJson(route('build.pdf'), $payload)
        ->assertOk()->assertDownload('richard-sanchez_'.$template.'.pdf')->assertHeader('Content-Type', 'application/pdf');
    $parser = new Parser;
    $previewPages = $parser->parseContent($preview->getContent())->getPages();
    $downloadPages = $parser->parseContent($download->getContent())->getPages();
    $preview->assertHeader('X-PDF-Pages', (string) count($downloadPages));
    expect(count($previewPages))->toBe(count($downloadPages));
    foreach ($downloadPages as $i => $page) {
        expect($page->getText())->toBe($previewPages[$i]->getText());
        $size = $page->getDetails()['MediaBox'];
        expect((float) $size[2])->toBeBetween(595, 596)
            ->and((float) $size[3])->toBeBetween(841, 843);
    }
})->with($templates);

test('every template prints empty and sparse resumes without blank pages', function (string $template, bool $sparse) {
    $data = Resume::defaultData();
    if ($sparse) {
        $data['personal']['full_name'] = 'Sparse Resume';
        $data['projects'][] = ['id' => 'only', 'name' => 'Only Project', 'description' => 'Only project description.'];
    }
    $bytes = Pdf::loadHTML(app(ResumeRenderer::class)->render($template, '#0f766e', $data, pdf: true))->setPaper('a4')->output();
    $pages = (new Parser)->parseContent($bytes)->getPages();
    expect($pages)->toHaveCount(1)
        ->and($pages[0]->getText())->toContain($sparse ? 'Only Project' : ($template === 'classic' ? 'Your Name' : 'YOUR NAME'));
})->with($templates)->with([false, true]);

test('omitted null and blank sections render exactly like an empty section', function (string $template, string $section) {
    $data = fullResumeData();
    $data[$section] = [];
    // An empty section must not activate a stale saved page break.
    $data['settings']['page_break_before'] = [$section];
    $renderer = app(ResumeRenderer::class);
    foreach ([false, true] as $pdf) {
        $expected = $renderer->render($template, '#0f766e', $data, pdf: $pdf);
        foreach (['missing', 'null', 'blank'] as $state) {
            $variant = $data;
            if ($state === 'missing') {
                unset($variant[$section]);
            } elseif ($state === 'null') {
                $variant[$section] = null;
            } elseif ($section === 'hobbies') {
                $variant[$section] = ['', '   ', null];
            } else {
                $variant[$section] = [['id' => 'blank', 'name' => '  ', 'job_title' => '', 'company' => ' ', 'degree' => ' ', 'institution' => '', 'level' => 3, 'page_break_before' => true]];
            }
            expect($renderer->render($template, '#0f766e', $variant, pdf: $pdf))->toBe($expected);
        }
    }
})->with($templates)->with(Resume::SECTIONS);

test('each section can be printed on its own without other sections', function (string $template, string $section) {
    $full = fullResumeData();
    $data = ['personal' => ['full_name' => 'Single Section'], $section => $full[$section]];
    $bytes = Pdf::loadHTML(app(ResumeRenderer::class)->render($template, '#0f766e', $data, pdf: true))->setPaper('a4')->output();
    $pages = (new Parser)->parseContent($bytes)->getPages();
    $labels = ['experience' => 'Borcelle 1', 'education' => 'Goa University', 'skills' => 'Branding', 'tools' => 'Figma', 'projects' => 'Brand Refresh', 'certifications' => 'Google Ads', 'languages' => 'Konkani', 'hobbies' => 'Chess', 'references' => 'Estelle Darcy'];
    expect($pages)->toHaveCount(1)
        ->and($pages[0]->getText())->toContain($template === 'classic' ? 'Single Section' : 'SINGLE SECTION', $labels[$section]);
})->with($templates)->with(Resume::SECTIONS);

test('whitespace only personal fields do not create empty headings or contacts', function (string $template) {
    $data = Resume::defaultData();
    $renderer = app(ResumeRenderer::class);
    foreach ([false, true] as $pdf) {
        $empty = $renderer->render($template, '#0f766e', $data, pdf: $pdf);
        $data['personal'] = array_fill_keys(array_keys($data['personal']), " \n\t ");
        expect($renderer->render($template, '#0f766e', $data, pdf: $pdf))->toBe($empty);
        $data = Resume::defaultData();
    }
})->with($templates);

test('partially completed employment and education rows print without missing field errors', function (string $template) {
    $data = Resume::defaultData();
    $data['experience'] = [['id' => 'company', 'company' => 'Only Company'], ['id' => 'role', 'job_title' => 'Only Role']];
    $data['education'] = [['id' => 'school', 'institution' => 'Only School'], ['id' => 'degree', 'degree' => 'Only Degree']];
    $renderer = app(ResumeRenderer::class);
    foreach ([false, true] as $pdf) {
        expect($renderer->render($template, '#0f766e', $data, pdf: $pdf))->toContain('Only Company', 'Only Role', 'Only School', 'Only Degree');
    }
    $bytes = Pdf::loadHTML($renderer->render($template, '#0f766e', $data, pdf: true))->setPaper('a4')->output();
    expect((new Parser)->parseContent($bytes)->getText())->toContain('Only Company', 'Only Role', 'Only School', 'Only Degree');
})->with($templates);
