<?php

namespace App\Support;

use App\Models\Resume;
use Carbon\Carbon;

/**
 * @phpstan-import-type ResumeData from Resume
 */
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

    /**
     * Non-empty, trimmed lines of a free-text field.
     *
     * @return list<string>
     */
    public static function lines(?string $text): array
    {
        $lines = preg_split('/\R/', (string) $text) ?: [];

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
        $blocks = preg_split('/\R\s*\R/', trim((string) $text)) ?: [];

        return array_values(array_filter(array_map(self::lines(...), $blocks)));
    }

    /**
     * Whether a rated section (skills, tools, languages) should show its 1–5 levels.
     *
     * @param  ResumeData  $data
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
     * @param  array<string, mixed>  $data  resume data, defaulted or raw
     * @param  list<string>|null  $only
     * @return list<value-of<Resume::SECTIONS>>
     */
    public static function sectionOrder(array $data, ?array $only = null): array
    {
        // Rank each section by its first position in the saved order; unsaved ones
        // rank last and, since usort is stable, keep their default order.
        $saved = $data['settings']['section_order'] ?? [];
        $rank = [];
        foreach (is_array($saved) ? array_values($saved) : [] as $i => $key) {
            if (is_string($key)) {
                $rank[$key] ??= $i;
            }
        }

        $order = Resume::SECTIONS;
        usort($order, fn (string $a, string $b) => ($rank[$a] ?? PHP_INT_MAX) <=> ($rank[$b] ?? PHP_INT_MAX));

        return $only === null ? $order : array_values(array_intersect($order, $only));
    }

    /**
     * Items where at least one of $keys has a non-blank value.
     *
     * Accepts any value so a missing or null section still renders as empty.
     *
     * @return list<array<string, mixed>>
     */
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

    /**
     * Trimmed, non-empty strings from a list of scalars.
     *
     * @return list<string>
     */
    public static function strings(mixed $items): array
    {
        return array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            (array) $items
        )));
    }

    public static function initials(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = array_map(fn ($word) => mb_substr($word, 0, 1), array_slice($words, 0, 2));

        return mb_strtoupper(implode('', $letters)) ?: 'CV';
    }

    /**
     * Everything a template needs, filtered so empty entries are skipped:
     * `@php(extract(F::prepare($data)))` at the top of a template.
     *
     * @param  ResumeData  $data
     * @return array<string, mixed>
     */
    public static function prepare(array $data): array
    {
        return [
            'p' => $data['personal'],
            'contacts' => self::contacts($data['personal']),
            'experience' => self::filled($data['experience'], 'job_title', 'company'),
            'education' => self::filled($data['education'], 'degree', 'institution'),
            'skills' => self::filled($data['skills'], 'name'),
            'tools' => self::filled($data['tools'], 'name'),
            'languages' => self::filled($data['languages'], 'name'),
            'projects' => self::filled($data['projects'], 'name'),
            'certifications' => self::filled($data['certifications'], 'name'),
            'hobbies' => self::strings($data['hobbies']),
            'references' => self::filled($data['references'], 'name'),
        ];
    }

    /**
     * Filled contact details as [type, value, label], in display order.
     *
     * @param  array<string, string>  $personal
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function contacts(array $personal): array
    {
        $fields = [
            'phone' => 'Phone',
            'email' => 'Email',
            'location' => 'Address',
            'website' => 'Website',
            'linkedin' => 'LinkedIn',
            'github' => 'GitHub',
        ];

        $contacts = [];

        foreach ($fields as $key => $label) {
            $value = trim((string) ($personal[$key] ?? ''));

            if ($value !== '') {
                $contacts[] = [$key, $value, $label];
            }
        }

        return $contacts;
    }

    /** A URL without the scheme and "www.", for display. */
    public static function shortUrl(string $url): string
    {
        return rtrim(preg_replace('#^(https?://)?(www\.)?#i', '', $url), '/');
    }

    /**
     * Small single-colour icon as an SVG data URI, usable in <img> by both the
     * browser preview and DomPDF (which supports SVG images, not icon fonts).
     */
    public static function icon(string $name, string $color = '#374151'): string
    {
        $paths = [
            'phone' => '<path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/>',
            'email' => '<path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5z"/>',
            'location' => '<path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>',
            'website' => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm6.9 6h-2.95a15.6 15.6 0 0 0-1.38-3.56A8 8 0 0 1 18.9 8zM12 4.04A14 14 0 0 1 13.91 8h-3.82A14 14 0 0 1 12 4.04zM4.26 14a8 8 0 0 1 0-4h3.38a16.5 16.5 0 0 0 0 4zm.82 2h2.95a15.6 15.6 0 0 0 1.38 3.56A8 8 0 0 1 5.08 16zm2.95-8H5.08a8 8 0 0 1 4.33-3.56A15.6 15.6 0 0 0 8.03 8zM12 19.96A14 14 0 0 1 10.09 16h3.82A14 14 0 0 1 12 19.96zM14.34 14H9.66a14.7 14.7 0 0 1 0-4h4.68a14.7 14.7 0 0 1 0 4zm.25 5.56A15.6 15.6 0 0 0 15.97 16h2.95a8 8 0 0 1-4.33 3.56zM16.36 14a16.5 16.5 0 0 0 0-4h3.38a8 8 0 0 1 0 4z"/>',
            'linkedin' => '<path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zM8.3 18.3V10H5.7v8.3zM7 8.9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm11.3 9.4v-4.6c0-2.4-1.3-3.6-3.1-3.6a2.7 2.7 0 0 0-2.4 1.3V10h-2.6v8.3h2.6v-4.4c0-1.2.2-2.3 1.7-2.3s1.5 1.4 1.5 2.4v4.3z"/>',
            'github' => '<path d="M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.7c-2.78.6-3.37-1.34-3.37-1.34a2.65 2.65 0 0 0-1.11-1.46c-.91-.62.07-.6.07-.6a2.1 2.1 0 0 1 1.53 1.03 2.13 2.13 0 0 0 2.91.83 2.14 2.14 0 0 1 .63-1.34c-2.22-.25-4.56-1.11-4.56-4.94a3.87 3.87 0 0 1 1.03-2.68 3.6 3.6 0 0 1 .1-2.65s.84-.27 2.75 1.02a9.47 9.47 0 0 1 5 0c1.91-1.3 2.75-1.02 2.75-1.02a3.6 3.6 0 0 1 .1 2.65 3.87 3.87 0 0 1 1.03 2.68c0 3.84-2.34 4.69-4.57 4.93a2.39 2.39 0 0 1 .68 1.86v2.75c0 .27.18.58.69.48A10 10 0 0 0 12 2z"/>',
            'user' => '<path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-4.42 0-8 2.24-8 5v3h16v-3c0-2.76-3.58-5-8-5z"/>',
            'briefcase' => '<path d="M14 6V4h-4v2zM4 8v11h16V8zm16-2a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2z"/>',
            'education' => '<path d="M12 3 1 9l11 6 9-4.91V17h2V9zM5 13.18v4L12 21l7-3.82v-4L12 17z"/>',
            'star' => '<path d="m12 17.27 6.18 3.73-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>',
            'tools' => '<path d="m22.7 19-9.1-9.1a6.4 6.4 0 0 0-1.5-6.9 6.6 6.6 0 0 0-7.4-1.3L9 6 6 9 1.6 4.7a6.6 6.6 0 0 0 1.3 7.4 6.4 6.4 0 0 0 6.9 1.5l9.1 9.1a1 1 0 0 0 1.4 0l2.3-2.3a1 1 0 0 0 .1-1.4z"/>',
            'project' => '<path d="M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8z"/>',
            'award' => '<path d="M12 2a7 7 0 0 0-4 12.74V22l4-2 4 2v-7.26A7 7 0 0 0 12 2zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>',
            'language' => '<path d="m12.87 15.07-2.54-2.51.03-.03A17.5 17.5 0 0 0 14.07 6H17V4h-7V2H8v2H1v2h11.17C11.5 7.92 10.44 9.75 9 11.35A15.6 15.6 0 0 1 6.69 8h-2c.73 1.63 1.73 3.17 2.98 4.56l-5.09 5.02L4 19l5-5 3.11 3.11zM18.5 10h-2L12 22h2l1.12-3h4.75L21 22h2zm-2.62 7 1.62-4.33L19.12 17z"/>',
            'heart' => '<path d="m12 21.35-1.45-1.32C5.4 15.36 2 12.28 2 8.5A5.5 5.5 0 0 1 7.5 3 6 6 0 0 1 12 5.09 6 6 0 0 1 16.5 3 5.5 5.5 0 0 1 22 8.5c0 3.78-3.4 6.86-8.55 11.54z"/>',
            'people' => '<path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05A4.2 4.2 0 0 1 17 16.5V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
            'calendar' => '<path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V9h14z"/>',
        ];

        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#374151';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="'.$color.'">'.($paths[$name] ?? $paths['star']).'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** Colour mixed with white; $amount 0 = colour, 1 = white. For tinted backgrounds. */
    public static function tint(string $hex, float $amount): string
    {
        if (! preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $m)) {
            return '#f3f4f6';
        }

        $channels = array_map(
            fn ($c) => (int) round(hexdec($c) + (255 - hexdec($c)) * $amount),
            str_split($m[1], 2),
        );

        return vsprintf('#%02x%02x%02x', $channels);
    }

    /** Colour mixed with black; $amount 0 = colour, 1 = black. */
    public static function shade(string $hex, float $amount): string
    {
        if (! preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $m)) {
            return '#111827';
        }

        $channels = array_map(fn ($c) => (int) round(hexdec($c) * (1 - $amount)), str_split($m[1], 2));

        return vsprintf('#%02x%02x%02x', $channels);
    }

    /**
     * Bundled open-licence fonts (public/fonts/resume, OFL). DomPDF only knows
     * normal/bold/italic, so lighter and semibold cuts are their own families.
     */
    private const FONTS = [
        'Lato' => [['Lato-Regular.ttf', 'normal', 'normal'], ['Lato-Bold.ttf', 'bold', 'normal'], ['Lato-Italic.ttf', 'normal', 'italic']],
        'Lato Light' => [['Lato-Light.ttf', 'normal', 'normal']],
        'Montserrat' => [['Montserrat-Regular.ttf', 'normal', 'normal'], ['Montserrat-Bold.ttf', 'bold', 'normal']],
        'Montserrat Light' => [['Montserrat-Light.ttf', 'normal', 'normal']],
        'Montserrat SemiBold' => [['Montserrat-SemiBold.ttf', 'normal', 'normal']],
        'Lora' => [['Lora-Regular.ttf', 'normal', 'normal'], ['Lora-Bold.ttf', 'bold', 'normal'], ['Lora-Italic.ttf', 'normal', 'italic']],
    ];

    /**
     * @font-face rules for the given families. The PDF reads the files from disk
     * (DomPDF has remote loading off); the browser preview loads them by URL.
     *
     * @param  list<string>  $families  keys of FONTS
     */
    public static function fontFaces(array $families, bool $pdf): string
    {
        $css = '';

        foreach ($families as $family) {
            foreach (self::FONTS[$family] ?? [] as [$file, $weight, $style]) {
                $src = $pdf ? public_path("fonts/resume/{$file}") : asset("fonts/resume/{$file}");
                $css .= "@font-face { font-family: '{$family}'; src: url('{$src}') format('truetype'); font-weight: {$weight}; font-style: {$style}; }\n";
            }
        }

        return $css;
    }

    /** Default section headings; a template may use its own words. */
    public const TITLES = [
        'experience' => 'Work Experience',
        'education' => 'Education',
        'skills' => 'Skills',
        'tools' => 'Software',
        'projects' => 'Projects',
        'certifications' => 'Certifications',
        'languages' => 'Languages',
        'hobbies' => 'Hobbies',
        'references' => 'References',
    ];

    /**
     * One experience / education / project item in a common shape, so the shared
     * entry markup can show any of them.
     *
     * @param  array<string, mixed>  $item
     * @return array{title: string, org: string, date: string, place: string, extra: string, lines: list<string>}
     */
    public static function entry(string $kind, array $item): array
    {
        return match ($kind) {
            'experience' => [
                'title' => trim((string) ($item['job_title'] ?? '')),
                'org' => trim((string) ($item['company'] ?? '')),
                'date' => self::range($item['start_date'] ?? null, $item['end_date'] ?? null, (bool) ($item['current'] ?? false)),
                'place' => trim((string) ($item['location'] ?? '')),
                'extra' => '',
                'lines' => self::lines($item['description'] ?? ''),
            ],
            'education' => [
                'title' => trim((string) ($item['degree'] ?? '')),
                'org' => trim((string) ($item['institution'] ?? '')),
                'date' => self::range($item['start_date'] ?? null, $item['end_date'] ?? null),
                'place' => trim((string) ($item['location'] ?? '')),
                'extra' => trim((string) ($item['score'] ?? '')),
                'lines' => self::lines($item['description'] ?? ''),
            ],
            default => [
                'title' => trim((string) ($item['name'] ?? '')),
                'org' => '',
                'date' => trim((string) ($item['duration'] ?? '')),
                'place' => '',
                'extra' => self::shortUrl(trim((string) ($item['link'] ?? ''))),
                'lines' => self::lines($item['description'] ?? ''),
            ],
        };
    }

    /**
     * Split a page-1 sidebar into the sections that fit and the ones that should
     * flow on in the main column instead. DomPDF can't break a sidebar across
     * pages, so this estimates height in text lines (roughly; it errs on the
     * generous side) and moves everything from the first section that doesn't fit.
     *
     * @param  ResumeData  $data
     * @param  array<string, mixed>  $prepared  the result of prepare()
     * @param  list<string>  $sections  sidebar sections in the user's order
     * @param  int  $capacity  lines available below whatever else the sidebar holds
     * @param  int  $charsPerLine  roughly how many characters fit on one line
     * @return array{0: list<string>, 1: list<string>}
     */
    public static function fitSidebar(array $data, array $prepared, array $sections, int $capacity, int $charsPerLine): array
    {
        $wrap = fn (string $text) => max(1, (int) ceil(mb_strlen($text) / max(1, $charsPerLine)));
        $used = 0;

        foreach ($sections as $i => $section) {
            $items = $prepared[$section] ?? [];

            if (! count($items)) {
                continue;
            }

            $lines = 3; // heading and spacing

            foreach ($items as $item) {
                $lines += match ($section) {
                    'skills', 'tools', 'languages' => self::showLevels($data, $section) ? 2 : 1,
                    'hobbies' => 1,
                    'certifications' => $wrap((string) $item['name']) + 1.5,
                    'references' => 4.5,
                    default => (function () use ($section, $item, $wrap) {
                        $entry = self::entry($section, $item);

                        return $wrap($entry['title']) + $wrap($entry['org']) + 1.5
                            + array_sum(array_map($wrap, $entry['lines']));
                    })(),
                };
            }

            if ($used + $lines > $capacity) {
                return [array_slice($sections, 0, $i), array_slice($sections, $i)];
            }

            $used += $lines;
        }

        return [$sections, []];
    }
}
