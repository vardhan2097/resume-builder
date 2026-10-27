<?php

namespace App\Support;

use App\Models\Resume;
use Carbon\Carbon;

class ResumeFormat
{
    public static function month(?string $yearMonth): string
    {
        if (! $yearMonth) {
            return '';
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $yearMonth.'-01')->format('M Y');
        } catch (\Throwable) {
            return $yearMonth;
        }
    }

    public static function range(?string $start, ?string $end, bool $current = false): string
    {
        $from = self::month($start);
        $to = $current ? 'Present' : self::month($end);

        if ($from && $to) {
            return "{$from} – {$to}";
        }

        return $from ?: $to;
    }

    public static function lines(?string $text): array
    {
        $lines = preg_split('/\R/', (string) $text);

        return array_values(array_filter(array_map('trim', $lines)));
    }

    /**
     * Free text split the way the user typed it: blank lines separate paragraphs,
     * single newlines become line breaks within a paragraph.
     *
     * @return list<list<string>>
     */
    public static function paragraphs(?string $text): array
    {
        $blocks = preg_split('/\R\s*\R/', trim((string) $text));

        return array_values(array_filter(array_map(self::lines(...), $blocks)));
    }

    /**
     * Whether a rated section (skills, tools, languages) should show its 1–5 levels.
     */
    public static function showLevels(array $data, string $section): bool
    {
        return (bool) ($data['settings']['show_levels'][$section] ?? true);
    }

    /**
     * The user's section order, limited to $only (e.g. the sections one column of a
     * template shows). Unknown or repeated keys are dropped and any section missing
     * from the saved order is appended in its default position.
     *
     * @param  list<string>|null  $only
     * @return list<string>
     */
    public static function sectionOrder(array $data, ?array $only = null): array
    {
        $saved = array_filter((array) ($data['settings']['section_order'] ?? []), 'is_string');
        $order = array_values(array_unique([
            ...array_intersect($saved, Resume::SECTIONS),
            ...Resume::SECTIONS,
        ]));

        return $only === null ? $order : array_values(array_intersect($order, $only));
    }

    public static function filled(mixed $items, string ...$keys): array
    {
        return array_values(array_filter((array) $items, function ($item) use ($keys) {
            foreach ($keys as $key) {
                if (trim((string) ($item[$key] ?? '')) !== '') {
                    return true;
                }
            }

            return false;
        }));
    }

    public static function strings(mixed $items): array
    {
        return array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            (array) $items
        )));
    }

    public static function initials(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        $letters = array_map(fn ($word) => mb_substr($word, 0, 1), array_slice($words, 0, 2));

        return mb_strtoupper(implode('', $letters)) ?: 'CV';
    }
}
