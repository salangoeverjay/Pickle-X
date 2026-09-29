<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Possible PickleX accounts for a typed name or email, best first. Suggestions only:
 * nothing is linked until the organizer picks one (paid lists, walk-ins).
 */
class AccountMatch
{
    /**
     * @return Collection<int, array{user: User, why: string, score: int}>
     */
    public static function find(string $text, int $limit = 4, array $exclude = [], ?string $club = null): Collection
    {
        $q = Str::lower(preg_replace('/\s+/', ' ', trim($text)));
        if (mb_strlen($q) < 2) {
            return collect();
        }
        $email = str_contains($q, '@');
        $tokens = array_values(array_filter(explode(' ', $q), fn ($t) => mb_strlen($t) >= ($email ? 1 : 2)));
        $local = $email ? Str::before($q, '@') : null;

        $users = User::where('role', 'member')->whereNotIn('id', $exclude)
            ->where(function ($w) use ($email, $q, $local, $tokens) {
                if ($email) {
                    $w->whereRaw('lower(email) = ?', [$q]);
                    if (mb_strlen($local) >= 3) {
                        $w->orWhereRaw('lower(email) like ?', [$local.'%']);
                    }
                } else {
                    foreach ($tokens as $t) {
                        $w->orWhereRaw('lower(name) like ?', ['%'.$t.'%']);
                    }
                }
            })->limit(40)->get();

        return $users->map(function (User $u) use ($q, $email, $local, $tokens, $club) {
            $name = Str::lower($u->name);
            $parts = explode(' ', $name);
            [$score, $why] = match (true) {
                $email && Str::lower($u->email) === $q => [100, 'Email matches'],
                $email && mb_strlen($local) >= 3 && str_starts_with(Str::lower(Str::before($u->email, '@')), $local) => [55, 'Similar email'],
                $email => [0, ''],
                $name === $q => [90, 'Same name'],
                count($tokens) === 1 && $parts[0] === $q => [70, 'Same first name'],
                str_starts_with($name, $q) => [60, 'Name starts the same'],
                collect($tokens)->contains(fn ($t) => mb_strlen($t) >= 3 && in_array($t, $parts)) => [50, 'Part of the name matches'],
                default => [0, ''],
            };

            return ['user' => $u, 'why' => $why, 'score' => $score + ($score && $club && $u->club === $club ? 5 : 0)];
        })->filter(fn ($m) => $m['score'] > 0)
            ->sort(fn ($a, $b) => [$b['score'], $a['user']->name] <=> [$a['score'], $b['user']->name])
            ->take($limit)->values();
    }
}
