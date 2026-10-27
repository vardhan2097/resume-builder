<?php

use App\Models\Resume;
use App\Models\User;
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
