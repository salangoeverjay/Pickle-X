<?php

namespace App\Support;

use App\Models\OpenPlay;
use Illuminate\Support\Facades\Cache;

/**
 * "I'm up next" emails: players who just moved into the next full game get one email each time
 * they enter it. Who was already in it is remembered per open play, so later board changes don't repeat it.
 * ponytail: get-then-put on the cache isn't atomic; two board writes in the same instant could email twice.
 */
class UpNext
{
    public static function notify(OpenPlay $play): void
    {
        $group = $play->isLive() ? $play->nextGroup() : null;
        $ids = $group && $group['full'] ? [...$group['a']['ids'], ...$group['b']['ids']] : [];
        $key = "up-next:{$play->id}";
        $new = array_diff($ids, Cache::get($key, []));
        Cache::put($key, $ids, now()->addDay());

        foreach ($play->participantMap()->only($new) as $p) {
            if ($p->user && $p->user_id !== $play->organizer_id) {
                Emails::notify([$p->user], 'upNext', fn ($u) => Emails::upNext($play, $u, $group, $p->id));
            }
        }
    }
}
