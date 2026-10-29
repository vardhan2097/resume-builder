<?php

use App\Models\Resume;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

function guestPayload(array $personal = []): array
{
    $data = Resume::defaultData();
    $data['personal'] = array_replace($data['personal'], ['full_name' => 'Guest Person', 'email' => 'guest@example.com'], $personal);
    $data['skills'] = [['id' => 's1', 'name' => 'Laravel', 'level' => 4]];

    return [
        'title' => 'My Resume',
        'template' => 'classic',
        'color' => '#1e3a8a',
        'data' => $data,
    ];
}

test('guests can open the builder', function () {
    $this->get(route('build'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/Build')
            ->has('templates.classic')
            ->where('defaults.data', Resume::defaultData()));
});

test('logged in users are sent to their resumes from the builder', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('build'))
        ->assertRedirect(route('resumes.index'));
});

test('guests can preview a resume', function () {
    $this->postJson(route('build.preview'), guestPayload())
        ->assertOk()
        ->assertSee('Guest Person')
        ->assertSee('Laravel');
});

test('guest preview tolerates half typed emails and links', function () {
    $this->postJson(route('build.preview'), guestPayload(['email' => 'guest@', 'website' => 'https://']))
        ->assertOk()
        ->assertSee('guest@');
});

test('guest preview still enforces the resume shape and limits', function () {
    $payload = guestPayload();
    $payload['data']['hacked'] = true;
    $payload['data']['personal']['summary'] = str_repeat('a', 2001);

    $this->postJson(route('build.preview'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['data', 'data.personal.summary']);
});

test('guests can download a pdf', function () {
    $this->postJson(route('build.pdf'), guestPayload())
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('guest-person-resume.pdf');
});

test('guests can download a docx', function () {
    $this->postJson(route('build.docx'), guestPayload())
        ->assertOk()
        ->assertDownload('guest-person-resume.docx');
});

test('guest downloads reject invalid data', function (string $route) {
    $this->postJson(route($route), guestPayload(['email' => 'not-an-email']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('data.personal.email');
})->with(['build.pdf', 'build.docx']);

test('guest endpoints do not touch the database', function () {
    $this->postJson(route('build.pdf'), guestPayload())->assertOk();
    $this->postJson(route('build.docx'), guestPayload())->assertOk();

    expect(Resume::count())->toBe(0);
});

test('guest preview is rate limited', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->postJson(route('build.preview'), guestPayload())->assertOk();
    }

    $this->postJson(route('build.preview'), guestPayload())->assertTooManyRequests();
});

test('guest downloads are rate limited', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->postJson(route('build.docx'), guestPayload())->assertOk();
    }

    $this->postJson(route('build.pdf'), guestPayload())->assertTooManyRequests();
});

test('guests can import a cv without anything being saved', function () {
    $word = new PhpWord;
    $section = $word->addSection();
    foreach (['Asha Naik', 'Laravel Developer', 'asha@example.com', 'Skills', 'PHP, Laravel'] as $line) {
        $section->addText($line);
    }
    $path = sys_get_temp_dir().'/cv-guest-'.uniqid().'.docx';
    IOFactory::createWriter($word, 'Word2007')->save($path);

    $this->post(route('build.import'), ['cv' => new UploadedFile($path, 'cv.docx', null, null, true)], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.personal.full_name', 'Asha Naik');

    expect(Resume::count())->toBe(0);
});

test('guest import rejects unsupported files', function () {
    $this->post(route('build.import'), ['cv' => UploadedFile::fake()->create('cv.txt', 10, 'text/plain')], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cv');
});

test('a logged in user can claim a guest resume', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('resumes.claim'), guestPayload());

    $resume = $user->resumes()->sole();

    $response->assertRedirect(route('resumes.edit', $resume));
    expect($resume->title)->toBe('My Resume')
        ->and($resume->color)->toBe('#1e3a8a')
        ->and($resume->data['personal']['full_name'])->toBe('Guest Person');
});

test('claimed resume belongs only to the logged in user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($owner)->post(route('resumes.claim'), guestPayload());

    expect($owner->resumes()->count())->toBe(1)
        ->and($other->resumes()->count())->toBe(0);
});

test('guests cannot claim', function () {
    $this->post(route('resumes.claim'), guestPayload())->assertRedirect(route('login'));

    expect(Resume::count())->toBe(0);
});

test('claim validates the resume', function () {
    $payload = guestPayload();
    unset($payload['data']['personal']);

    $this->actingAs(User::factory()->create())
        ->post(route('resumes.claim'), $payload)
        ->assertSessionHasErrors('data.personal');

    expect(Resume::count())->toBe(0);
});
