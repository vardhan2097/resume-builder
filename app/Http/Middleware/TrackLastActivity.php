<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records when a logged-in user was last active, for the inactivity cleanup
 * (resumes:prune-inactive). Writes at most once an hour per user, not on
 * every request, and cancels a pending deletion warning when they return.
 */
class TrackLastActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->deletion_warned_at || ! $user->last_active_at || $user->last_active_at->lt(now()->subHour()))) {
            // saveQuietly + timestamps off: this isn't a profile change, so leave updated_at alone.
            $user->timestamps = false;
            $user->forceFill(['last_active_at' => now(), 'deletion_warned_at' => null])->saveQuietly();
            $user->timestamps = true;
        }

        return $next($request);
    }
}
