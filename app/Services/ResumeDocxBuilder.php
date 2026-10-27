<?php

namespace App\Services;

use App\Models\Resume;
use App\Support\ResumeFormat as F;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;

class ResumeDocxBuilder
{
    private string $color;

    public function build(Resume $resume): string
    {
        Settings::setOutputEscapingEnabled(true);

        $defaults = Resume::defaultData();
        $data = array_replace($defaults, (array) $resume->data);
        $p = array_replace($defaults['personal'], (array) ($data['personal'] ?? []));

        $this->color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $resume->color)
            ? ltrim($resume->color, '#')
            : '0F766E';

        $word = new PhpWord;
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10);

        $section = $word->addSection([
            'marginTop' => Converter::cmToTwip(1.8),
            'marginBottom' => Converter::cmToTwip(1.8),
            'marginLeft' => Converter::cmToTwip(2),
            'marginRight' => Converter::cmToTwip(2),
        ]);

        $this->header($section, $p);

        if (trim((string) $p['summary']) !== '') {
            $this->heading($section, 'Summary');
            $this->paragraphs($section, $p['summary']);
        }

        $this->experience($section, F::filled($data['experience'], 'job_title', 'company'));
        $this->education($section, F::filled($data['education'], 'degree', 'institution'));
        $this->projects($section, F::filled($data['projects'], 'name'));

        $this->inlineList($section, 'Skills', array_column(F::filled($data['skills'], 'name'), 'name'));
        $this->inlineList($section, 'Software & Tools', array_column(F::filled($data['tools'], 'name'), 'name'));
        $this->inlineList($section, 'Languages', array_column(F::filled($data['languages'], 'name'), 'name'));
        $this->inlineList($section, 'Hobbies', F::strings($data['hobbies']));

        $this->certifications($section, F::filled($data['certifications'], 'name'));
        $this->references($section, F::filled($data['references'], 'name'));

        $path = tempnam(sys_get_temp_dir(), 'resume_');
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    private function header(Section $section, array $p): void
    {
        $section->addText(
            (string) ($p['full_name'] ?: 'Your Name'),
            ['size' => 24, 'bold' => true, 'color' => '111827'],
            ['spaceAfter' => 0]
        );

        if (trim((string) $p['job_title']) !== '') {
            $section->addText(
                (string) $p['job_title'],
                ['size' => 13, 'bold' => true, 'color' => $this->color],
                ['spaceAfter' => 60]
            );
        }

        $contacts = F::strings([$p['phone'], $p['email'], $p['location'], $p['website'], $p['linkedin']]);

        if ($contacts) {
            $section->addText(implode('  |  ', $contacts), ['size' => 9, 'color' => '4B5563']);
        }
    }

    private function heading(Section $section, string $title): void
    {
        $section->addText(
            mb_strtoupper($title),
            ['size' => 11, 'bold' => true, 'color' => $this->color],
            [
                'spaceBefore' => 240,
                'spaceAfter' => 80,
                'borderBottomSize' => 6,
                'borderBottomColor' => $this->color,
            ]
        );
    }

    private function itemTitle(Section $section, string $text): void
    {
        $section->addText($text, ['size' => 11, 'bold' => true, 'color' => '111827'], ['spaceBefore' => 80, 'spaceAfter' => 0]);
    }

    private function meta(Section $section, string $text): void
    {
        if (trim($text) !== '') {
            $section->addText($text, ['size' => 9, 'color' => '6B7280'], ['spaceAfter' => 40]);
        }
    }

    /**
     * Free text with the user's line breaks kept. Word ignores raw newlines in addText(),
     * so each line is its own paragraph, tight within a block and spaced between blocks.
     */
    private function paragraphs(Section $section, ?string $text): void
    {
        foreach (F::paragraphs($text) as $paragraph) {
            foreach ($paragraph as $i => $line) {
                $last = $i === count($paragraph) - 1;

                $section->addText($line, null, ['spaceAfter' => $last ? Converter::pointToTwip(6) : 0]);
            }
        }
    }

    private function bullets(Section $section, ?string $text): void
    {
        foreach (F::lines($text) as $line) {
            $section->addListItem($line, 0);
        }
    }

    private function joinParts(string $separator, ?string ...$parts): string
    {
        return implode($separator, F::strings($parts));
    }

    private function experience(Section $section, array $items): void
    {
        if (! $items) {
            return;
        }

        $this->heading($section, 'Employment History');

        foreach ($items as $job) {
            $this->itemTitle($section, $this->joinParts(', ', $job['job_title'] ?? '', $job['company'] ?? ''));
            $this->meta($section, $this->joinParts(' · ',
                F::range($job['start_date'] ?? null, $job['end_date'] ?? null, (bool) ($job['current'] ?? false)),
                $job['location'] ?? ''
            ));
            $this->bullets($section, $job['description'] ?? '');
        }
    }

    private function education(Section $section, array $items): void
    {
        if (! $items) {
            return;
        }

        $this->heading($section, 'Education');

        foreach ($items as $edu) {
            $this->itemTitle($section, $this->joinParts(', ', $edu['degree'] ?? '', $edu['institution'] ?? ''));
            $this->meta($section, $this->joinParts(' · ',
                F::range($edu['start_date'] ?? null, $edu['end_date'] ?? null),
                $edu['score'] ?? ''
            ));
            $this->bullets($section, $edu['description'] ?? '');
        }
    }

    private function projects(Section $section, array $items): void
    {
        if (! $items) {
            return;
        }

        $this->heading($section, 'Projects');

        foreach ($items as $project) {
            $this->itemTitle($section, (string) $project['name']);
            $this->meta($section, $this->joinParts(' · ', $project['duration'] ?? '', $project['link'] ?? ''));
            $this->bullets($section, $project['description'] ?? '');
        }
    }

    private function certifications(Section $section, array $items): void
    {
        if (! $items) {
            return;
        }

        $this->heading($section, 'Certifications');

        foreach ($items as $cert) {
            $this->itemTitle($section, (string) $cert['name']);
            $this->meta($section, $this->joinParts(' · ', $cert['issuer'] ?? '', F::month($cert['date'] ?? null)));
        }
    }

    private function references(Section $section, array $items): void
    {
        if (! $items) {
            return;
        }

        $this->heading($section, 'References');

        foreach ($items as $ref) {
            $this->itemTitle($section, (string) $ref['name']);
            $this->meta($section, $this->joinParts(', ', $ref['position'] ?? '', $ref['company'] ?? ''));
            $this->meta($section, $this->joinParts('  |  ', $ref['phone'] ?? '', $ref['email'] ?? ''));
        }
    }

    private function inlineList(Section $section, string $title, array $names): void
    {
        $names = F::strings($names);

        if (! $names) {
            return;
        }

        $this->heading($section, $title);
        $section->addText(implode(', ', $names));
    }
}
