<?php

use App\Models\Resume;
use App\Support\ResumeFormat as F;

test('paragraphs keep single line breaks and split on blank lines', function () {
    $text = "First line.\nSecond line.\n\n\r\nNew paragraph.\n  Last line.  \n";

    expect(F::paragraphs($text))->toBe([
        ['First line.', 'Second line.'],
        ['New paragraph.', 'Last line.'],
    ]);
});

test('paragraphs of empty text is empty', function () {
    expect(F::paragraphs(''))->toBe([])
        ->and(F::paragraphs(null))->toBe([])
        ->and(F::paragraphs("\n\n  \n"))->toBe([]);
});

test('section order follows the saved order and fills in missing sections', function () {
    $data = ['settings' => ['section_order' => ['projects', 'bogus', 'experience', 'projects']]];

    expect(F::sectionOrder($data))->toBe([
        'projects', 'experience', 'education', 'skills', 'tools',
        'certifications', 'languages', 'hobbies', 'references',
    ])->and(F::sectionOrder($data, ['experience', 'references', 'projects']))
        ->toBe(['projects', 'experience', 'references']);
});

test('section order defaults when nothing is saved', function () {
    expect(F::sectionOrder([]))->toBe(Resume::SECTIONS);
});
