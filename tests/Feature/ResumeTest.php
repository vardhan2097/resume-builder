<?php

use App\Models\Resume;
use App\Models\User;
use App\Services\ResumeRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

test('guests are redirected to login', function () {
    $this->get(route('resumes.index'))->assertRedirect(route('login'));
});

test('user can create a resume', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('resumes.store'));

    $resume = $user->resumes()->first();
    expect($resume)->not->toBeNull();
    $response->assertRedirect(route('resumes.edit', $resume));
});

test('user cannot open another users resume', function () {
    $owner = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'Private']);

    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('resumes.edit', $resume))
        ->assertForbidden();
});

test('user can delete own resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'Delete me']);

    $this->actingAs($user)
        ->delete(route('resumes.destroy', $resume))
        ->assertRedirect(route('resumes.index'));

    expect($user->resumes()->count())->toBe(0);
});

function validPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Updated title',
        'template' => 'classic',
        'color' => '#123456',
        'data' => Resume::defaultData(),
    ], $overrides);
}

test('user can update own resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'Old']);

    $this->actingAs($user)
        ->put(route('resumes.update', $resume), validPayload())
        ->assertSessionHasNoErrors();

    expect($resume->fresh()->title)->toBe('Updated title');
});

test('invalid colour is rejected', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)
        ->put(route('resumes.update', $resume), validPayload(['color' => 'red']))
        ->assertSessionHasErrors('color');
});

test('user cannot update another users resume', function () {
    $resume = User::factory()->create()->resumes()->create(['title' => 'Mine']);

    $this->actingAs(User::factory()->create())
        ->put(route('resumes.update', $resume), validPayload())
        ->assertForbidden();

    expect($resume->fresh()->title)->toBe('Mine');
});

test('preview renders the resume as html', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $data = Resume::defaultData();
    $data['personal']['full_name'] = 'Shrivardhan Joshi';

    $this->actingAs($user)
        ->post(route('resumes.preview', $resume), [
            'template' => 'classic',
            'color' => '#0f766e',
            'data' => $data,
        ])
        ->assertOk()
        ->assertSee('Shrivardhan Joshi');
});

test('preview escapes html in user input', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $data = Resume::defaultData();
    $data['personal']['full_name'] = '<script>alert(1)</script>';

    $this->actingAs($user)
        ->post(route('resumes.preview', $resume), ['data' => $data])
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('user cannot preview another users resume', function () {
    $resume = User::factory()->create()->resumes()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('resumes.preview', $resume), [])
        ->assertForbidden();
});

test('user can download own resume as pdf', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)
        ->get(route('resumes.pdf', $resume))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('user cannot download another users pdf', function () {
    $resume = User::factory()->create()->resumes()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('resumes.pdf', $resume))
        ->assertForbidden();
});

test('user can download own resume as docx', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)
        ->get(route('resumes.docx', $resume))
        ->assertOk()
        ->assertDownload();
});

test('docx handles special characters', function () {
    $user = User::factory()->create();

    $data = Resume::defaultData();
    $data['personal']['full_name'] = 'R&D <Team> "Lead"';
    $data['experience'][] = [
        'id' => 'x1',
        'job_title' => 'Dev & Ops',
        'company' => 'A<B>C',
        'location' => '',
        'start_date' => '2024-01',
        'end_date' => '',
        'current' => true,
        'description' => "Line one & two\nLine <three>",
    ];

    $resume = $user->resumes()->create(['data' => $data]);

    $this->actingAs($user)
        ->get(route('resumes.docx', $resume))
        ->assertOk()
        ->assertDownload();
});

test('user cannot download another users docx', function () {
    $resume = User::factory()->create()->resumes()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('resumes.docx', $resume))
        ->assertForbidden();
});

test('user can upload a photo', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)
        ->post(route('resumes.photo.upload', $resume), [
            'photo' => UploadedFile::fake()->image('me.jpg', 1200, 900),
        ])
        ->assertSessionHasNoErrors();

    $resume->refresh();

    expect($resume->photo_path)->not->toBeNull();
    Storage::disk('local')->assertExists($resume->photo_path);

    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($resume->photo_path));
    expect([$width, $height])->toBe([400, 400]);
});

test('non image files are rejected', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)
        ->post(route('resumes.photo.upload', $resume), [
            'photo' => UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('photo');
});

test('deleting a resume deletes its photo', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)->post(route('resumes.photo.upload', $resume), [
        'photo' => UploadedFile::fake()->image('me.jpg'),
    ]);

    $path = $resume->refresh()->photo_path;

    $this->actingAs($user)->delete(route('resumes.destroy', $resume));

    Storage::disk('local')->assertMissing($path);
});

test('user cannot see another users photo', function () {
    $resume = User::factory()->create()->resumes()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('resumes.photo.show', $resume))
        ->assertForbidden();
});

test('user can import a docx cv', function () {
    $word = new PhpWord;
    $section = $word->addSection();
    foreach (['Asha Naik', 'Laravel Developer', 'asha@example.com | +91 98765 43210', 'Skills', 'PHP, Laravel, MySQL'] as $line) {
        $section->addText($line);
    }
    $path = sys_get_temp_dir().'/cv-test-'.uniqid().'.docx';
    IOFactory::createWriter($word, 'Word2007')->save($path);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('resumes.import'), [
        'cv' => new UploadedFile($path, 'cv.docx', null, null, true),
    ]);

    $resume = $user->resumes()->first();

    expect($resume)->not->toBeNull();
    $response->assertRedirect(route('resumes.edit', $resume));
    expect($resume->data['personal']['full_name'])->toBe('Asha Naik')
        ->and(array_column($resume->data['skills'], 'name'))->toBe(['PHP', 'Laravel', 'MySQL']);
});

test('import rejects unsupported files', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('resumes.import'), [
            'cv' => UploadedFile::fake()->create('cv.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('cv');

    expect($user->resumes()->count())->toBe(0);
});
function twoTonePortrait(): UploadedFile
{
    // 400x800: top half red, bottom half blue.
    $image = imagecreatetruecolor(400, 800);
    imagefilledrectangle($image, 0, 0, 399, 399, imagecolorallocate($image, 255, 0, 0));
    imagefilledrectangle($image, 0, 400, 399, 799, imagecolorallocate($image, 0, 0, 255));

    $path = tempnam(sys_get_temp_dir(), 'photo').'.png';
    imagepng($image, $path);

    return new UploadedFile($path, 'portrait.png', 'image/png', null, true);
}

function centrePixel(string $jpeg): array
{
    $image = imagecreatefromstring($jpeg);
    $rgb = imagecolorat($image, 200, 200);

    return [($rgb >> 16) & 0xFF, $rgb & 0xFF];
}

test('photo upload applies the chosen crop and keeps the original', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)
        ->post(route('resumes.photo.upload', $resume), [
            'photo' => twoTonePortrait(),
            'crop' => ['x' => 0, 'y' => 0, 'size' => 1],
        ])
        ->assertSessionHasNoErrors();

    $resume->refresh();

    Storage::disk('local')->assertExists($resume->photo_original_path);
    [$red, $blue] = centrePixel(Storage::disk('local')->get($resume->photo_path));
    expect($red)->toBeGreaterThan(200)->and($blue)->toBeLessThan(50);
});

test('photo can be re-framed from the original', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)->post(route('resumes.photo.upload', $resume), [
        'photo' => twoTonePortrait(),
        'crop' => ['x' => 0, 'y' => 0, 'size' => 1],
    ]);

    $oldPath = $resume->refresh()->photo_path;

    $this->actingAs($user)
        ->put(route('resumes.photo.frame', $resume), [
            'crop' => ['x' => 0, 'y' => 0.5, 'size' => 1],
        ])
        ->assertSessionHasNoErrors();

    $resume->refresh();

    Storage::disk('local')->assertMissing($oldPath);
    [$red, $blue] = centrePixel(Storage::disk('local')->get($resume->photo_path));
    expect($blue)->toBeGreaterThan(200)->and($red)->toBeLessThan(50);
});

test('re-framing requires a crop', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)
        ->put(route('resumes.photo.frame', $resume), [])
        ->assertSessionHasErrors('crop');
});

test('removing a photo deletes the original too', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)->post(route('resumes.photo.upload', $resume), [
        'photo' => twoTonePortrait(),
    ]);

    $resume->refresh();
    $paths = [$resume->photo_path, $resume->photo_original_path];

    $this->actingAs($user)->delete(route('resumes.photo.delete', $resume));

    Storage::disk('local')->assertMissing($paths[0]);
    Storage::disk('local')->assertMissing($paths[1]);
    expect($resume->refresh()->photo_original_path)->toBeNull();
});

function resumeWithMultilineSummary(User $user): Resume
{
    $data = Resume::defaultData();
    $data['personal']['summary'] = "Builds Laravel apps.\n\nNow looking for a PHP role.\nImmediate joiner & <ready>.";

    return $user->resumes()->create(['data' => $data]);
}

test('summary line breaks are kept in the preview and pdf html', function () {
    $user = User::factory()->create();
    $resume = resumeWithMultilineSummary($user);

    $this->actingAs($user)
        ->postJson(route('resumes.preview', $resume), [
            'template' => 'classic',
            'color' => '#0f766e',
            'data' => $resume->data,
        ])
        ->assertOk()
        ->assertSee('<p class="">Builds Laravel apps.</p>', false)
        ->assertSee('<p class="">Now looking for a PHP role.<br>Immediate joiner', false)
        ->assertSee('Immediate joiner &amp; &lt;ready&gt;.', false);
});

test('summary line breaks are kept in the docx', function () {
    $user = User::factory()->create();
    $resume = resumeWithMultilineSummary($user);

    $response = $this->actingAs($user)->get(route('resumes.docx', $resume))->assertOk();

    $zip = new ZipArchive;
    $zip->open($response->getFile()->getPathname());
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    // Each typed line is its own Word paragraph, in order.
    preg_match_all('/<w:p>.*?<\/w:p>/s', $xml, $paragraphs);
    $texts = array_map(fn ($p) => trim(html_entity_decode(strip_tags($p))), $paragraphs[0]);

    expect($texts)->toContain('Builds Laravel apps.', 'Now looking for a PHP role.', 'Immediate joiner & <ready>.')
        ->and(array_search('Immediate joiner & <ready>.', $texts))->toBe(array_search('Now looking for a PHP role.', $texts) + 1)
        ->and($xml)->not->toContain('<w:br/>');
});

function ratedData(array $showLevels = []): array
{
    $data = Resume::defaultData();
    $data['skills'] = [['id' => 's1', 'name' => 'Laravel', 'level' => 4]];
    $data['tools'] = [['id' => 't1', 'name' => 'Docker', 'level' => 3]];
    $data['languages'] = [['id' => 'l1', 'name' => 'Konkani', 'level' => 5]];
    $data['settings']['show_levels'] = array_replace($data['settings']['show_levels'], $showLevels);

    return $data;
}

test('ratings can be hidden per section in the preview', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $html = $this->actingAs($user)
        ->post(route('resumes.preview', $resume), [
            'template' => 'classic',
            'color' => '#0f766e',
            'data' => ratedData(['skills' => false, 'languages' => false]),
        ])
        ->assertOk()
        ->assertSee(['Laravel', 'Docker', 'Konkani'])
        ->getContent();

    // Only the tools section still draws a rating bar; the others are compact tags.
    expect(substr_count($html, 'class="bar-fill"'))->toBe(1)
        ->and($html)->toContain('width: 60%')
        ->and($html)->toContain('<span class="tag">Laravel</span>')
        ->and($html)->toContain('<span class="tag">Konkani</span>')
        ->and($html)->not->toContain('<span class="tag">Docker</span>');
});

test('ratings show by default, including for resumes saved before the setting existed', function () {
    $user = User::factory()->create();
    $data = ratedData();
    unset($data['settings']);
    $resume = $user->resumes()->create(['data' => $data]);

    $html = app(ResumeRenderer::class)->render('classic', '#0f766e', $resume->data);

    expect(substr_count($html, 'class="bar-fill"'))->toBe(3);

    $this->actingAs($user)
        ->get(route('resumes.edit', $resume))
        ->assertInertia(fn ($page) => $page->where('resume.data.settings.show_levels', [
            'skills' => true, 'tools' => true, 'languages' => true,
        ]));
});

test('the ratings setting is saved', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();

    $this->actingAs($user)
        ->put(route('resumes.update', $resume), validPayload(['data' => ratedData(['tools' => false])]))
        ->assertSessionHasNoErrors();

    expect($resume->fresh()->data['settings']['show_levels'])
        ->toBe(['skills' => true, 'tools' => false, 'languages' => true]);
});

test('saving without settings is still accepted', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();
    $data = ratedData();
    unset($data['settings']);

    $this->actingAs($user)
        ->put(route('resumes.update', $resume), validPayload(['data' => $data]))
        ->assertSessionHasNoErrors();
});

test('invalid ratings settings are rejected', function (array $settings, string $error) {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();
    $data = ratedData();
    $data['settings'] = $settings;

    $this->actingAs($user)
        ->put(route('resumes.update', $resume), validPayload(['data' => $data]))
        ->assertSessionHasErrors($error);
})->with([
    'unknown setting' => [['theme' => 'dark'], 'data.settings'],
    'unknown section' => [['show_levels' => ['hobbies' => true]], 'data.settings.show_levels'],
    'not a boolean' => [['show_levels' => ['skills' => 'maybe']], 'data.settings.show_levels.skills'],
]);
