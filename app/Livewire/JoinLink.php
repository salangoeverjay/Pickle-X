<?php

namespace App\Livewire;

use App\Models\OpenPlay;
use App\Models\Participant;
use App\Models\User;
use App\Support\Emails;
use InvalidArgumentException;
use Livewire\Component;

/**
 * The page a share link opens (/j/{code}). Logged in: you're on the list as soon as it opens.
 * Logged out: join as a guest (if the organizer allows guests), create an account and join, or log in and join.
 * A guest who then creates an account or logs in has their spot moved to the account.
 */
class JoinLink extends Component
{
    public string $code;

    /** choose | guest (the guest form) */
    public string $step = 'choose';

    public string $name = '';

    public string $email = '';

    public string $error = '';

    /** Joined in this visit (the page says "You're in" instead of "You're already in"). */
    public bool $just = false;

    public function mount(string $code): void
    {
        $this->code = $code;
        $play = $this->play();
        // Logged in: added straight away, unless you host or co-host it (a co-host can still join as a player).
        if ($play && auth()->check() && ! $play->isRunBy(auth()->user())) {
            session()->forget('join');
            $this->joinAsUser($play);
        }
    }

    private function play(): ?OpenPlay
    {
        return OpenPlay::with('organizer')->where('link_code', $this->code)->first();
    }

    /** This visitor's guest spot on $play, kept in the session. */
    private function guestSpot(OpenPlay $play): ?Participant
    {
        $id = session("join_guests.{$play->id}");

        return $id ? $play->activeParticipants()->whereNull('user_id')->find($id) : null;
    }

    private function joinAsUser(OpenPlay $play): void
    {
        $user = auth()->user();
        if ($play->status === 'done' || $play->organizer_id === $user->id) {
            return;
        }
        $guest = $this->guestSpot($play);
        if ($play->activeParticipants()->where('user_id', $user->id)->exists()) {
            return;
        }
        // Their guest spot from this visit moves to the account.
        $guest?->update(['left_at' => now()]);
        session()->forget("join_guests.{$play->id}");
        $rejoin = $play->participants()->where('user_id', $user->id)->whereNotNull('left_at')->exists();

        try {
            $play->join($user);
        } catch (InvalidArgumentException) {
            $guest?->update(['left_at' => null]);

            return;
        }

        $this->just = true;
        $this->dispatch('toast', message: $guest ? 'Your guest spot moved to your account' : "You're in. See you {$play->whenLabel()}");
        if (! $rejoin && ! $guest) {
            Emails::send($user, Emails::joined($play, $user));
            if ($play->organizer && $play->organizer->pref('notify.joins')) {
                Emails::send($play->organizer, Emails::playerJoined($play, $user));
            }
        }
    }

    public function join(): void
    {
        abort_unless(auth()->check() && ($play = $this->play()), 404);
        $this->joinAsUser($play);
    }

    public function pickGuest(): void
    {
        $this->step = 'guest';
        $this->error = '';
    }

    public function back(): void
    {
        $this->step = 'choose';
        $this->error = '';
    }

    public function joinGuest(): void
    {
        $play = $this->play() ?? abort(404);
        $name = preg_replace('/\s+/', ' ', trim($this->name));
        $email = trim($this->email);
        if (mb_strlen($name) < 2) {
            $this->error = 'Add your name so the organizer can find you at check-in.';

            return;
        }
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error = 'That email address does not look right. Fix it or leave it empty.';

            return;
        }

        try {
            $guest = $play->addLinkGuest(mb_substr($name, 0, 60), $email ?: null);
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();

            return;
        }

        session()->put("join_guests.{$play->id}", $guest->id);
        $this->error = '';
        $this->dispatch('toast', message: "You're in as a guest · see you {$play->whenLabel()}");
        if ($email) {
            Emails::send($email, Emails::joined($play, new User(['name' => $name, 'email' => $email])));
        }
    }

    public function leave(): void
    {
        $play = $this->play() ?? abort(404);
        if (auth()->check()) {
            $play->leave(auth()->user());
        } elseif ($guest = $this->guestSpot($play)) {
            $guest->update(['left_at' => now()]);
            session()->forget("join_guests.{$play->id}");
        }
        $this->just = false;
        $this->step = 'choose';
        $this->dispatch('toast', message: "You left {$play->title}");
    }

    /** Log in or sign up, then come back here and join. A guest's name and email carry over to the sign-up form. */
    public function signup()
    {
        $play = $this->play() ?? abort(404);
        $guest = $this->guestSpot($play);
        session()->put('join', ['code' => $this->code, 'name' => $guest?->name ?? trim($this->name), 'email' => $guest?->email ?? trim($this->email)]);

        return $this->redirectRoute('signup', navigate: true);
    }

    public function login()
    {
        session()->put('join', ['code' => $this->code]);
        session()->put('url.intended', route('join', $this->code));

        return $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        $play = $this->play();
        $user = auth()->user();
        if (! $play) {
            return view('livewire.join-link', ['play' => null])->title("This link doesn't work anymore · PickleX");
        }

        $joined = $user && $play->activeParticipants()->where('user_id', $user->id)->exists();
        $guest = $user ? null : $this->guestSpot($play);
        $count = $play->playerCount();
        $left = max(0, $play->cap - $count);
        $full = ! $left && ! $joined && ! $guest;
        $state = match (true) {
            $play->status === 'done' => 'ended',
            $user && ($play->organizer_id === $user->id || ($play->isCohost($user) && ! $joined)) => 'mine',
            (bool) $user => $joined ? 'in' : ($full ? 'full' : 'out'),
            (bool) $guest => 'guestIn',
            $full => 'full',
            $this->step === 'guest' && $play->link_guests && ! $play->isLive() => 'guest',
            default => 'choose',
        };
        $me = $user ? $play->activeParticipants()->where('user_id', $user->id)->first() : $guest;

        return view('livewire.join-link', [
            'play' => $play,
            'state' => $state,
            'guest' => $guest,
            'count' => $count,
            'left' => $left,
            'faces' => $play->whoIsIn()->take(6),
            'seesNames' => $joined || $guest,
            'owes' => in_array($state, ['in', 'guestIn']) && $play->fee && $play->hasPaymentQr() && ! $me?->paid,
        ])->title("{$play->title} · Join · PickleX");
    }
}
