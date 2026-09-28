<?php

namespace App\Livewire\Organizer;

use App\Models\OpenPlay;
use App\Support\PaidList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Your open plays · PickleX')]
class OpenPlays extends Component
{
    /** Paid-list preview tag per PaidList match type: [label, classes]. */
    public const PAID_TAGS = [
        'email' => ['Member · email match', 'bg-lime-soft text-lime-text'],
        'name' => ['Member · linked', 'bg-lime-soft text-lime-text'],
        'first' => ['Matched by first name', 'bg-hot-soft text-hot'],
        'guest' => ['Guest · no account', 'bg-surface2 text-ink2'],
    ];

    // Create dialog; ?create=1 opens it (sidebar "Create open play" link).
    #[Url(except: false)]
    public bool $create = false;

    public string $title = '';

    public string $venue = '';

    public string $date = '';

    public string $starts = '18:00';

    public string $ends = '21:00';

    public int $courts = 4;

    public int $cap = 20;

    public int $raceTo = 11;

    public string $rotation = 'rotate';

    public string $visibility = 'public';

    public string $fee = '';

    // Paid players, one name or email per line (matched to accounts on publish).
    public string $paid = '';

    // Payment QR details. The image itself stays in the browser until publish(); see the view.
    public string $payMethod = 'gcash';

    public string $payName = '';

    public string $payNumber = '';

    public ?string $error = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->organizer_status === 'approved', 403);

        $this->resetForm();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->create = true;
    }

    /**
     * $qr: null (no payment QR), "saved" (reuse the QR from your last open play),
     * or the data URL of a newly uploaded QR image.
     */
    public function publish(?string $qr = null): void
    {
        $this->title = trim($this->title);
        $this->venue = trim($this->venue);
        $this->fee = trim($this->fee);
        $missing = 'Add a title and a venue so members know where to go.';

        $v = validator($this->all(), [
            'title' => 'required|max:120',
            'venue' => 'required|max:160',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today|before_or_equal:'.today()->addYear()->toDateString(),
            'starts' => 'required|date_format:H:i',
            'ends' => 'required|date_format:H:i',
            'courts' => 'integer|between:1,12',
            'cap' => 'integer|between:4,64',
            'raceTo' => 'in:11,15,21',
            'rotation' => 'in:rotate,winners',
            'visibility' => 'in:public,invite',
            'fee' => 'max:40',
            'payMethod' => 'in:'.implode(',', array_keys(OpenPlay::PAY_METHODS)),
            'payName' => 'max:120',
            'payNumber' => 'max:40',
        ], [
            'title.required' => $missing,
            'venue.required' => $missing,
            'date.after_or_equal' => 'Pick a date from today onward.',
            'date.before_or_equal' => 'Open plays can be scheduled up to a year ahead.',
        ]);

        if ($v->fails()) {
            $this->error = $v->errors()->first();

            return;
        }

        // Ends at or before the start = the next day (overnight session).
        $starts = Carbon::parse("{$this->date} {$this->starts}");
        $ends = Carbon::parse("{$this->date} {$this->ends}");
        if ($ends->lessThanOrEqualTo($starts)) {
            $ends->addDay();
        }
        $minutes = (int) $starts->diffInMinutes($ends);
        if ($minutes === 0 || $minutes === 1440) {
            $this->error = 'The session has to end after it starts. Pick a later end time.';

            return;
        }
        if ($minutes > 720) {
            $this->error = 'Open plays can run up to 12 hours. Check the end time.';

            return;
        }

        $fee = preg_match('/^\d+(\.\d{1,2})?$/', $this->fee) ? '₱'.$this->fee : $this->fee;
        if ($qr && ! $fee) {
            $this->error = 'Add the fee per player so people know how much to send to your QR.';

            return;
        }
        if ($qr && ! trim($this->payName)) {
            $this->error = 'Add the account name on your QR so players can double-check before they pay.';

            return;
        }
        $png = match (true) {
            ! $qr => null,
            $qr === 'saved' => $this->lastPayPlay() ? DB::table('payment_qrs')->where('open_play_id', $this->lastPayPlay()->id)->value('data') : null,
            default => self::cleanQr($qr),
        };
        if ($qr && ! $png) {
            $this->error = "We couldn't use that QR image. Upload a PNG, JPG or WebP screenshot of your QR.";

            return;
        }

        $rows = PaidList::match($this->paid);
        if (count($rows) > $this->cap) {
            $this->error = 'You listed '.count($rows)." paid players but the cap is {$this->cap}. Raise the cap or trim the list.";

            return;
        }

        $user = auth()->user();
        $play = $user->openPlays()->create([
            'title' => $this->title,
            'club' => $user->club ?? 'Your club',
            'venue' => $this->venue,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'courts' => $this->courts,
            'cap' => $this->cap,
            'race_to' => $this->raceTo,
            'rotation' => $this->rotation,
            'visibility' => $this->visibility,
            'fee' => $fee ?: null,
            'status' => 'upcoming',
        ] + ($png ? [
            'pay_method' => $this->payMethod,
            'pay_name' => trim($this->payName),
            'pay_number' => trim($this->payNumber) ?: null,
            'pay_qr_version' => substr(sha1($png), 0, 12),
        ] : []));
        if ($png) {
            DB::table('payment_qrs')->insert(['open_play_id' => $play->id, 'data' => $png, 'created_at' => now(), 'updated_at' => now()]);
        }

        [$added, $linked] = $play->addPaidPlayers($rows);

        $this->resetForm();
        $this->create = false;
        $this->dispatch('create-published', qrUrl: $play->payQrUrl());
        $qrNote = $png ? " · {$play->payMethodLabel()} QR attached" : '';
        $this->dispatch('toast', message: match (true) {
            $added > 0 => "Open play published · {$added} paid players added, {$linked} linked to accounts{$qrNote}",
            (bool) $png => "Open play published with a {$play->payMethodLabel()} QR. Members can join now.",
            default => 'Open play published. Members can join now.',
        });
    }

    /** Validate an uploaded QR data URL and re-encode it as PNG (base64). Null if unusable. */
    private static function cleanQr(string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/(png|jpeg|webp);base64,([A-Za-z0-9+/]+={0,2})$#', $dataUrl, $m)) {
            return null;
        }
        $bytes = base64_decode($m[2], true);
        $info = $bytes && strlen($bytes) <= 3 * 1024 * 1024 ? @getimagesizefromstring($bytes) : false;
        if (! $info || min($info[0], $info[1]) < 120 || max($info[0], $info[1]) > 2000) {
            return null;
        }
        if (! function_exists('imagecreatefromstring') || ! ($img = @imagecreatefromstring($bytes))) {
            return $info[2] === IMAGETYPE_PNG ? base64_encode($bytes) : null;
        }
        ob_start();
        imagepng($img, null, 6);
        imagedestroy($img);

        return base64_encode(ob_get_clean());
    }

    /** Your most recent open play with a payment QR (its QR pre-fills the create form). */
    private function lastPayPlay(): ?OpenPlay
    {
        return auth()->user()->openPlays()->whereNotNull('pay_qr_version')->latest('id')->first();
    }

    public function startSession(int $id)
    {
        $play = OpenPlay::findOrFail($id);
        abort_unless($play->organizer_id === auth()->id(), 403);

        if (auth()->user()->openPlays()->status('live')->exists()) {
            $this->dispatch('toast', message: 'End the current live session first');

            return;
        }

        try {
            $play->start();
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return;
        }

        session()->flash('toast', "{$play->title} is live. Courts filled from the queue.");

        return $this->redirectRoute('organizer.board', navigate: true);
    }

    private function resetForm(): void
    {
        $user = auth()->user();
        $this->courts = (int) $user->pref('defaults.courts');
        $this->raceTo = (int) $user->pref('defaults.race_to');
        $this->rotation = $user->pref('defaults.rotation');
        $this->cap = max(8, $this->courts * 4 + 4);
        $this->date = now()->addDay()->format('Y-m-d');
        $this->starts = '18:00';
        $this->ends = '21:00';
        $this->title = $this->venue = $this->fee = $this->paid = '';
        $this->visibility = 'public';
        $this->error = null;
        $last = $this->lastPayPlay();
        $this->payMethod = $last?->pay_method ?? 'gcash';
        $this->payName = $last?->pay_name ?? '';
        $this->payNumber = $last?->pay_number ?? '';
    }

    public function render()
    {
        $query = fn () => auth()->user()->openPlays()->withCount(['activeParticipants', 'games']);
        $plays = $query()->whereIn('status', ['live', 'upcoming'])->withCount([
            'activeParticipants as paid_count' => fn ($q) => $q->where('paid', true),
            'activeParticipants as paid_linked_count' => fn ($q) => $q->where('paid', true)->whereNotNull('user_id'),
        ])->orderBy('starts_at')->get();

        return view('livewire.organizer.open-plays', [
            'club' => auth()->user()->club ?? 'Your club',
            'live' => $plays->where('status', 'live'),
            'upcoming' => $plays->where('status', 'upcoming'),
            // Recent history only; older sessions still count toward stats.
            'past' => $query()->status('done')->latest('starts_at')->limit(10)->get(),
            'paidPreview' => $this->create ? PaidList::match($this->paid) : [],
            'lastQrUrl' => $this->lastPayPlay()?->payQrUrl(),
        ]);
    }
}
