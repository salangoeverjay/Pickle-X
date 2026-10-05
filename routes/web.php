<?php

use App\Livewire\About;
use App\Livewire\Admin;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Signup;
use App\Livewire\Home;
use App\Livewire\JoinLink;
use App\Livewire\LiveView;
use App\Livewire\Member;
use App\Livewire\Organizer;
use App\Livewire\Settings;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;

Route::livewire('/', Home::class)->name('home');
Route::livewire('/about', About::class)->name('about');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/signup', Signup::class)->name('signup');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');

    // Log in with a passkey (resources/js/passkeys.js): the browser asks for options, signs them on the device, posts the result.
    Route::get('/passkeys/options', fn (GeneratePasskeyAuthenticationOptionsAction $options) => response($options->execute(), 200, ['Content-Type' => 'application/json']))
        ->middleware('throttle:20,1')->name('passkeys.options');
    Route::post('/passkeys/login', function (Request $request, FindPasskeyToAuthenticateAction $find) {
        $request->validate(['response' => 'required|json']);
        $options = $request->session()->pull('passkey-authentication-options');
        $user = $options ? rescue(fn () => $find->execute($request->input('response'), $options)?->authenticatable, null, false) : null;
        if (! $user) {
            return response()->json(['message' => "That passkey didn't work. Try again, or log in with your password."], 422);
        }
        if ($user->isSuspended()) {
            return response()->json(['message' => $user->suspensionMessage()], 422);
        }
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('mode');
        $request->session()->flash('loader', ['kind' => 'login']);
        $request->session()->flash('toast', 'Logged in with your passkey');

        return response()->json(['redirect' => $request->session()->pull('url.intended', route($user->homeRoute()))]);
    })->middleware('throttle:10,1')->name('passkeys.login');
});

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
    $request->session()->forget('mode');
    $request->session()->flash('loader', ['kind' => 'login']);
    if (Auth::user()->isAdmin()) {
        $request->session()->flash('toast', 'Logged in as admin · '.User::where('organizer_status', 'pending')->count().' pending');
    }

    return redirect()->route(Auth::user()->homeRoute());
})->name('demo-login');

// Uploaded profile photos. The version in the URL changes on every upload, so it can cache forever.
Route::get('/avatars/{user}/{version}.jpg', function (int $user, string $version) {
    $data = DB::table('avatar_photos')->where('user_id', $user)->value('data');
    abort_unless($data && User::whereKey($user)->where('avatar', "photo:{$version}")->exists(), 404);

    return response(base64_decode($data), 200, [
        'Content-Type' => 'image/jpeg',
        'Cache-Control' => 'public, max-age=31536000, immutable',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->where('version', '[a-f0-9]{12}')->name('avatar.photo');

// An open play's payment QR (players pay the fee with it, guests from a share link too). Versioned and unguessable, so it caches forever.
Route::get('/plays/{play}/pay-qr/{version}.png', function (Request $request, OpenPlay $play, string $version) {
    abort_unless($play->pay_qr_version === $version, 404);
    $data = DB::table('payment_qrs')->where('open_play_id', $play->id)->value('data');
    abort_unless($data, 404);

    return response(base64_decode($data), 200, array_filter([
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=31536000, immutable',
        'X-Content-Type-Options' => 'nosniff',
        'Content-Disposition' => $request->boolean('download') ? 'attachment; filename="picklex-'.Str::slug($play->title).'-qr.png"' : null,
    ]));
})->where('version', '[a-f0-9]{12}')->name('play.pay-qr');

// A share link (/j/{code}): see the open play and join as a guest, with an account, or straight away when logged in.
Route::livewire('/j/{code}', JoinLink::class)->where('code', '[a-z0-9]{4,12}')->name('join');

// Public, read-only live view (QR target). No login needed.
Route::livewire('/live/{play:share_code}', LiveView::class)->name('live');

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

        // Final standings of a concluded open play (linked from the organizer recap email).
        Route::get('/plays/{play}/standings.csv', function (OpenPlay $play) {
            abort_unless($play->organizer_id === auth()->id() && $play->status === 'done', 404);

            return response()->streamDownload(function () use ($play) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Rank', 'Player', 'Wins', 'Losses', 'Games', 'Points for', 'Points against', 'Point difference', 'Win %']);
                foreach ($play->standings() as $r) {
                    fputcsv($out, [$r['rank'], $play->participant($r['participant_id'])?->name, $r['w'], $r['l'], $r['g'], $r['pf'], $r['pa'], $r['diff'], round($r['w'] * 100 / $r['g'])]);
                }
                fclose($out);
            }, 'picklex-'.Str::slug($play->title).'-'.$play->starts_at->format('Y-m-d').'-standings.csv', ['Content-Type' => 'text/csv']);
        })->name('standings-csv');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/requests', Admin\Requests::class)->name('requests');
        Route::livewire('/users', Admin\Users::class)->name('users');
        Route::livewire('/developers', Admin\Developers::class)->name('developers');
        Route::livewire('/sponsors', Admin\Sponsors::class)->name('sponsors');
    });
});

Route::post('/logout', function () {
    $first = Auth::user()->firstName();
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    request()->session()->flash('loader', ['kind' => 'logout', 'first' => $first]);

    return redirect()->route('home');
})->middleware('auth')->name('logout');
