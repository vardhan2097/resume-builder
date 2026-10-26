<?php

use App\Models\Resume;
use App\Models\User;

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