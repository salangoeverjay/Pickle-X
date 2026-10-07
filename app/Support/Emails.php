<?php

namespace App\Support;

use App\Livewire\Admin\OpenPlays;
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

    /** A notification switch email: sent to each of $users who keeps notify.$pref on (skips suspended accounts). $build(User) may return null. */
    public static function notify(iterable $users, string $pref, \Closure $build): void
    {
        foreach ($users as $user) {
            if ($user && $user->pref("notify.{$pref}") && ! $user->isSuspended() && ($mail = $build($user))) {
                self::send($user, $mail);
            }
        }
    }

    /** Last week, Monday to Sunday: [start, end]. The weekly emails go out the Monday after. */
    public static function lastWeek(): array
    {
        $start = now()->startOfWeek(CarbonInterface::MONDAY)->subWeek();

        return [$start, $start->copy()->endOfWeek(CarbonInterface::SUNDAY)];
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
        $hosting = $role === 'member' ? OpenPlay::where('organizer_id', $user->id)->status('upcoming')
            ->where('starts_at', '>', now())->orderBy('starts_at')->get() : collect();

        return new PickleXMail('role-changed', "Your PickleX role changed to {$label}", match ($role) {
            'organizer' => 'A PickleX admin made you an Organizer for '.($user->club ?: 'your club').'. You can host open plays now.',
            'admin' => 'You can manage organizer requests, users, developers and sponsors now.',
            default => "You can still join open plays. Here's what stays and what changes.",
        }, ['user' => $user, 'was' => $was, 'role' => $role, 'by' => auth()->user()?->name, 'hosting' => $hosting]);
    }

    /** Built after User::suspend(): the reason, how long and the admin's note. */
    public static function accountSuspended(User $user): PickleXMail
    {
        $until = $user->suspended_until;
        $days = $until ? (int) round($user->suspended_at->copy()->startOfDay()->diffInDays($until)) : null;
        $key = isset(User::SUSPENSION_REASONS[$user->suspension_reason]) ? $user->suspension_reason : 'other';
        $why = $key === 'spam' ? 'spam' : $user->suspensionReason()[2];
        $for = $until ? "Suspended for {$days} days for {$why}." : "Suspended for {$why} until an admin restores it.";

        return new PickleXMail('account-suspended', ($key === 'fake' ? 'This' : 'Your').' PickleX account is suspended'.($until ? ' until '.$until->format('M j') : ''), match ($key) {
            'fake' => "It looks like a second account. Here's what to do if it isn't.",
            'unpaid' => "{$for} Settle up and it can lift sooner.",
            'spam' => "{$for} Your display name goes back to your full name.",
            'other' => ($until ? "Suspended for {$days} days." : 'Suspended until an admin restores it.')." The admin's note explains what happened.",
            default => $for.($until ? " Here's what that means and how to ask for another look." : ' Reply to tell us your side.'),
        }, [
            'user' => $user, 'at' => $user->suspended_at, 'until' => $until, 'days' => $days, 'key' => $key,
            'reason' => $user->suspensionReason(), 'note' => $user->suspension_note, 'by' => $user->suspender?->name,
        ]);
    }

    /**
     * Built before User::liftSuspension() clears the details. $early: an admin restored it (not the end date).
     * Three versions: auto (ended on its own), early (lifted before its end date), open (lifted, no end date).
     */
    public static function accountRestored(User $user, bool $early): PickleXMail
    {
        $back = now();
        $kind = ! $early ? 'auto' : ($user->suspended_until ? 'early' : 'open');

        return new PickleXMail('account-restored', $kind === 'auto' ? 'Your PickleX suspension ended' : 'Your PickleX account is active again', match ($kind) {
            'auto' => "You're active again. Log in and join an open play anytime.",
            'early' => 'Your suspension was lifted early. Log in and join an open play anytime.',
            'open' => 'An admin took another look and lifted the suspension.',
        }, [
            'user' => $user, 'early' => $early, 'kind' => $kind, 'at' => $user->suspended_at, 'until' => $user->suspended_until, 'back' => $back,
            'days' => max(1, (int) round($user->suspended_at->copy()->startOfDay()->diffInDays($back->copy()->startOfDay()))),
            'reason' => $user->suspensionReason(), 'reasonKey' => $user->suspension_reason, 'by' => auth()->user()?->name,
        ]);
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

    /** $user was added as (or removed from being) a co-host of $play. */
    public static function cohost(OpenPlay $play, User $user, bool $added): PickleXMail
    {
        $host = $play->organizer;

        return $added
            ? new PickleXMail('cohost', "You're co-hosting {$play->title}", "{$host->name} added you. You can start it on {$play->starts_at->format('D, M j')} and run the court board.",
                ['play' => $play, 'user' => $user, 'host' => $host, 'added' => true])
            : new PickleXMail('cohost', "You're no longer co-hosting {$play->title}", "{$host->name} removed you as a co-host.".(($me = $play->activeParticipants()->where('user_id', $user->id)->first()) ? " You're still on the player list for {$play->starts_at->format('D, M j')}." : ''),
                ['play' => $play, 'user' => $user, 'host' => $host, 'added' => false, 'joined' => (bool) $me, 'paid' => (bool) $me?->paid]);
    }

    /** To the host: an admin deleted their concluded open play. Built before the delete. $why: test | dup | other. */
    public static function playDeleted(OpenPlay $play, string $why, int $games, int $players): PickleXMail
    {
        $because = OpenPlays::REASONS[$why][2] ?? null;
        $count = $games.' '.Str::plural('game', $games);

        return new PickleXMail('play-deleted', "Your open play “{$play->title}” was deleted",
            ($because ? 'It was '.$because.", so its {$count}" : "Its {$count}")." no longer count toward anyone's stats.",
            ['play' => $play, 'user' => $play->organizer, 'why' => $why, 'because' => $because, 'games' => $games, 'players' => $players]);
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

    // ------------------------------------------------------------ notification switches

    /** To the organizer (notify.payClaims): a player tapped "I've sent the payment". */
    public static function payClaimed(OpenPlay $play, User $player): PickleXMail
    {
        $claims = DB::table('pay_claims')->where('open_play_id', $play->id)->count();

        return new PickleXMail('pay-claimed', "{$player->name} says they paid for {$play->title}",
            "Check {$play->payApp()} for ".($play->fee ?: 'the payment')." from {$player->name}, then mark them as paid.", [
                'play' => $play, 'player' => $player, 'claims' => $claims, 'at' => now(),
                'paidCount' => $play->activeParticipants()->where('paid', true)->count(), 'count' => $play->playerCount(),
            ]);
    }

    /** To the organizer (notify.reminder): one hour before the open play starts. */
    public static function sessionReminder(OpenPlay $play): PickleXMail
    {
        $count = $play->playerCount();
        $paid = $play->activeParticipants()->where('paid', true)->count();
        $claims = DB::table('pay_claims')->where('open_play_id', $play->id)->count();

        return new PickleXMail('session-reminder', "{$play->title} starts at {$play->starts_at->format('g:i A')}",
            "{$count} of {$play->cap} players joined".($play->fee ? " · {$paid} paid" : '').($claims ? " · {$claims} to confirm" : '').'. Start the session when you get to the courts.',
            ['play' => $play, 'count' => $count, 'paid' => $paid, 'claims' => $claims]);
    }

    /** To each player in it (notify.liveStart): the organizer started the session. */
    public static function liveStarted(OpenPlay $play, User $user): PickleXMail
    {
        $owes = $play->fee && ! $play->activeParticipants()->where('user_id', $user->id)->value('paid');

        return new PickleXMail('live-started', "{$play->title} is live",
            "Head to {$play->venue} and check in when you get there so you're added to the rotation.",
            ['play' => $play, 'user' => $user, 'owes' => $owes, 'count' => $play->playerCount()],
            fromName: "{$play->club} via PickleX");
    }

    /** To a player who just moved into the next full group (notify.upNext). $group: OpenPlay::nextGroup(), $pid: their participant id. */
    public static function upNext(OpenPlay $play, User $user, array $group, int $pid): PickleXMail
    {
        $mine = in_array($pid, $group['a']['ids']) ? $group['a'] : $group['b'];
        $theirs = $mine === $group['a'] ? $group['b'] : $group['a'];
        $partner = $play->names(array_values(array_diff($mine['ids'], [$pid])));
        $opponents = $play->names($theirs['ids']);

        return new PickleXMail('up-next', "You're up next at {$play->title}",
            ($partner ? "With {$partner} vs " : 'Against ')."{$opponents}. You go on as soon as a court opens.",
            ['play' => $play, 'user' => $user, 'partner' => $partner, 'opponents' => $opponents]);
    }

    /** To admins (notify.requests): someone applied to host open plays. */
    public static function organizerRequest(User $applicant): PickleXMail
    {
        return new PickleXMail('organizer-request', "New organizer request: {$applicant->club}",
            "{$applicant->name} wants to host open plays in {$applicant->city}. Review it in PickleX.",
            ['applicant' => $applicant, 'stats' => new MemberStats($applicant)]);
    }

    /** Monday recap for a member who played last week (notify.recap). Null when they didn't play. */
    public static function weeklyRecap(User $user): ?PickleXMail
    {
        [$from, $to] = self::lastWeek();
        $games = DB::table('game_players')->join('games', 'games.id', '=', 'game_players.game_id')
            ->where('game_players.user_id', $user->id)->whereBetween('games.played_at', [$from, $to])
            ->get(['game_players.won', 'games.open_play_id']);
        if ($games->isEmpty()) {
            return null;
        }

        $wins = $games->where('won', true)->count();
        $losses = $games->count() - $wins;
        $rate = (int) round($wins * 100 / $games->count());
        $club = MemberStats::homeTable($user);
        $rank = MemberStats::rankOf($user, null, $club);
        $was = MemberStats::ranking($user, null, $from->copy()->subSecond(), $club)->firstWhere('me', true)?->rank;
        $move = match (true) {
            ! $rank => null,
            ! $was => "You're on the {$club} table at #{$rank}.",
            $rank < $was => "You moved up to #{$rank} at {$club}, from #{$was}.",
            $rank > $was => "You slipped to #{$rank} at {$club}, from #{$was}.",
            default => "You held #{$rank} at {$club}.",
        };

        return new PickleXMail('weekly-recap', "Your week on PickleX: {$games->count()} ".Str::plural('game', $games->count()).", {$wins}-{$losses}",
            "{$rate}% win rate across {$games->unique('open_play_id')->count()} open ".Str::plural('play', $games->unique('open_play_id')->count()).'.'.($move ? " {$move}" : ''), [
                'user' => $user, 'from' => $from, 'to' => $to, 'games' => $games->count(), 'wins' => $wins, 'losses' => $losses, 'rate' => $rate,
                'plays' => $games->unique('open_play_id')->count(), 'rank' => $rank, 'move' => $move,
            ]);
    }

    /** Monday summary for an organizer whose sessions ran last week (notify.clubRecap). Null when none ran. */
    public static function clubSummary(User $organizer): ?PickleXMail
    {
        [$from, $to] = self::lastWeek();
        $plays = $organizer->openPlays()->status('done')->whereBetween('started_at', [$from, $to])->withCount('games')->orderBy('starts_at')->get();
        if ($plays->isEmpty()) {
            return null;
        }

        $players = DB::table('game_players')->whereIn('game_id', DB::table('games')->whereIn('open_play_id', $plays->pluck('id'))->select('id'))
            ->get(['user_id', 'participant_id'])->map(fn ($r) => $r->user_id ? "u{$r->user_id}" : "p{$r->participant_id}")->unique()->count();
        $counts = DB::table('game_players')->join('games', 'games.id', '=', 'game_players.game_id')->whereIn('games.open_play_id', $plays->pluck('id'))
            ->selectRaw('games.open_play_id, count(distinct game_players.participant_id) as n')->groupBy('games.open_play_id')->pluck('n', 'open_play_id');
        $busiest = $plays->sortByDesc(fn ($p) => [$counts[$p->id] ?? 0, $p->games_count])->take(3)->values();
        $top = $busiest->first();
        $games = $plays->sum('games_count');

        return new PickleXMail('club-summary', "{$organizer->club} last week: {$plays->count()} ".Str::plural('session', $plays->count()).", {$games} ".Str::plural('game', $games),
            "{$players} ".Str::plural('player', $players).' came out. Busiest night: '.$top->starts_at->format('D, M j').' with '.($counts[$top->id] ?? 0).' '.Str::plural('player', $counts[$top->id] ?? 0).'.', [
                'organizer' => $organizer, 'from' => $from, 'to' => $to, 'plays' => $plays, 'games' => $games, 'players' => $players,
                'busiest' => $busiest, 'counts' => $counts,
            ]);
    }

    /** Monday summary for admins (notify.adminRecap): new members, organizers and sessions across PickleX. */
    public static function platformSummary(User $admin): PickleXMail
    {
        [$from, $to] = self::lastWeek();
        $members = User::where('role', 'member')->whereBetween('created_at', [$from, $to])->count();
        $pending = User::where('organizer_status', 'pending')->count();
        $sessions = OpenPlay::whereBetween('started_at', [$from, $to]);
        $hosts = OpenPlay::whereNotNull('started_at')->groupBy('organizer_id')->havingRaw('min(started_at) between ? and ?', [$from, $to])->pluck('organizer_id')->count();
        $games = DB::table('games')->whereBetween('played_at', [$from, $to])->count();
        $count = (clone $sessions)->count();

        return new PickleXMail('platform-summary', "PickleX last week: {$members} new ".Str::plural('member', $members).", {$count} ".Str::plural('session', $count),
            "{$games} ".Str::plural('game', $games).' logged.'.($pending ? " {$pending} organizer ".Str::plural('request', $pending).' waiting for review.' : ''), [
                'admin' => $admin, 'from' => $from, 'to' => $to, 'members' => $members, 'pending' => $pending, 'sessions' => $count,
                'hosts' => $hosts, 'games' => $games, 'clubs' => (clone $sessions)->distinct()->count('club'),
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
