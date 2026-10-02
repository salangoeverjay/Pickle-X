<?php

namespace App\Support;

use App\Mail\PickleXMail;
use App\Models\OpenPlay;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Builds and sends every PickleX email (templates in resources/views/emails).
 * Mail goes out after the response, so a slow or failing mail server never
 * holds up or breaks the page; failures are reported to the log.
 */
class Emails
{
    public static function send(User|string $to, PickleXMail $mail): void
    {
        // Namespaced on purpose: some PHP builds (Swoole in Sail) define a global defer().
        \Illuminate\Support\defer(fn () => rescue(fn () => Mail::to($to)->send($mail)));
    }

    // ------------------------------------------------------------ account

    public static function reset(User $user, string $code, CarbonInterface $expires, string $device): PickleXMail
    {
        return new PickleXMail('reset', 'Reset your PickleX password', "Your code is {$code}. It expires in 10 minutes.", [
            'user' => $user,
            'code' => $code,
            'expires' => $expires,
            'device' => $device,
            'link' => route('password.request', ['email' => $user->email, 'code' => $code]),
        ], security: true);
    }

    public static function welcome(User $user): PickleXMail
    {
        $plays = OpenPlay::status('upcoming')->public()->where('starts_at', '>', now())
            ->withCount('activeParticipants')->orderBy('starts_at')->limit(6)->get()
            ->filter(fn ($p) => $p->active_participants_count < $p->cap)->take(2);

        return new PickleXMail('welcome', "Welcome to PickleX, {$user->firstName()}",
            "Join an open play, check in at the courts, and we'll tell you when you're up.", ['user' => $user, 'plays' => $plays]);
    }

    public static function organizerApproved(User $user): PickleXMail
    {
        return new PickleXMail('organizer-approved', "You're approved to host open plays on PickleX",
            "{$user->club} is ready. Create your first open play in about two minutes.", ['user' => $user]);
    }

    public static function organizerDeclined(User $user): PickleXMail
    {
        return new PickleXMail('organizer-declined', "About your organizer request for {$user->club}",
            "We couldn't approve it this time. You can still play, and you can apply again any time.", ['user' => $user]);
    }

    public static function passkeyAdded(User $user, string $name, string $device): PickleXMail
    {
        return new PickleXMail('passkey-added', 'A passkey was added to your PickleX account',
            "{$name} can now log in to {$user->email} without your password.", [
                'user' => $user, 'name' => $name, 'device' => $device, 'at' => now(), 'count' => $user->passkeys()->count(),
            ], security: true);
    }

    public static function passkeyRemoved(User $user, string $name, CarbonInterface $added, string $device): PickleXMail
    {
        return new PickleXMail('passkey-removed', 'A passkey was removed from your PickleX account',
            "{$name} can't log in to {$user->email} anymore. Your password still works.", [
                'user' => $user, 'name' => $name, 'added' => $added, 'device' => $device, 'at' => now(), 'left' => $user->passkeys()->count(),
            ], security: true);
    }

    /** $was and $role: member | organizer | admin (User::accountRole()). */
    public static function roleChanged(User $user, string $was, string $role): PickleXMail
    {
        $label = ['member' => 'Member', 'organizer' => 'Organizer', 'admin' => 'Admin'][$role];

        return new PickleXMail('role-changed', "Your PickleX role changed to {$label}", match ($role) {
            'organizer' => 'A PickleX admin made you an Organizer for '.($user->club ?: 'your club').'. You can host open plays now.',
            'admin' => 'A PickleX admin made you an Admin. You can review organizer requests and manage accounts now.',
            default => 'A PickleX admin made you a Member. You still play on the same account.',
        }, ['user' => $user, 'was' => $was, 'role' => $role]);
    }

    public static function accountSuspended(User $user): PickleXMail
    {
        return new PickleXMail('account-suspended', 'Your PickleX account is suspended',
            "You can't log in or join open plays for now. Here's what that means, and how to ask for another look.", ['user' => $user]);
    }

    /** Built before the account is deleted; send it to the address, not the gone account. */
    public static function accountDeleted(User $user, bool $byAdmin): PickleXMail
    {
        return $byAdmin
            ? new PickleXMail('account-deleted', 'Your PickleX account was deleted',
                "A PickleX admin deleted {$user->email}. You can't log in with it anymore.", ['user' => $user, 'byAdmin' => true, 'at' => now()])
            : new PickleXMail('account-deleted', 'Your PickleX account is deleted',
                "You deleted {$user->email}. Thanks for playing with us.", ['user' => $user, 'byAdmin' => false, 'at' => now()]);
    }

    // ------------------------------------------------------------ open plays

    public static function newPlay(OpenPlay $play, User $to): PickleXMail
    {
        $count = $play->playerCount();

        return new PickleXMail('new-play', "New open play: {$play->title}, {$play->starts_at->format('D M j')}",
            "{$play->timeRange()} at {$play->venue}. {$count} of {$play->cap} spots taken.",
            ['play' => $play, 'user' => $to, 'count' => $count, 'going' => $play->activeParticipants()->latest('id')->limit(4)->get()],
            fromName: "{$play->club} via PickleX");
    }

    public static function joined(OpenPlay $play, User $user): PickleXMail
    {
        $paid = (bool) $play->activeParticipants()->where('user_id', $user->id)->value('paid');
        $owes = $play->fee && ! $paid;
        $pre = $owes && $play->hasPaymentQr()
            ? "Pay {$play->fee}".($play->pay_method === 'other' ? '' : " with {$play->payMethodLabel()}").' before you play, then check in when you get to the courts.'
            : "Check in when you get to the courts so you're added to the rotation.";

        return new PickleXMail('joined', "You're in: {$play->title}, {$play->starts_at->format('D M j')}", $pre, [
            'play' => $play, 'user' => $user, 'owes' => $owes, 'position' => $play->playerCount(),
        ]);
    }

    /**
     * An open play someone joined changed. $changes: review rows with label, value (new) and was.
     * Only-the-time moves read as "New time"; anything else is "Updated".
     */
    public static function playUpdated(OpenPlay $play, User $user, array $changes): PickleXMail
    {
        $paid = (bool) $play->activeParticipants()->where('user_id', $user->id)->value('paid');
        $timeOnly = count($changes) === 1 && $changes[0]['key'] === 'when';
        $start = $play->starts_at->format('g:i A');
        $subject = $timeOnly ? "New time: {$play->title} now starts at {$start}" : "Updated: {$play->title} on {$play->starts_at->format('D, M j')}";
        $pre = "{$play->starts_at->format('D, M j')} · {$play->timeRange()} at {$play->venue}. You're still in".($paid && $play->fee ? ' and your payment still counts.' : '.');

        return new PickleXMail('play-updated', $subject, $pre, [
            'play' => $play, 'user' => $user, 'changes' => $changes, 'paid' => $paid, 'timeOnly' => $timeOnly, 'start' => $start,
            'host' => $play->organizer?->name ?? $play->club, 'count' => $play->playerCount(),
        ], fromName: "{$play->club} via PickleX");
    }

    /** An open play someone joined was deleted. Built before the delete, so it only uses loaded data. */
    public static function playCancelled(OpenPlay $play, User $user, bool $paid): PickleXMail
    {
        $host = $play->organizer?->name ?? $play->club;
        $hostFirst = Str::before($host, ' ');
        $owed = $paid && $play->fee;
        $others = OpenPlay::status('upcoming')->public()->whereKeyNot($play->id)->where('starts_at', '>', now())
            ->withCount('activeParticipants')->orderBy('starts_at')->limit(6)->get()
            ->filter(fn ($p) => $p->active_participants_count < $p->cap)->take(2)->values();

        return new PickleXMail('play-cancelled', "Cancelled: {$play->title} on {$play->starts_at->format('D, M j')}",
            "{$host} cancelled this open play.".($owed ? " Your refund for the {$play->fee} fee comes from {$hostFirst} directly." : ''),
            ['play' => $play, 'user' => $user, 'host' => $host, 'hostFirst' => $hostFirst, 'owed' => $owed, 'others' => $others,
                'method' => $play->hasPaymentQr() ? $play->payMethodLabel() : null],
            fromName: "{$play->club} via PickleX");
    }

    public static function playerJoined(OpenPlay $play, User $player): PickleXMail
    {
        $count = $play->playerCount();
        $paid = (bool) $play->activeParticipants()->where('user_id', $player->id)->value('paid');
        $pre = "{$count} of {$play->cap} spots filled.".($play->fee && ! $paid ? " {$player->firstName()} hasn't paid yet." : '');

        return new PickleXMail('player-joined', "{$player->name} joined {$play->title}", $pre, [
            'play' => $play,
            'player' => $player,
            'paid' => $paid,
            'count' => $count,
            'stats' => new MemberStats($player),
            'latest' => $play->activeParticipants()->with('user')->latest('id')->limit(3)->get(),
            'paidCount' => $play->activeParticipants()->where('paid', true)->count(),
            'claims' => DB::table('pay_claims')->where('open_play_id', $play->id)->count(),
        ]);
    }

    public static function recap(OpenPlay $play, User $user, Results $results): ?PickleXMail
    {
        $pid = $play->participants()->where('user_id', $user->id)->value('id');
        $mine = $results->mine($pid);
        if (! $mine) {
            return null;
        }

        $medal = $mine['rank'] <= 3 ? strtolower(Results::MEDALS[$mine['rank'] - 1][1]) : null;
        $next = OpenPlay::status('upcoming')->where('starts_at', '>', now())
            ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $user->id))->orderBy('starts_at')->first();

        return new PickleXMail('recap', "Your {$play->title} recap: ".Results::ordinal($mine['rank'])." of {$mine['of']}",
            "{$mine['w']}-{$mine['l']} with ".Results::signed($mine['diff']).' points'.($medal ? " and a {$medal} finish" : '').'. See where everyone landed.',
            ['play' => $play, 'user' => $user, 'results' => $results, 'mine' => $mine, 'pid' => $pid, 'next' => $next]);
    }

    /** Recap emails once a session ends: one per player who logged games (unless turned off), one for the organizer. */
    public static function sendRecaps(OpenPlay $play): void
    {
        $results = new Results($play);
        $ids = collect($results->rows)->pluck('participant_id');
        $play->participants()->whereIn('id', $ids)->whereNotNull('user_id')->with('user')->get()
            ->pluck('user')->filter(fn ($u) => $u && $u->pref('notify.sessionRecap'))
            ->each(function (User $u) use ($play, $results) {
                if ($mail = self::recap($play, $u, $results)) {
                    self::send($u, $mail);
                }
            });

        if ($play->organizer) {
            self::send($play->organizer, self::recapOrganizer($play, $results));
        }
    }

    public static function recapOrganizer(OpenPlay $play, Results $results): PickleXMail
    {
        $players = $results->players();
        $winner = $results->rows ? $play->participant($results->rows[0]['participant_id'])?->firstName() : null;
        $money = self::collected($play, $results);
        $pre = ($winner ? "{$winner} won the night." : 'No games were logged.')
            .($money ? " {$money['total']} collected from {$money['paid']} of {$players} players." : '');

        return new PickleXMail('recap-organizer', "{$play->title} recap: {$players} players, {$results->log->count()} games", $pre,
            ['play' => $play, 'results' => $results, 'players' => $players, 'money' => $money]);
    }

    /** To the organizer: a session nobody ended closed on its own 12 hours after its end time. */
    public static function autoEnded(OpenPlay $play): PickleXMail
    {
        $games = $play->games()->count();
        $end = $play->ends_at->format('g:i A');

        return new PickleXMail('auto-ended', "{$play->title} ended automatically",
            'It was still live '.OpenPlay::AUTO_END_HOURS." hours after its {$end} end time. All {$games} ".Str::plural('game', $games).' and the standings are saved.', [
                'play' => $play, 'games' => $games, 'end' => $end, 'at' => $play->ended_at->format('g:i A'),
                'players' => $play->activeParticipants()->count(),
                'checkedIn' => $play->activeParticipants()->whereNotNull('checked_in_at')->count(),
                'ran' => max(1, (int) round($play->started_at->diffInMinutes($play->ended_at) / 60)).'h',
            ]);
    }

    /**
     * Fees for a paid session: {total "₱2,850", paid, unpaid names of players who played}.
     * Null when the open play is free or the fee isn't a number.
     */
    public static function collected(OpenPlay $play, Results $results): ?array
    {
        $fee = (float) preg_replace('/[^\d.]/', '', (string) $play->fee);
        if ($fee <= 0) {
            return null;
        }

        $played = collect($results->rows)->pluck('participant_id');
        $paid = $play->participants()->where('paid', true)->count();

        return [
            'total' => '₱'.number_format($fee * $paid, fmod($fee, 1) ? 2 : 0),
            'paid' => $paid,
            'unpaid' => $play->participants()->whereIn('id', $played)->where('paid', false)->pluck('name')->all(),
        ];
    }

    /** Google Calendar "add event" link. */
    public static function calendarUrl(OpenPlay $play): string
    {
        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $play->title,
            'dates' => $play->starts_at->copy()->utc()->format('Ymd\THis\Z').'/'.$play->ends_at->copy()->utc()->format('Ymd\THis\Z'),
            'location' => $play->venue,
            'details' => "{$play->club} open play on PickleX",
        ]);
    }
}
