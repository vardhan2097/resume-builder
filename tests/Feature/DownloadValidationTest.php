<?php

use App\Models\Resume;
use App\Models\User;

it('returns field validation errors for guest downloads without changing the input', function (string $route) {
    $data = Resume::defaultData();
    $data['personal']['full_name'] = 'Priya Mehta';
    $data['personal']['email'] = 'not-an-email';
    $payload = ['title' => 'My Resume', 'template' => 'classic', 'color' => '#0f766e', 'data' => $data];

    $this->postJson(route($route), $payload)->assertUnprocessable()
        ->assertJsonValidationErrors('data.personal.email');
    expect($data['personal']['full_name'])->toBe('Priya Mehta')
        ->and($data['personal']['email'])->toBe('not-an-email');
})->with(['build.pdf', 'build.docx']);

it('validates saved data before a signed-in download without modifying it', function (string $route) {
    $data = Resume::defaultData();
    $data['personal']['full_name'] = 'Priya Mehta';
    $data['personal']['email'] = 'not-an-email';
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume', 'data' => $data]);

    $this->actingAs($user)->getJson(route($route, $resume))->assertUnprocessable()
        ->assertJsonValidationErrors('data.personal.email');
    expect($resume->fresh()->data['personal']['email'])->toBe('not-an-email')
        ->and($resume->fresh()->data['personal']['full_name'])->toBe('Priya Mehta');
})->with(['resumes.pdf', 'resumes.docx']);
