<?php

use App\Models\User;
use App\Notifications\InactiveAccountWarning;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/** A user whose last activity was $days ago (and who registered before that). */
function userInactiveFor(int $days): User
{
    $user = User::factory()->create(['created_at' => now()->subDays($days + 30)]);
    $user->forceFill(['last_active_at' => now()->subDays($days)])->save();

    return $user;
}

function prune(array $options = []): void
{
    test()->artisan('resumes:prune-inactive', $options)->assertSuccessful();
}

beforeEach(function () {
    Notification::fake();
    $this->travelTo(now()->startOfDay()->setTime(3, 0));
});

// ---------- Activity tracking ----------

test('a request by a logged in user records activity', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('resumes.index'))->assertOk();

    expect($user->fresh()->last_active_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('activity is written at most once an hour', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('resumes.index'));
    $first = $user->fresh()->last_active_at;

    $this->travel(30)->minutes();
    $this->actingAs($user->fresh())->get(route('resumes.index'));
    expect($user->fresh()->last_active_at->eq($first))->toBeTrue();

    $this->travel(31)->minutes();
    $this->actingAs($user->fresh())->get(route('resumes.index'));
    expect($user->fresh()->last_active_at->gt($first))->toBeTrue();
});

test('activity tracking does not touch updated_at', function () {
    $user = User::factory()->create(['updated_at' => now()->subWeek()]);

    $this->actingAs($user)->get(route('resumes.index'));

    expect($user->fresh()->updated_at->toDateString())->toBe(now()->subWeek()->toDateString());
});

test('coming back cancels a pending deletion warning', function () {
    $user = userInactiveFor(55);
    $user->forceFill(['deletion_warned_at' => now()->subDays(2)])->save();

    $this->actingAs($user)->get(route('resumes.index'));

    expect($user->fresh()->deletion_warned_at)->toBeNull();

    $this->travel(10)->days();
    prune();

    expect(User::find($user->id))->not->toBeNull();
});

// ---------- Warnings ----------

test('users inactive for 53 days are warned once', function () {
    $due = userInactiveFor(53);
    $notYet = userInactiveFor(52);

    prune();

    Notification::assertSentTo($due, InactiveAccountWarning::class, function ($notification) {
        return $notification->deleteOn->toDateString() === now()->addDays(7)->toDateString();
    });
    Notification::assertNotSentTo($notYet, InactiveAccountWarning::class);
    expect($due->fresh()->deletion_warned_at)->not->toBeNull()
        ->and($notYet->fresh()->deletion_warned_at)->toBeNull();
});

test('re-running does not warn twice', function () {
    $user = userInactiveFor(53);

    prune();
    $this->travel(1)->day();
    prune();

    Notification::assertSentToTimes($user, InactiveAccountWarning::class, 1);
});

test('users who never came back after registering count from sign up', function () {
    $user = User::factory()->create(['created_at' => now()->subDays(53)]);

    prune();

    Notification::assertSentTo($user, InactiveAccountWarning::class);
});

test('the warning email says when the account will be deleted', function () {
    $user = userInactiveFor(53);

    $mail = (new InactiveAccountWarning(now()->addDays(7)))->toMail($user);

    expect($mail->subject)->toContain('will be deleted')
        ->and(implode(' ', $mail->introLines))->toContain(now()->addDays(7)->format('j F Y'))
        ->and($mail->actionUrl)->toBe(route('login'));
});

// ---------- Deletion ----------

test('accounts are deleted only after the 7 day warning period', function () {
    $user = userInactiveFor(53);

    prune(); // warned
    $this->travel(6)->days();
    prune();
    expect(User::find($user->id))->not->toBeNull();

    $this->travel(1)->day(); // 60 days inactive, warned 7 days ago
    prune();
    expect(User::find($user->id))->toBeNull();
});

test('long inactive users who were never warned get the full warning period', function () {
    $user = userInactiveFor(120);

    prune();
    Notification::assertSentTo($user, InactiveAccountWarning::class);
    expect(User::find($user->id))->not->toBeNull();

    $this->travel(7)->days();
    prune();
    expect(User::find($user->id))->toBeNull();
});

test('a warning older than the last activity is replaced by a new one', function () {
    // e.g. warned, came back (but the warning wasn't cleared), then went quiet again.
    $user = userInactiveFor(61);
    $user->forceFill(['deletion_warned_at' => now()->subDays(70)])->save();

    prune();

    // Not deleted; warned afresh instead, and deleted only after a new full week.
    expect(User::find($user->id))->not->toBeNull();
    Notification::assertSentTo($user, InactiveAccountWarning::class);

    $this->travel(6)->days();
    prune();
    expect(User::find($user->id))->not->toBeNull();

    $this->travel(1)->day();
    prune();
    expect(User::find($user->id))->toBeNull();
});

test('deleting an inactive account removes its resumes and photo files', function () {
    Storage::fake('local');
    $user = userInactiveFor(60);
    $user->forceFill(['deletion_warned_at' => now()->subDays(7)])->save();

    Storage::disk('local')->put('photos/a.jpg', 'x');
    Storage::disk('local')->put('photos/a-original.jpg', 'x');
    $resume = $user->resumes()->create(['photo_path' => 'photos/a.jpg', 'photo_original_path' => 'photos/a-original.jpg']);
    $user->resumes()->create();

    prune();

    expect(User::find($user->id))->toBeNull()
        ->and($user->resumes()->count())->toBe(0);
    Storage::disk('local')->assertMissing(['photos/a.jpg', 'photos/a-original.jpg']);
});

test('dry run changes nothing', function () {
    $toWarn = userInactiveFor(53);
    $toDelete = userInactiveFor(60);
    $toDelete->forceFill(['deletion_warned_at' => now()->subDays(7)])->save();

    $this->artisan('resumes:prune-inactive', ['--dry-run' => true])
        ->expectsOutputToContain("Would warn #{$toWarn->id}")
        ->expectsOutputToContain("Would delete #{$toDelete->id}")
        ->assertSuccessful();

    Notification::assertNothingSent();
    expect($toWarn->fresh()->deletion_warned_at)->toBeNull()
        ->and(User::find($toDelete->id))->not->toBeNull();
});

test('active users are left alone', function () {
    $user = userInactiveFor(1);

    prune();

    Notification::assertNothingSent();
    expect(User::find($user->id))->not->toBeNull();
});

test('the cleanup is scheduled daily', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'resumes:prune-inactive'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 3 * * *');
});

// ---------- Account deletion from settings ----------

test('deleting your account from settings removes photo files too', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    Storage::disk('local')->put('photos/b.jpg', 'x');
    $user->resumes()->create(['photo_path' => 'photos/b.jpg']);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    expect(User::find($user->id))->toBeNull();
    Storage::disk('local')->assertMissing('photos/b.jpg');
});
