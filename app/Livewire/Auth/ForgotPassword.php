<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\Emails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Forgot password: email -> 6-digit code (valid 10 minutes, 5 tries) -> new password.
 * Never says whether an account exists. The code is stored hashed in password_reset_tokens.
 */
#[Title('Reset your password · PickleX')]
class ForgotPassword extends Component
{
    public const TTL = 10;      // minutes a code stays valid

    public const RESEND = 30;   // seconds between codes

    public const TRIES = 5;

    public string $email = '';

    public string $code = '';

    public string $password = '';

    public string $confirm = '';

    #[Locked]
    public string $step = 'email'; // email | code | new | done

    #[Locked]
    public int $sentAt = 0;

    public string $error = '';

    /** The email's "Reset password" button links here with the code filled in. */
    public function mount(): void
    {
        $this->email = trim((string) request('email', ''));
        $code = preg_replace('/\D/', '', (string) request('code', ''));
        if ($this->email && strlen($code) === 6) {
            $this->code = $code;
            $this->step = 'code';
        }
    }

    public function send(): void
    {
        $this->email = trim($this->email);
        $this->error = '';

        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->error = $this->email ? "That email doesn't look right. Check it and try again." : 'Enter the email you use for PickleX.';

            return;
        }
        if ($this->sentAt && time() - $this->sentAt < self::RESEND) {
            return;
        }

        $key = 'pw-send:'.sha1(Str::lower($this->email)).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->error = 'Too many codes requested. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes.';

            return;
        }
        RateLimiter::hit($key, 3600);

        if ($user = User::where('email', $this->email)->first()) {
            self::sendCode($user, self::device((string) request()->userAgent()));
            RateLimiter::clear($this->triesKey());
        }

        $this->step = 'code';
        $this->sentAt = time();
        $this->code = '';
    }

    /** Emails a fresh 6-digit code; its button links back here with the code filled in. Also used by Admin › Users. */
    public static function sendCode(User $user, string $device): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('password_reset_tokens')->upsert(
            [['email' => $user->email, 'token' => Hash::make($code), 'created_at' => now()]], ['email'], ['token', 'created_at'],
        );
        Emails::send($user, Emails::reset($user, $code, now()->addMinutes(self::TTL), $device));
    }

    public function resend(): void
    {
        $this->send();
        if (! $this->error) {
            $this->dispatch('toast', message: 'New code sent to '.self::mask($this->email));
        }
    }

    public function verify(): void
    {
        $this->error = '';
        $this->code = preg_replace('/\D/', '', $this->code);
        $key = $this->triesKey();

        if (RateLimiter::tooManyAttempts($key, self::TRIES)) {
            $this->error = 'Too many tries. Send yourself a new code to keep going.';

            return;
        }
        if (strlen($this->code) !== 6) {
            $this->error = 'Enter all 6 digits from the email.';

            return;
        }

        $row = DB::table('password_reset_tokens')->where('email', $this->email)->first();
        $fresh = $row && now()->subMinutes(self::TTL)->lt($row->created_at);

        if (! $fresh || ! Hash::check($this->code, $row->token)) {
            RateLimiter::hit($key, 3600);
            $this->error = match (true) {
                RateLimiter::tooManyAttempts($key, self::TRIES) => 'Too many tries. Send yourself a new code to keep going.',
                $row && ! $fresh => 'That code has expired. Send yourself a new one.',
                default => "That code doesn't match. Check the newest email from PickleX and try again.",
            };

            return;
        }

        RateLimiter::clear($key);
        session(['password_reset' => ['email' => $row->email, 'at' => time()]]);
        $this->step = 'new';
        $this->code = '';
    }

    public function save(): void
    {
        $this->error = '';
        $verified = session('password_reset');
        $user = $verified && time() - $verified['at'] < self::TTL * 60 ? User::where('email', $verified['email'])->first() : null;

        if (! $user) {
            session()->forget('password_reset');
            $this->step = 'email';
            $this->error = 'That reset took too long. Send yourself a new code to start again.';

            return;
        }
        if (! collect(self::rules($this->password, $user->email, $user->name))->every(fn ($r) => $r[1])) {
            $this->error = "Your new password doesn't meet the checklist yet.";

            return;
        }
        if ($this->confirm !== $this->password) {
            $this->error = "The two passwords don't match. Type the new one again.";

            return;
        }

        $user->forceFill(['password' => $this->password, 'remember_token' => Str::random(60)])->save();
        // Sign the account out everywhere else.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        session()->forget('password_reset');

        $this->reset('password', 'confirm');
        $this->step = 'done';
    }

    public function changeEmail(): void
    {
        $this->step = 'email';
        $this->sentAt = 0;
        $this->reset('code', 'error');
    }

    public function finish()
    {
        session()->flash('login_email', $this->email);
        session()->flash('toast', 'Log in with your new password');

        return $this->redirectRoute('login', navigate: true);
    }

    /** [[label, ok], ...]: the checklist the new password must pass (mirrored in resources/js/new-password.js). */
    public static function rules(string $pass, string $email, string $name = ''): array
    {
        $low = Str::lower($pass);
        $local = Str::lower(Str::before($email, '@'));
        $first = Str::lower(Str::before(trim($name), ' '));
        $personal = (strlen($local) >= 3 && str_contains($low, $local)) || (strlen($first) >= 3 && str_contains($low, $first));

        return [
            ['At least 8 characters', strlen($pass) >= 8],
            ['A letter and a number', preg_match('/[a-z]/i', $pass) && preg_match('/\d/', $pass)],
            ["Doesn't include your email or name", $pass !== '' && ! $personal],
        ];
    }

    /** "k•••i@picklex.app" */
    public static function mask(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + ['', ''];

        return $local && $domain ? mb_substr($local, 0, 1).'•••'.(mb_strlen($local) > 3 ? mb_substr($local, -1) : '').'@'.$domain : $email;
    }

    /** "Chrome on macOS" from a user agent, for the reset email. */
    public static function device(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'A browser',
        };
        $os = match (true) {
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        return $os ? "{$browser} on {$os}" : $browser;
    }

    protected function triesKey(): string
    {
        return 'pw-tries:'.sha1(Str::lower($this->email));
    }

    public function render()
    {
        return view('livewire.auth.forgot-password', [
            'wait' => $this->sentAt ? max(0, self::RESEND - (time() - $this->sentAt)) : 0,
        ]);
    }
}
