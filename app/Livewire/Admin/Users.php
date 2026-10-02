<?php

namespace App\Livewire\Admin;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Concerns\Pages;
use App\Models\GamePlayer;
use App\Models\User;
use App\Support\Emails;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Admin › Users (admins): every account, with roles, password resets, sign-outs, suspension and deletion. */
#[Title('Users · PickleX')]
class Users extends Component
{
    use Pages;

    public const ROLES = ['member' => 'Member', 'organizer' => 'Organizer', 'admin' => 'Admin'];

    #[Url(except: 'all')]
    public string $tab = 'all'; // all | member | organizer | admin | suspended

    public string $q = '';

    // The Manage dialog: the account, a role picked but not saved yet, and the delete confirmation.
    public ?int $manage = null;

    public ?string $role = null;

    public bool $confirmDelete = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function open(int $id): void
    {
        $this->manage = $this->account($id)->id;
        $this->role = null;
        $this->confirmDelete = false;
    }

    public function close(): void
    {
        $this->reset('manage', 'role', 'confirmDelete');
    }

    public function pickRole(string $role): void
    {
        if (isset(self::ROLES[$role]) && ! $this->isSelf()) {
            $this->role = $role;
        }
    }

    public function saveRole(): void
    {
        $user = $this->other();
        $was = $user->accountRole();
        if (! $this->role || $this->role === $was) {
            return;
        }
        $user->forceFill(match ($this->role) {
            'member' => ['role' => 'member', 'organizer_status' => $user->organizer_status === 'approved' ? null : $user->organizer_status],
            'organizer' => ['role' => 'member', 'organizer_status' => 'approved'],
            'admin' => ['role' => 'admin'],
        })->save();
        Emails::send($user, Emails::roleChanged($user, $was, $this->role));
        $label = self::ROLES[$this->role];
        $this->role = null;
        $this->dispatch('toast', message: "{$user->name} is now ".(in_array($label, ['Admin', 'Organizer']) ? 'an ' : 'a ').$label." · we emailed {$user->firstName()}");
    }

    public function sendReset(): void
    {
        $user = $this->account($this->manage);
        ForgotPassword::sendCode($user, 'Sent by a PickleX admin');
        $this->dispatch('toast', message: "Password reset email sent to {$user->email}");
    }

    public function signOut(): void
    {
        $user = $this->other();
        $user->signOutEverywhere();
        $this->dispatch('toast', message: "Signed {$user->firstName()} out on every device · Remember me cleared");
    }

    public function suspend(): void
    {
        $user = $this->other();
        $user->forceFill(['suspended_at' => now()])->save();
        $user->signOutEverywhere();
        $user->releaseUpcomingSpots();
        Emails::send($user, Emails::accountSuspended($user));
        $this->dispatch('toast', message: "{$user->name} is suspended and can't log in · we emailed {$user->firstName()}");
    }

    public function restore(): void
    {
        $user = $this->other();
        $user->forceFill(['suspended_at' => null])->save();
        $this->dispatch('toast', message: "{$user->name} can log in again");
    }

    /** Their logged games stay in other players' history as Former player. */
    public function delete(): void
    {
        $user = $this->other();
        $mail = Emails::accountDeleted($user, byAdmin: true);
        $user->deleteAccount();
        Emails::send($user->email, $mail);
        $this->close();
        $this->dispatch('toast', message: "Deleted {$user->name}'s account · we emailed {$user->firstName()}");
    }

    /** A new tab or search starts on page 1. */
    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'q'])) {
            $this->resetPage('usersPage');
        }
    }

    public function render()
    {
        $all = User::query()
            ->addSelect(['last_activity' => DB::table('sessions')->selectRaw('max(last_activity)')->whereColumn('user_id', 'users.id')])
            ->get();
        $filters = [
            'all' => fn (User $u) => true,
            'member' => fn (User $u) => $u->accountRole() === 'member',
            'organizer' => fn (User $u) => $u->accountRole() === 'organizer',
            'admin' => fn (User $u) => $u->isAdmin(),
            'suspended' => fn (User $u) => $u->isSuspended(),
        ];
        $count = fn (string $tab) => $all->filter($filters[$tab])->count();
        $q = Str::lower(trim($this->q));
        $rows = $all->filter($filters[$this->tab] ?? $filters['all'])
            ->filter(fn (User $u) => $q === '' || Str::contains(Str::lower($u->name.' '.$u->email), $q))
            ->sortBy([fn ($a, $b) => ($b->id === auth()->id()) <=> ($a->id === auth()->id()), ['name', 'asc']])
            ->values();
        $games = GamePlayer::whereIn('user_id', $all->pluck('id'))->selectRaw('user_id, count(*) as n')->groupBy('user_id')->pluck('n', 'user_id');
        $managed = $this->manage ? $all->firstWhere('id', $this->manage) : null;

        return view('livewire.admin.users', [
            'rows' => $rows,
            'rowsPage' => $this->pageOf($rows, 10, 'usersPage'),
            'total' => $all->count(),
            'tabs' => collect(['all' => 'All', 'member' => 'Members', 'organizer' => 'Organizers', 'admin' => 'Admins', 'suspended' => 'Suspended'])->map(fn ($label, $tab) => [$label, $count($tab)]),
            'tiles' => [['Accounts', $all->count()], ['Organizers', $count('organizer')], ['Admins', $count('admin')], ['Suspended', $count('suspended')]],
            'games' => $games,
            'managed' => $managed,
            'draft' => $managed ? ($this->role ?? $managed->accountRole()) : null,
            'lastActive' => fn (User $u) => self::when($u->last_activity ? now()->setTimestamp($u->last_activity) : $u->updated_at),
            'descriptions' => [
                'member' => 'Joins open plays, checks in and tracks stats.',
                'organizer' => 'Also hosts open plays and runs the court board for '.($managed?->club ?: 'their club').'.',
                'admin' => 'Reviews organizer requests and manages users, developers and sponsors.',
            ],
        ]);
    }

    /** "Today", "Yesterday", "3 days ago", "Sep 24". */
    public static function when(?CarbonInterface $at): string
    {
        return match (true) {
            ! $at => 'Never',
            $at->isToday() => 'Today',
            $at->isYesterday() => 'Yesterday',
            $at->greaterThan(today()->subDays(7)) => (int) $at->copy()->startOfDay()->diffInDays(today()).' days ago',
            default => $at->format('M j'),
        };
    }

    private function account(?int $id): User
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return User::findOrFail($id);
    }

    /** The managed account, never your own (another admin changes, suspends or deletes you). */
    private function other(): User
    {
        abort_if($this->isSelf(), 403);

        return $this->account($this->manage);
    }

    private function isSelf(): bool
    {
        return $this->manage === auth()->id();
    }
}
