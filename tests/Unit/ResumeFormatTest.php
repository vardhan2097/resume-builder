<?php

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
