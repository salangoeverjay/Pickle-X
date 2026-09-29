<?php

namespace App\Support;

use App\Models\User;

/**
 * An organizer's pasted "paid players" list (one name or email per line). Every line is a guest
 * until the organizer links it to an account; AccountMatch suggests possible accounts.
 * $links maps a line's key (lowercased) to a user id, or "guest" when they chose not to link.
 */
class PaidList
{
    public static function key(string $line): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($line)));
    }

    /**
     * @return array<int, array{line: string, key: string, user: ?User, guest: bool, cands: array, review: bool, name: string}>
     */
    public static function rows(string $text, array $links = [], ?string $club = null): array
    {
        $lines = collect(preg_split('/\R/', $text))
            ->map(fn ($l) => preg_replace('/\s+/', ' ', trim($l)))
            ->filter()
            ->unique(fn ($l) => self::key($l))
            ->values();

        if ($lines->isEmpty()) {
            return [];
        }

        $ids = collect($links)->filter(fn ($v) => is_int($v) || ctype_digit((string) $v))->map(fn ($v) => (int) $v);
        $linked = User::where('role', 'member')->whereIn('id', $ids->values())->get()->keyBy('id');

        $rows = $lines->map(function ($line) use ($links, $linked) {
            $key = self::key($line);
            $user = isset($links[$key]) && $links[$key] !== 'guest' ? $linked->get((int) $links[$key]) : null;

            return ['line' => $line, 'key' => $key, 'user' => $user, 'guest' => ($links[$key] ?? null) === 'guest', 'cands' => [], 'review' => false, 'name' => $user?->name ?? $line];
        })->all();

        // Suggestions for lines that are neither linked nor kept as a guest (accounts already linked are left out).
        $taken = collect($rows)->pluck('user.id')->filter()->all();
        foreach ($rows as &$r) {
            if (! $r['user'] && ! $r['guest']) {
                $r['cands'] = AccountMatch::find($r['line'], 3, $taken, $club)->all();
                $r['review'] = (bool) $r['cands'];
            }
        }

        return $rows;
    }
}
