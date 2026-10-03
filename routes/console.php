<?php

use App\Models\OpenPlay;
use App\Models\User;
use App\Support\Emails;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
