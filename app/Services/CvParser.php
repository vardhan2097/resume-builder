<?php

namespace App\Services;

use App\Exceptions\CvHasNoText;
use App\Models\Resume;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class CvParser
{
    private const SECTIONS = [
        'summary' => ['summary', 'profile', 'professional summary', 'profile summary', 'objective', 'career objective', 'resume objective', 'about me', 'personal profile'],
        'experience' => ['experience', 'work experience', 'professional experience', 'employment history', 'employment', 'work history', 'professional history', 'career history'],
        'education' => ['education', 'academic background', 'qualifications', 'academic qualifications', 'educational qualifications', 'education and training'],
        'skills' => ['skills', 'key skills', 'technical skills', 'core skills', 'core competencies', 'relevant skills', 'skills and abilities', 'skills & abilities'],
        'tools' => ['software', 'tools', 'technologies', 'tech stack', 'software skills'],
        'projects' => ['projects', 'personal projects', 'key projects', 'academic projects'],
        'certifications' => ['certifications', 'certification', 'certificates', 'licenses', 'courses', 'certifications & courses'],
        'languages' => ['languages', 'language', 'languages known'],
        'hobbies' => ['hobbies', 'interests', 'hobbies & interests', 'hobbies and interests'],
        'references' => ['references', 'referees'],
    ];

    private const MONTH = '(?:jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)';

    private const BULLET = '/^[•\-\*▪●◦‣–·]\s*/u';

    public function parseFile(string $path, string $extension): array
    {
        $text = match ($extension) {
            'pdf' => $this->pdfText($path),
            'docx' => $this->docxText($path),
            default => throw new InvalidArgumentException('Unsupported file type.'),
        };

        if (mb_strlen(trim($text)) < 20) {
            throw new CvHasNoText('No readable text found in the file.');
        }

        return $this->parseText($text);
    }

    public function parseText(string $text): array
    {
        $lines = $this->cleanLines($text);
        [$header, $sections] = $this->splitSections($lines);

        $data = Resume::defaultData();
        $data['personal'] = $this->parseHeader($header, $text);
        $data['personal']['summary'] = implode(' ', array_map(fn ($l) => $this->stripBullet($l), $sections['summary'] ?? []));

        $data['experience'] = array_slice($this->parseExperience($sections['experience'] ?? []), 0, 20);
        $data['education'] = array_slice($this->parseEducation($sections['education'] ?? []), 0, 10);
        $data['projects'] = array_slice($this->parseProjects($sections['projects'] ?? []), 0, 15);
        $data['skills'] = array_slice($this->levelItems($sections['skills'] ?? []), 0, 40);
        $data['tools'] = array_slice($this->levelItems($sections['tools'] ?? []), 0, 30);
        $data['languages'] = array_slice($this->levelItems($sections['languages'] ?? [], true), 0, 15);
        $data['hobbies'] = array_slice($this->splitItems($sections['hobbies'] ?? []), 0, 20);
        $data['certifications'] = array_slice(array_map(
            fn ($line) => ['id' => (string) Str::uuid(), 'name' => mb_substr($line, 0, 150), 'issuer' => '', 'date' => ''],
            array_map(fn ($l) => $this->stripBullet($l), $sections['certifications'] ?? [])
        ), 0, 20);

        return $data;
    }

    // ---------- Text extraction ----------

    private function pdfText(string $path): string
    {
        return (new PdfParser)->parseFile($path)->getText();
    }

    private function docxText(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Could not open the DOCX file.');
        }

        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $xml = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml);
        $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml);
        $xml = str_replace('</w:p>', "\n", $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // ---------- Structure ----------

    private function cleanLines(string $text): array
    {
        $text = preg_replace('/\t+/', ' | ', $text);
        $lines = preg_split('/\R/u', $text);

        $lines = array_map(fn ($line) => trim(preg_replace('/\s+/u', ' ', $line)), $lines);

        return array_values(array_filter($lines, fn ($line) => $line !== '' && $line !== '|'));
    }

    private function splitSections(array $lines): array
    {
        $header = [];
        $sections = [];
        $current = null;

        foreach ($lines as $line) {
            $key = $this->sectionKey($line);

            if ($key !== null) {
                $current = $key;
                $sections[$current] ??= [];

                continue;
            }

            if ($current === null) {
                $header[] = $line;
            } else {
                $sections[$current][] = $line;
            }
        }

        return [$header, $sections];
    }

    private function sectionKey(string $line): ?string
    {
        if (mb_strlen($line) > 40) {
            return null;
        }

        $normalized = mb_strtolower(trim(preg_replace('/[^\pL&\s]/u', '', $line)));

        foreach (self::SECTIONS as $key => $names) {
            if (in_array($normalized, $names, true)) {
                return $key;
            }
        }

        return null;
    }

    // ---------- Header / contact ----------

    private function parseHeader(array $header, string $fullText): array
    {
        $personal = Resume::defaultData()['personal'];
        $headerText = implode("\n", $header);

        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $fullText, $m)) {
            $personal['email'] = $m[0];
        }

        if (preg_match('/\+?\d[\d\s\-()]{7,}\d/', $headerText, $m)) {
            $personal['phone'] = trim($m[0]);
        }

        if (preg_match('/(?:https?:\/\/)?(?:www\.)?linkedin\.com\/[^\s|,]+/i', $fullText, $m)) {
            $personal['linkedin'] = $this->normalizeUrl($m[0]);
        }

        $withoutLinkedin = preg_replace('/(?:https?:\/\/)?(?:www\.)?linkedin\.com\/[^\s|,]+/i', '', $headerText);
        if (preg_match('/(?:https?:\/\/|www\.)[^\s|,]+/i', $withoutLinkedin, $m)) {
            $personal['website'] = $this->normalizeUrl($m[0]);
        }

        $nameLike = array_values(array_filter($header, fn ($line) => $this->isNameLike($line)));
        $personal['full_name'] = mb_substr($nameLike[0] ?? '', 0, 100);
        $personal['job_title'] = mb_substr($nameLike[1] ?? '', 0, 100);

        foreach ($header as $line) {
            $parts = preg_split('/\s*[|•·]\s*/u', $line);

            if (count($parts) < 2) {
                continue;
            }

            foreach ($parts as $part) {
                if ($this->looksLikeLocation($part, $personal)) {
                    $personal['location'] = mb_substr($part, 0, 150);
                    break 2;
                }
            }
        }

        return $personal;
    }

    private function isNameLike(string $line): bool
    {
        if (preg_match('/[@\d]|https?:|www\.|\|/i', $line)) {
            return false;
        }

        $words = preg_split('/\s+/', trim($line));

        return count($words) >= 1 && count($words) <= 6 && mb_strlen($line) <= 60;
    }

    private function looksLikeLocation(string $part, array $personal): bool
    {
        $part = trim($part);

        return $part !== ''
            && mb_strlen($part) <= 40
            && preg_match('/\pL/u', $part)
            && ! preg_match('/@|https?:|www\.|linkedin|\d{5,}/i', $part)
            && $part !== $personal['full_name']
            && $part !== $personal['job_title'];
    }

    private function normalizeUrl(string $url): string
    {
        $url = rtrim($url, '.,;');

        return preg_match('/^https?:\/\//i', $url) ? $url : 'https://'.$url;
    }

    // ---------- Lists ----------

    private function stripBullet(string $line): string
    {
        return trim(preg_replace(self::BULLET, '', $line));
    }

    private function splitItems(array $lines): array
    {
        $items = [];

        foreach ($lines as $line) {
            foreach (preg_split('/\s*[,|•·;]\s*/u', $this->stripBullet($line)) as $item) {
                $item = trim($item);

                if ($item !== '' && mb_strlen($item) <= 60) {
                    $items[] = $item;
                }
            }
        }

        return array_values(array_unique($items));
    }

    private function levelItems(array $lines, bool $isLanguage = false): array
    {
        return array_map(function ($item) use ($isLanguage) {
            $level = $isLanguage ? $this->languageLevel($item) : 3;
            $name = trim(preg_replace('/\s*\(.*?\)\s*/', '', $item));

            return ['id' => (string) Str::uuid(), 'name' => $name, 'level' => $level];
        }, $this->splitItems($lines));
    }

    private function languageLevel(string $text): int
    {
        $text = mb_strtolower($text);

        return match (true) {
            (bool) preg_match('/native|mother|fluent|proficient/', $text) => 5,
            (bool) preg_match('/advanced/', $text) => 4,
            (bool) preg_match('/basic|beginner|elementary/', $text) => 2,
            default => 3,
        };
    }

    // ---------- Dated entries (experience, education, projects) ----------

    private function entries(array $lines): array
    {
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            $isBullet = (bool) preg_match(self::BULLET, $line);
            $text = $this->stripBullet($line);
            $range = $isBullet ? null : $this->dateRange($text);

            if ($range) {
                if ($current && ! $current['range'] && ! $current['bullets']) {
                    $current['range'] = $range;
                } else {
                    if ($current) {
                        $entries[] = $current;
                    }
                    $current = ['heading' => [], 'range' => $range, 'bullets' => []];
                }

                $rest = trim(str_ireplace($range['match'], '', $text), " \t-–—|,·");
                if ($rest !== '') {
                    $current['heading'][] = $rest;
                }

                continue;
            }

            if (! $isBullet && ($current === null || $current['bullets'])) {
                if ($current) {
                    $entries[] = $current;
                }
                $current = ['heading' => [$text], 'range' => null, 'bullets' => []];

                continue;
            }

            if (! $isBullet && count($current['heading']) < 3) {
                $current['heading'][] = $text;

                continue;
            }

            $current ??= ['heading' => [], 'range' => null, 'bullets' => []];
            $current['bullets'][] = $text;
        }

        if ($current) {
            $entries[] = $current;
        }

        return $entries;
    }

    private function parseExperience(array $lines): array
    {
        return array_map(function ($entry) {
            [$title, $company] = $this->splitTitle($entry['heading'][0] ?? '');

            if ($company === '' && isset($entry['heading'][1])) {
                $company = $entry['heading'][1];
            }

            return [
                'id' => (string) Str::uuid(),
                'job_title' => mb_substr($title, 0, 100),
                'company' => mb_substr($company, 0, 150),
                'location' => '',
                'start_date' => $entry['range']['start'] ?? '',
                'end_date' => $entry['range']['end'] ?? '',
                'current' => $entry['range']['current'] ?? false,
                'description' => mb_substr(implode("\n", $entry['bullets']), 0, 3000),
            ];
        }, $this->entries($lines));
    }

    private function parseEducation(array $lines): array
    {
        return array_map(function ($entry) {
            $all = array_merge($entry['heading'], $entry['bullets']);
            $score = '';

            foreach ($all as $line) {
                if (preg_match('/(?:cgpa|gpa|percentage|grade)\s*[:\-]?\s*[\d.]+(?:\s*\/\s*[\d.]+)?|\b\d{1,3}(?:\.\d+)?\s*%/i', $line, $m)) {
                    $score = $m[0];
                    break;
                }
            }

            [$degree, $institution] = $this->splitTitle($entry['heading'][0] ?? '');

            if ($institution === '' && isset($entry['heading'][1])) {
                $institution = $entry['heading'][1];
            }

            return [
                'id' => (string) Str::uuid(),
                'degree' => mb_substr($degree, 0, 150),
                'institution' => mb_substr($institution, 0, 150),
                'location' => '',
                'start_date' => $entry['range']['start'] ?? '',
                'end_date' => $entry['range']['end'] ?? '',
                'score' => mb_substr($score, 0, 50),
                'description' => mb_substr(implode("\n", $entry['bullets']), 0, 1000),
            ];
        }, $this->entries($lines));
    }

    private function parseProjects(array $lines): array
    {
        return array_map(function ($entry) {
            $extra = array_slice($entry['heading'], 1);

            return [
                'id' => (string) Str::uuid(),
                'name' => mb_substr($entry['heading'][0] ?? 'Project', 0, 150),
                'duration' => '',
                'link' => '',
                'description' => mb_substr(implode("\n", array_merge($extra, $entry['bullets'])), 0, 2000),
            ];
        }, $this->entries($lines));
    }

    private function splitTitle(string $heading): array
    {
        $parts = preg_split('/\s+(?:at|@)\s+|\s+[-–|]\s+|,\s+/u', $heading, 2);

        return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
    }

    // ---------- Dates ----------

    private function dateRange(string $text): ?array
    {
        $token = '(?:'.self::MONTH.'\.?\s*,?\s*(?:19|20)\d{2}|\d{1,2}\/(?:19|20)\d{2}|(?:19|20)\d{2})';
        $end = '(?:'.$token.'|present|current|now|till date|ongoing|today)';

        if (preg_match('/('.$token.')\s*(?:-|–|—|to|till)\s*('.$end.')/iu', $text, $m)) {
            $isCurrent = (bool) preg_match('/present|current|now|till date|ongoing|today/i', $m[2]);

            return [
                'start' => $this->toDate($m[1]),
                'end' => $isCurrent ? '' : $this->toDate($m[2]),
                'current' => $isCurrent,
                'match' => $m[0],
            ];
        }

        if (preg_match('/^\s*('.$token.')\s*$/iu', $text, $m)) {
            return ['start' => '', 'end' => $this->toDate($m[1]), 'current' => false, 'match' => $m[0]];
        }

        return null;
    }

    private function toDate(string $token): string
    {
        $token = mb_strtolower(trim($token));

        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $token, $m)) {
            return sprintf('%04d-%02d', $m[2], $m[1]);
        }

        if (preg_match('/^(?:19|20)\d{2}$/', $token)) {
            return $token;
        }

        if (preg_match('/^([a-z]{3})[a-z]*\.?\s*,?\s*(\d{4})$/', $token, $m)) {
            $months = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
                'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

            return isset($months[$m[1]]) ? sprintf('%s-%02d', $m[2], $months[$m[1]]) : $m[2];
        }

        return '';
    }
}
