<?php

use App\Models\User;

test('the privacy page is public', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/Privacy'));
});

test('the privacy page uses the configured retention and contact details', function () {
    config([
        'resume.contact_email' => 'grievance@resume.test',
        'resume.inactivity.warn_after_days' => 53,
        'resume.inactivity.delete_after_days' => 60,
    ]);

    $this->get(route('privacy'))
        ->assertInertia(fn ($page) => $page
            ->where('contactEmail', 'grievance@resume.test')
            ->where('warnAfterDays', 53)
            ->where('deleteAfterDays', 60));
});

test('logged in users can read the privacy page too', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('privacy'))
        ->assertOk();
});
