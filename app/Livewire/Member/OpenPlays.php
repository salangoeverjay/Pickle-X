<?php

namespace App\Livewire\Member;

use App\Livewire\Concerns\Pages;
use App\Models\OpenPlay;
use App\Support\Emails;
use App\Support\MemberStats;
use App\Support\Results;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Open plays · PickleX')]
class OpenPlays extends Component
{
    use Pages;

    // Open play whose payment QR dialog is showing.
    public ?int $payFor = null;

    // "See who's in" (upcoming) or "See who's here" (live): the open play, the search and the filter tab.
    public ?int $whoFor = null;

    public string $whoQ = '';

    public string $whoF = 'all';

    public function openPay(int $id): void
    {
        abort_unless($this->joinable($id)->hasPaymentQr(), 404);
        $this->payFor = $id;
    }

    public function closePay(): void
    {
        $this->payFor = null;
    }

    /** "I've sent the payment": noted for the organizer, who still confirms it. */
    public function claimPayment(int $id): void
    {
        abort_unless($this->joinable($id)->hasPaymentQr(), 404);
        DB::table('pay_claims')->insertOrIgnore(['open_play_id' => $id, 'user_id' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
        $this->payFor = null;
        $this->dispatch('toast', message: 'Thanks. The organizer will mark you as paid once they see it.');
    }

    public function joinPlay(int $id): void
    {
        $play = $this->joinable($id);
        // Leaving keeps the participant row, so a rejoin has one: skip the emails, they went out the first time.
        $rejoin = $play->participants()->where('user_id', auth()->id())->whereNotNull('left_at')->exists();

        try {
            $play->join(auth()->user());
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return;
        }

        $this->dispatch('toast', message: $play->isLive() ? "You're in the queue. Check in when you reach the courts." : "You're in. See you {$play->whenLabel()}.");

        if ($rejoin) {
            return;
        }
        $user = auth()->user();
        Emails::send($user, Emails::joined($play, $user));
        $organizer = $play->organizer;
        if ($organizer && $organizer->id !== $user->id && $organizer->pref('notify.joins')) {
            Emails::send($organizer, Emails::playerJoined($play, $user));
        }
    }

    public function leavePlay(int $id): void
    {
        $play = $this->joinable($id);
        $play->leave(auth()->user());

        $this->dispatch('toast', message: "You left {$play->title}");
    }

    /** "I'm at the courts": only checked-in players get called. */
    public function checkInPlay(int $id): void
    {
        $play = OpenPlay::status('live')->findOrFail($id);
        $me = $play->participants()->where('user_id', auth()->id())->whereNull('left_at')->firstOrFail();
        $play->checkIn([$me->id]);

        $this->dispatch('toast', message: 'Checked in at '.now()->format('g:i A').". You're in the rotation.");
    }

    /** Back from stepping out (or being subbed out): keeps the same spot in line. */
    public function stepBackIn(int $id): void
    {
        $play = OpenPlay::status('live')->findOrFail($id);
        $status = $play->statusFor(auth()->user());
        abort_unless(($status['state'] ?? null) === 'paused', 404);
        $play->setPaused($status['entry'], false);

        $this->dispatch('toast', message: "You're back in the rotation");
    }

    public function openWho(int $id): void
    {
        $this->whoFor = $this->joinable($id)->id;
        $this->whoQ = '';
        $this->whoF = 'all';
        $this->resetPage('whoPage');
    }

    public function closeWho(): void
    {
        $this->whoFor = null;
    }

    /** A new filter or search starts on page 1. */
    public function updated(string $property): void
    {
        if (in_array($property, ['whoQ', 'whoF'])) {
            $this->resetPage('whoPage');
        }
    }

    /**
     * The read-only player list: names as each player shares them, home clubs, who they've played with you
     * and, when live, where they are tonight. Never emails or payments.
     */
    private function who(): ?array
    {
        $play = $this->whoFor ? OpenPlay::public()->whereIn('status', ['live', 'upcoming'])->find($this->whoFor) : null;
        if (! $play) {
            return null;
        }
        $me = auth()->id();
        $live = $play->isLive();
        $stats = new MemberStats(auth()->user());
        $partner = $stats->partners->mapWithKeys(fn ($r) => [$r->user->id => $r->games]);
        $opponent = $stats->opponents->mapWithKeys(fn ($r) => [$r->user->id => $r->games]);
        $record = $live ? collect($play->standings())->keyBy('participant_id') : collect();

        $all = $play->whoIsIn()->map(function ($r) use ($me, $live, $partner, $opponent, $record, $play) {
            $p = $r['p'];
            $uid = $p->user_id;
            [$pa, $op] = [$uid === $me ? 0 : (int) $partner->get($uid), $uid === $me ? 0 : (int) $opponent->get($uid)];
            $with = $pa && $op ? "Partner {$pa}× · opponent {$op}×" : ($pa ? 'Your partner '.$pa.' '.Str::plural('time', $pa) : ($op ? 'Played against you '.$op.' '.Str::plural('time', $op) : ''));
            $rec = $record->get($p->id);
            $sub = ! $uid ? ($live ? 'Walk-in guest' : 'Guest · added by the organizer')
                : collect([$rec ? "{$rec['w']}-{$rec['l']} tonight" : $p->user?->club, $with])->filter()->join(' · ');

            return $r + ['guest' => ! $uid, 'me' => $uid && $uid === $me, 'host' => $uid && $uid === $play->organizer_id, 'played' => (bool) ($pa || $op), 'sub' => $sub];
        });

        $inLine = fn ($r) => in_array($r['st'], ['line', 'out']);
        $tabs = $live
            ? ['all' => 'All', 'court' => 'On court', 'line' => 'In line', 'away' => '<span class="lg:hidden">Not in yet</span><span class="hidden lg:inline">Not checked in</span>']
            : ['all' => 'All', 'played' => 'Played with you'] + ($all->contains('guest', true) ? ['guests' => 'Guests'] : []);
        $test = fn ($f) => fn ($r) => match ($f) {
            'court' => $r['st'] === 'court', 'line' => $inLine($r), 'away' => $r['st'] === 'away', 'played' => $r['played'], 'guests' => $r['guest'], default => true,
        };
        $counts = collect($tabs)->map(fn ($l, $f) => $all->filter($test($f))->count());
        $f = isset($tabs[$this->whoF]) ? $this->whoF : 'all';
        $q = mb_strtolower(trim($this->whoQ));
        // Names only: players never search by someone's full name or email.
        $rows = $all->filter($test($f))->filter(fn ($r) => $q === '' || str_contains(mb_strtolower($r['p']->name), $q))->values();

        return [
            'play' => $play, 'live' => $live, 'all' => $all, 'rows' => $rows, 'page' => $this->pageOf($rows, 10, 'whoPage'),
            'tabs' => $tabs, 'counts' => $counts, 'f' => $f, 'q' => trim($this->whoQ),
            'onCourt' => $counts['court'] ?? 0, 'inLine' => $all->filter($inLine)->count(), 'away' => $counts['away'] ?? 0, 'played' => $counts['played'] ?? 0,
            'joined' => $all->contains('me', true),
        ];
    }

    /** Members only act on public sessions that haven't ended. */
    private function joinable(int $id): OpenPlay
    {
        return OpenPlay::public()->whereIn('status', ['live', 'upcoming'])->findOrFail($id);
    }

    public function render()
    {
        $user = auth()->user();
        $joined = $user->participations()->whereNull('left_at')->pluck('open_play_id')->all();

        $plays = OpenPlay::public()->whereIn('status', ['live', 'upcoming'])
            ->withCount('activeParticipants')->orderBy('starts_at')
            // "Hosted by": the organizer and how many open plays they've run.
            ->with(['organizer' => fn ($q) => $q->withCount(['openPlays as hosted_count' => fn ($q) => $q->where('status', 'done')])])
            ->get();

        // "My status": the first live session (public or not) the member is standing in.
        $my = null;
        foreach (OpenPlay::status('live')->whereIn('id', $joined)->get() as $play) {
            if ($status = $play->statusFor($user)) {
                $index = $status['state'] === 'court' ? $status['index'] : $play->longestCourtIndex();
                $my = [
                    'play' => $play,
                    'status' => $status,
                    'court' => $index === null ? null : $play->boardCourts()[$index],
                    'index' => $index,
                ];
                break;
            }
        }

        return view('livewire.member.open-plays', [
            'my' => $my,
            // Never joined an open play: a short how-it-works banner above the list.
            'firstTime' => ! $user->participations()->exists(),
            'live' => $plays->where('status', 'live'),
            'upcoming' => $plays->where('status', 'upcoming'),
            'joined' => $joined,
            'paidIds' => $user->participations()->whereNull('left_at')->where('paid', true)->pluck('open_play_id')->all(),
            'claimedIds' => DB::table('pay_claims')->where('user_id', $user->id)->pluck('open_play_id')->all(),
            'who' => $this->who(),
            'payPlay' => $this->payFor ? $plays->firstWhere('id', $this->payFor) : null,
            'history' => $history = Results::memberHistory($user),
            'historyPage' => $this->pageOf($history['rows'], 5, 'pastPage'),
        ]);
    }
}
