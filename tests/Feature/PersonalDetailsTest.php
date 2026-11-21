<?php

use App\Models\Resume;
use App\Models\User;

it('saves personal details and includes them in every template and both download formats', function (string $template) {
    $user = User::factory()->create();
    $resume = $user->resumes()->create();
    $data = Resume::defaultData();
    $data['personal']['nationality'] = 'Indian';
    $data['personal']['date_of_birth'] = '1995-05-15';
    $payload = ['title' => 'My Resume', 'template' => $template, 'color' => '#0f766e', 'data' => $data];

    $this->actingAs($user)->put(route('resumes.update', $resume), $payload)->assertSessionHasNoErrors();
    expect($resume->fresh()->data['personal']['nationality'])->toBe('Indian')
        ->and($resume->fresh()->data['personal']['date_of_birth'])->toBe('1995-05-15');
    $this->post(route('resumes.preview', $resume), $payload)->assertOk()->assertSee('Indian')->assertSee('1995-05-15');
    $this->get(route('resumes.pdf', $resume))->assertOk();
    $response = $this->get(route('resumes.docx', $resume))->assertOk();
    $zip = new ZipArchive;
    $zip->open($response->getFile()->getPathname());
    expect($zip->getFromName('word/document.xml'))->toContain('Indian')->toContain('1995-05-15');
    $zip->close();
})->with(['classic', 'banner', 'executive']);

it('accepts older resumes without these fields', function () {
    $data = Resume::defaultData();
    unset($data['personal']['nationality'], $data['personal']['date_of_birth']);
    expect(Resume::withDefaults($data)['personal'])->toMatchArray(['nationality' => '', 'date_of_birth' => '']);
    $this->postJson(route('build.pdf'), ['template' => 'classic', 'color' => '#0f766e', 'data' => $data])->assertOk();
});

it('rejects invalid dates on downloads without changing the submitted data', function (string $route) {
    $data = Resume::defaultData();
    $data['personal']['nationality'] = 'Indian';
    $data['personal']['date_of_birth'] = 'not-a-date';
    $this->postJson(route($route), ['template' => 'classic', 'color' => '#0f766e', 'data' => $data])
        ->assertUnprocessable()->assertJsonValidationErrors('data.personal.date_of_birth');
    expect($data['personal']['nationality'])->toBe('Indian');
})->with(['build.pdf', 'build.docx']);
