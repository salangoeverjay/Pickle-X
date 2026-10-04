<?php

use App\Models\OpenPlay;
use App\Models\User;
use App\Support\Emails;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// A live session nobody ended closes on its own 12 hours after its end time (games and standings saved).
Artisan::command('open-plays:auto-end', function () {
    $due = OpenPlay::status('live')->where('ends_at', '<=', now()->subHours(OpenPlay::AUTO_END_HOURS))->get();
    $due->each->endAutomatically();
    $this->info("Ended {$due->count()} open ".Str::plural('play', $due->count()).' automatically.');
})->purpose('End live open plays still running 12 hours after their end time');

Schedule::command('open-plays:auto-end')->everyMinute();

// A 7 or 30 day suspension lifts on its own on its end date; the person gets an "active again" email.
Artisan::command('users:lift-suspensions', function () {
    $due = User::whereNotNull('suspended_at')->where('suspended_until', '<=', now())->get();
    $due->each(function (User $user) {
        $mail = Emails::accountRestored($user, early: false);
        $user->liftSuspension();
        Emails::send($user, $mail);
    });
    $this->info("Lifted {$due->count()} ".Str::plural('suspension', $due->count()).'.');
})->purpose('Lift timed suspensions whose end date has passed');

Schedule::command('users:lift-suspensions')->everyMinute();

// One hour before an upcoming open play starts, its organizer gets a reminder. reminder_sent_at keeps it to one.
Artisan::command('open-plays:remind', function () {
    $due = OpenPlay::status('upcoming')->whereNull('reminder_sent_at')->whereBetween('starts_at', [now(), now()->addHour()])->with('organizer')->get();
    $due->each(function (OpenPlay $play) {
        $play->forceFill(['reminder_sent_at' => now()])->save();
        Emails::notify([$play->organizer], 'reminder', fn () => Emails::sessionReminder($play));
    });
    $this->info("Reminded {$due->count()} open ".Str::plural('play', $due->count()).'.');
})->purpose('Remind organizers an hour before each open play starts');

Schedule::command('open-plays:remind')->everyMinute();

// Mondays: last week's recap for members who played, summary for organizers whose sessions ran, platform summary for admins.
Artisan::command('emails:weekly', function () {
    [$from, $to] = Emails::lastWeek();
    $players = User::whereIn('id', DB::table('game_players')->join('games', 'games.id', '=', 'game_players.game_id')
        ->whereBetween('games.played_at', [$from, $to])->whereNotNull('game_players.user_id')->select('game_players.user_id'))->get();
    Emails::notify($players, 'recap', fn (User $u) => Emails::weeklyRecap($u));
    $hosts = User::whereIn('id', OpenPlay::status('done')->whereBetween('started_at', [$from, $to])->select('organizer_id'))->get();
    Emails::notify($hosts, 'clubRecap', fn (User $u) => Emails::clubSummary($u));
    Emails::notify(User::where('role', 'admin')->get(), 'adminRecap', fn (User $u) => Emails::platformSummary($u));
    $this->info("Weekly emails for {$players->count()} ".Str::plural('player', $players->count())." and {$hosts->count()} ".Str::plural('organizer', $hosts->count()).'.');
})->purpose('Send the Monday recap, club summary and platform summary emails');

Schedule::command('emails:weekly')->weeklyOn(1, '8:00');
