<?php

namespace App\Livewire;

use App\Models\OpenPlay;
use Livewire\Component;

/** Public, read-only board for a live open play (QR / share-link target). Names only, no contact details. */
class LiveView extends Component
{
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
        $next = $play->nextUpTeams();
        $courts = $play->boardCourts();
        $free = collect($courts)->first(fn ($c) => ! $c['a']);

        $queue = collect($play->queue())->map(function ($e, $i) use ($play, $ready, $next, $me) {
            $people = collect($e['ids'])->map(fn ($id) => $play->participant($id));
            $pair = $e['locked'] && count($e['ids']) === 2;
            $notHere = (bool) array_diff($e['ids'], $ready);

            [$meta, $metaClass] = match (true) {
                $notHere => ['Not checked in', 'text-hot'],
                $e['paused'] ?? false => ['Stepped out', 'text-hot'],
                in_array($e['id'], $next['used'] ?? []) => ['Up next', 'text-lime-text'],
                $pair => ['Locked partners', 'text-ink3'],
                default => [null, null],
            };

            return [
                'pos' => $i + 1,
                'people' => $pair ? $people : $people->take(1),
                'label' => $pair ? $play->names($e['ids']) : $people->first()->name,
                'meta' => $meta,
                'metaClass' => $metaClass,
                'you' => $me && $people->contains(fn ($p) => $p->user_id === $me->id),
            ];
        });

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
            'queue' => $queue,
            'queueCount' => $play->queuedPlayerCount(),
            'results' => $results,
            'next' => $next,
            'where' => $free ? "Court {$free['n']} is open" : 'Next court to finish',
            'busy' => $play->busyCourtCount().' of '.count($courts).' courts in play',
            'here' => $play->checkedInCount().' of '.count($play->sessionPlayerIds()).' checked in',
            'ago' => ago(now()->timestamp - ($play->started_at ?? now())->timestamp),
        ])->title("{$play->title} · Live · PickleX");
    }
}
