<?php

use App\Livewire\Admin;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Signup;
use App\Livewire\Home;
use App\Livewire\Member;
use App\Livewire\Organizer;
use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Home::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/signup', Signup::class)->name('signup');

    // One-tap demo accounts (seeded). Only exists when APP_DEMO=true.
    Route::post('/demo-login', function (Request $request) {
        abort_unless(config('app.demo'), 404);

        $email = [
            'member' => 'kai@picklex.app',
            'organizer' => 'ely@panabodink.ph',
            'admin' => 'admin@picklex.app',
        ][$request->input('role')] ?? abort(404);

        Auth::login(User::where('email', $email)->firstOrFail());
        $request->session()->regenerate();

        return redirect()->route(Auth::user()->homeRoute());
    })->name('demo-login');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/settings', Settings::class)->name('settings');

    Route::middleware('role:member')->group(function () {
        Route::livewire('/dashboard', Member\Dashboard::class)->name('member.dashboard');
        Route::livewire('/plays', Member\OpenPlays::class)->name('member.plays');
        Route::livewire('/played-with', Member\PlayedWith::class)->name('member.played-with');
        Route::livewire('/rankings', Member\Rankings::class)->name('member.rankings');
    });

    Route::middleware('role:organizer')->prefix('organizer')->name('organizer.')->group(function () {
        Route::livewire('/plays', Organizer\OpenPlays::class)->name('plays');
        Route::livewire('/board', Organizer\CourtBoard::class)->name('board');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/requests', Admin\Requests::class)->name('requests');
    });
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');
