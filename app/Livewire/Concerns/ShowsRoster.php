<?php

namespace App\Livewire\Concerns;

use App\Models\OpenPlay;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

/**
 * "See who's in" for an upcoming open play you host or co-host (members co-host from player mode):
 * the play, its search and filter tab. Only the host marks payments (setPaid lives on the organizer page).
 */
trait ShowsRoster
{
    #[Locked]
    public ?int $rosterId = null;

    public string $rosterQ = '';

    public string $rosterF = 'all';

    public function openRoster(int $id): void
    {
        $this->rosterId = OpenPlay::runBy(auth()->user())->status('upcoming')->findOrFail($id)->id;
        $this->rosterQ = '';
        $this->rosterF = 'all';
        $this->resetPage('rosterPage');
    }

    /** A new roster filter or search starts on page 1. */
    public function updatedRosterQ(): void
    {
        $this->resetPage('rosterPage');
    }

    public function updatedRosterF(): void
    {
        $this->resetPage('rosterPage');
    }

    public function closeRoster(): void
    {
        $this->rosterId = null;
    }

    /**
     * Everyone in the roster's open play, in the order they joined, with their payment state.
     * Filtered by $rosterF and $rosterQ in the view data; counts cover everyone.
     */
    private function roster(): ?array
    {
        if (! $this->rosterId) {
            return null;
        }
        $play = OpenPlay::runBy(auth()->user())->status('upcoming')->with('organizer')->findOrFail($this->rosterId);
        $free = ! $play->fee;
        $claims = DB::table('pay_claims')->where('open_play_id', $play->id)->pluck('user_id')->all();
        $all = $play->activeParticipants()->with('user')->orderBy('created_at')->orderBy('id')->get()->values()
            ->map(function (Participant $p, int $i) use ($claims, $free) {
                $sent = ! $free && ! $p->paid && $p->user_id && in_array($p->user_id, $claims);

                return [
                    'p' => $p, 'n' => $i + 1, 'guest' => ! $p->user_id,
                    'paid' => ! $free && $p->paid, 'sent' => $sent, 'owes' => ! $free && ! $p->paid,
                ];
            });

        $counts = ['all' => $all->count(), 'paid' => $all->where('paid', true)->count(), 'unpaid' => $all->where('owes', true)->count(), 'guests' => $all->where('guest', true)->count()];
        $counts['app'] = $counts['all'] - $counts['guests'];
        $tabs = $free ? ['all' => 'All', 'app' => 'PickleX accounts', 'guests' => 'Guests'] : ['all' => 'All', 'paid' => 'Paid', 'unpaid' => 'Unpaid', 'guests' => 'Guests'];
        $f = isset($tabs[$this->rosterF]) ? $this->rosterF : 'all';
        $q = mb_strtolower(trim($this->rosterQ));
        $rows = $all->filter(fn ($r) => match ($f) {
            'paid' => $r['paid'], 'unpaid' => $r['owes'], 'guests' => $r['guest'], 'app' => ! $r['guest'], default => true,
        } && ($q === '' || str_contains(mb_strtolower($r['p']->name.' '.$r['p']->fullName()), $q) || str_contains(mb_strtolower((string) $r['p']->user?->email), $q)))->values();

        return [
            'play' => $play, 'free' => $free, 'rows' => $rows, 'page' => $this->pageOf($rows, 10, 'rosterPage'), 'counts' => $counts, 'tabs' => $tabs, 'f' => $f, 'q' => trim($this->rosterQ),
            'fee' => (float) preg_replace('/[^\d.]/', '', (string) $play->fee),
            'sent' => $all->where('sent', true)->count(),
            'account' => $play->hasPaymentQr() ? $play->payMethodLabel() : 'account',
        ];
    }
}
