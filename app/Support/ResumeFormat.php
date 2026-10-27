<?php

namespace App\Support;

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
