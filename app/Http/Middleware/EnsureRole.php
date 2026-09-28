<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:organizer') or 'role:member'.
 * member = any player account, organizer = approved organizer access, admin = admin.
 * Visiting an organizer or member route also switches the app into that mode.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = collect($roles)->contains(fn ($role) => match ($role) {
            'admin' => $user?->isAdmin(),
            'organizer' => $user?->isOrganizer(),
            'member' => $user?->isMember(),
            default => false,
        });
        abort_unless($allowed, 403);

        if (in_array('organizer', $roles) || in_array('member', $roles)) {
            $request->session()->put('mode', in_array('organizer', $roles) ? 'organizer' : 'member');
        }

        return $next($request);
    }
}
