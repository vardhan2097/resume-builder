<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\InactiveAccountWarning;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Warn, then delete, accounts that have been inactive for a long time.
 *
 * Safe to re-run: a user is warned once (deletion_warned_at), and only deleted
 * once the warning is old enough and they haven't been active since it.
 */
#[Signature('resumes:prune-inactive {--dry-run : List who would be warned or deleted without changing anything}')]
#[Description('Email inactive users a deletion warning, and delete accounts that stayed inactive after it')]
class PruneInactiveUsers extends Command
{
    public function handle(): int
    {
        $warnAfter = (int) config('resume.inactivity.warn_after_days');
        $deleteAfter = (int) config('resume.inactivity.delete_after_days');
        $grace = $deleteAfter - $warnAfter;
        $dryRun = (bool) $this->option('dry-run');

        $warned = $this->warnInactive($warnAfter, $grace, $dryRun);
        $deleted = $this->deleteInactive($deleteAfter, $grace, $dryRun);

        $summary = sprintf('%s%d warned, %d deleted.', $dryRun ? '[dry run] ' : '', $warned, $deleted);
        $this->info($summary);

        if (! $dryRun) {
            Log::info("resumes:prune-inactive: {$summary}");
        }

        return self::SUCCESS;
    }

    private function warnInactive(int $warnAfter, int $grace, bool $dryRun): int
    {
        $count = 0;

        $this->inactiveFor($warnAfter)
            // Not warned yet, or warned before their latest activity (a stale warning).
            ->where(fn (Builder $query) => $query
                ->whereNull('deletion_warned_at')
                ->orWhereRaw('deletion_warned_at <= COALESCE(last_active_at, created_at)'))
            ->lazyById(100)
            ->each(function (User $user) use ($grace, $dryRun, &$count) {
                $this->line(($dryRun ? 'Would warn ' : 'Warning ')."#{$user->id} {$user->email}");
                $count++;

                if ($dryRun) {
                    return;
                }

                try {
                    $user->notify(new InactiveAccountWarning(now()->addDays($grace)));
                } catch (\Throwable $e) {
                    // Not marked as warned, so it is retried tomorrow and nobody is deleted unwarned.
                    report($e);
                    $this->error("  Could not email #{$user->id}: {$e->getMessage()}");
                    $count--;

                    return;
                }

                $user->timestamps = false;
                $user->forceFill(['deletion_warned_at' => now()])->saveQuietly();
            });

        return $count;
    }

    private function deleteInactive(int $deleteAfter, int $grace, bool $dryRun): int
    {
        $count = 0;

        $this->inactiveFor($deleteAfter)
            ->where('deletion_warned_at', '<=', now()->subDays($grace))
            // The warning must be newer than their last activity.
            ->whereRaw('deletion_warned_at > COALESCE(last_active_at, created_at)')
            ->lazyById(100)
            ->each(function (User $user) use ($dryRun, &$count) {
                $this->line(($dryRun ? 'Would delete ' : 'Deleting ')."#{$user->id} {$user->email}");
                $count++;

                if (! $dryRun) {
                    // User's deleting event removes resumes (and their photo files) via Eloquent.
                    $user->delete();
                }
            });

        return $count;
    }

    /**
     * Users with no activity for $days. Someone who never came back after
     * registering counts from their sign-up date.
     *
     * @return Builder<User>
     */
    private function inactiveFor(int $days): Builder
    {
        return User::query()->whereRaw(
            'COALESCE(last_active_at, created_at) <= ?',
            [now()->subDays($days)],
        );
    }
}
