<?php

namespace App\Livewire;

use App\Models\OpenPlay;
use Livewire\Component;

/** Public, read-only board for a live open play (QR / share-link target). Names only, no contact details. */
class LiveView extends Component
{
    /** Spots in line the big screen lists before "+ N more". */
    public const QUEUE_CAP = 12;

    public OpenPlay $play;

    public function mount(OpenPlay $play): void
    {
        $this->play = $play;
    }

    public function render()
    {
        $play = $this->play;
        $view = view('livewire.live-view', ['live' => $play->isLive()]);

        if (! $play->isLive()) {
            return $view->title("{$play->title} · Live · PickleX");
        }

        $me = auth()->user();
        $ready = $play->readyIds();
        $group = $play->nextGroup();
        $next = $group && $group['full'] ? $group : null;
        $courts = $play->boardCourts();
        $free = collect($courts)->first(fn ($c) => ! $c['a']);

        $queue = collect($play->queue())->map(function ($e, $i) use ($play, $ready, $group, $me) {
            $people = collect($e['ids'])->map(fn ($id) => $play->participant($id));
            $pair = $e['locked'] && count($e['ids']) === 2;
            $notHere = (bool) array_diff($e['ids'], $ready);

            $up = ! $notHere && ! ($e['paused'] ?? false) && in_array($e['id'], $group['used'] ?? []);
            [$meta, $metaClass] = match (true) {
                $notHere => ['Not checked in', 'text-hot'],
                $e['paused'] ?? false => ['Stepped out', 'text-hot'],
                $up => [$pair ? 'Up next · locked pair' : 'Up next', 'text-lime-text'],
                $pair => ['Locked pair', 'text-lime-text'],
                default => [null, null],
            };

            return [
                'pos' => $i + 1,
                'people' => $pair ? $people : $people->take(1),
                'label' => $pair ? $play->names($e['ids']) : $people->first()->name,
                'meta' => $meta,
                'pair' => $pair,
                'up' => $up,
                'metaClass' => $metaClass,
                'you' => $me && $people->contains(fn ($p) => $p->user_id === $me->id),
                'n' => count($e['ids']),
                'bg' => $people->map(fn ($p) => $p->queueBgClass())->first(fn ($c) => $c) ?? '',
                'framed' => $people->contains(fn ($p) => $p->frameClass()),
            ];
        });

        // The big screen shows the front of the line; a member far back still sees their own spot.
        $meAt = $queue->search(fn ($q) => $q['you']);
        $meRow = $meAt !== false && $meAt >= self::QUEUE_CAP ? $queue[$meAt] : null;
        $more = $queue->slice(self::QUEUE_CAP)->reject(fn ($q) => $q['you'])->sum('n');

        $results = $play->games()->with('players')->latest('played_at')->latest('id')->take(5)->get()->map(function ($g) use ($play) {
            $secs = now()->timestamp - $g->played_at->timestamp;

            return [
                'court' => $g->court,
                'ago' => $secs < 60 ? 'just now' : ago($secs).' ago',
                'a' => $play->names($g->players->where('team', 'a')->pluck('participant_id')->all()),
                'b' => $play->names($g->players->where('team', 'b')->pluck('participant_id')->all()),
                'sa' => $g->score_a,
                'sb' => $g->score_b,
                'aWon' => $g->winner === 'a',
            ];
        });

        return $view->with([
            'me' => $me,
            'standings' => $play->standings(),
            'myPid' => $me ? $play->participantMap()->firstWhere('user_id', $me->id)?->id : null,
            'courts' => $courts,
            'queue' => $queue->take(self::QUEUE_CAP),
            'meRow' => $meRow,
            'more' => $more,
            'queueCount' => $play->queuedPlayerCount(),
            'results' => $results,
            'next' => $next,
            'group' => $group,
            'skipped' => $group ? $play->skippedForNext($group) : ['', false],
            'where' => match (true) {
                (bool) $free => "Court {$free['n']} ".($next ? 'is open' : 'is waiting'),
                ($soon = $play->longestCourtIndex()) !== null => 'Courts full · Court '.$courts[$soon]['n'].' likely next',
                default => 'Next court to finish',
            },
            'busy' => $play->busyCourtCount().' of '.count($courts).' courts in play',
            'here' => $play->checkedInCount().' of '.count($play->sessionPlayerIds()).' checked in',
            'ago' => ago(now()->timestamp - ($play->started_at ?? now())->timestamp),
        ])->title("{$play->title} · Live · PickleX");
    }
}
