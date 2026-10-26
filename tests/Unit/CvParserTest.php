<?php

use App\Services\CvParser;

function sampleCv(): string
{
    return <<<'TXT'
Asha Naik
Laravel Developer
+91 98765 43210 | asha@example.com | Ponda, Goa
linkedin.com/in/ashanaik

Summary
PHP developer with 6 years of experience building Laravel apps.

Experience
Backend Developer at TenTwenty
Jan 2024 - Present
• Built REST APIs in Laravel
• Improved query performance by 40%

Full Stack Developer, National Informatics Centre
Oct 2020 – Dec 2023
- Built a citizen services portal

Education
Bachelor of Computer Science
Goa University
2015 - 2019
CGPA: 8.2/10

Skills
PHP, Laravel, MySQL | Vue.js

Languages
English (Fluent), Hindi, Konkani (Native)
TXT;
}

test('it reads contact details', function () {
    $personal = (new CvParser())->parseText(sampleCv())['personal'];

    expect($personal['full_name'])->toBe('Asha Naik')
        ->and($personal['job_title'])->toBe('Laravel Developer')
        ->and($personal['email'])->toBe('asha@example.com')
        ->and($personal['phone'])->toBe('+91 98765 43210')
        ->and($personal['location'])->toBe('Ponda, Goa')
        ->and($personal['linkedin'])->toBe('https://linkedin.com/in/ashanaik');
});

test('it reads the summary', function () {
    $data = (new CvParser())->parseText(sampleCv());

    expect($data['personal']['summary'])->toContain('6 years of experience');
});

test('it splits experience into entries with dates', function () {
    $experience = (new CvParser())->parseText(sampleCv())['experience'];

    expect($experience)->toHaveCount(2)
        ->and($experience[0]['job_title'])->toBe('Backend Developer')
        ->and($experience[0]['company'])->toBe('TenTwenty')
        ->and($experience[0]['start_date'])->toBe('2024-01')
        ->and($experience[0]['current'])->toBeTrue()
        ->and($experience[0]['description'])->toContain('Built REST APIs in Laravel')
        ->and($experience[1]['company'])->toBe('National Informatics Centre')
        ->and($experience[1]['end_date'])->toBe('2023-12');
});

test('it reads education with score', function () {
    $education = (new CvParser())->parseText(sampleCv())['education'];

    expect($education)->toHaveCount(1)
        ->and($education[0]['degree'])->toBe('Bachelor of Computer Science')
        ->and($education[0]['institution'])->toBe('Goa University')
        ->and($education[0]['start_date'])->toBe('2015')
        ->and($education[0]['end_date'])->toBe('2019')
        ->and($education[0]['score'])->toContain('8.2');
});

test('it splits skills and language levels', function () {
    $data = (new CvParser())->parseText(sampleCv());

    expect(array_column($data['skills'], 'name'))->toBe(['PHP', 'Laravel', 'MySQL', 'Vue.js'])
        ->and(array_column($data['languages'], 'name'))->toBe(['English', 'Hindi', 'Konkani'])
        ->and(array_column($data['languages'], 'level'))->toBe([5, 3, 5]);
});