<?php

namespace App\Support;

use App\Models\User;

/**
 * Matches an organizer's pasted "paid players" list (one name or email per line) to accounts.
 * Order of attempts: exact email, exact full name, unique first name; otherwise a guest.
 */
class PaidList
{
    /**
     * @return array<int, array{line: string, user: ?User, how: 'email'|'name'|'first'|'guest', name: string}>
     */
    public static function match(string $text): array
    {
        $lines = collect(preg_split('/\R/', $text))
            ->map(fn ($l) => preg_replace('/\s+/', ' ', trim($l)))
            ->filter()
            ->unique(fn ($l) => mb_strtolower($l))
            ->values();

        if ($lines->isEmpty()) {
            return [];
        }

        // ponytail: loads every member once per call; switch to targeted where-in queries if the directory gets large.
        $members = User::where('role', 'member')->get(['id', 'name', 'email', 'avatar_color', 'avatar', 'organizer_status', 'role']);
        $byEmail = $members->keyBy(fn ($u) => mb_strtolower($u->email));
        $byName = $members->groupBy(fn ($u) => mb_strtolower($u->name));
        $byFirst = $members->groupBy(fn ($u) => mb_strtolower(strtok($u->name, ' ')));

        return $lines->map(function ($line) use ($byEmail, $byName, $byFirst) {
            $key = mb_strtolower($line);
            [$user, $how] = match (true) {
                strpos($line, '@') > 0 => [$byEmail->get($key), 'email'],
                ($byName->get($key)?->count() ?? 0) === 1 => [$byName->get($key)->first(), 'name'],
                ! str_contains($line, ' ') && ($byFirst->get($key)?->count() ?? 0) === 1 => [$byFirst->get($key)->first(), 'first'],
                default => [null, 'guest'],
            };

            return ['line' => $line, 'user' => $user, 'how' => $user ? $how : 'guest', 'name' => $user?->name ?? $line];
        })->all();
    }
}
