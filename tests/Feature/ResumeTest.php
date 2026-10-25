<?php

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