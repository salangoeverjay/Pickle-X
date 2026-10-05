<?php

namespace App\Livewire\Organizer;

use App\Livewire\Concerns\Pages;
use App\Livewire\Concerns\ShowsRoster;
use App\Livewire\Concerns\StartsSessions;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\OpenPlay;
use App\Models\Participant;
use App\Models\User;
use App\Support\Emails;
use App\Support\PaidList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Your open plays · PickleX')]
class OpenPlays extends Component
{
    use Pages, ShowsRoster, StartsSessions;

    // Create dialog; ?create=1 opens it (sidebar "Create open play" link).
    #[Url(except: false)]
    public bool $create = false;

    /** Open play whose Share link dialog is showing (?share=id opens it, e.g. from the link page). */
    #[Url(except: null)]
    public ?int $share = null;

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

    // Paid players, one name or email per line. Each stays a guest until you link it to an account.
    public string $paid = '';

    // Paid-list links: line key (PaidList::key) => user id, or "guest" to keep a suggested match as a guest.
    public array $links = [];

    // Payment QR details. The image itself stays in the browser until publish(); see the view.
    public string $payMethod = 'gcash';

    public string $payName = '';

    public string $payNumber = '';

    public ?string $error = null;

    // The open play being edited (null = creating), and whether the sheet shows the review step.
    #[Locked]
    public ?int $editId = null;

    public bool $review = false;

    // What the review step shows (built by showReview()).
    #[Locked]
    public array $reviewData = [];

    // Upcoming open play waiting on the delete confirm.
    #[Locked]
    public ?int $deleteId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->organizer_status === 'approved', 403);

        $this->resetForm();
    }

    public function linkPaid(string $key, int $userId): void
    {
        abort_unless(User::where('role', 'member')->whereKey($userId)->exists(), 404);
        $this->links[$key] = $userId;
        $this->error = null;
    }

    public function keepGuest(string $key): void
    {
        $this->links[$key] = 'guest';
    }

    public function unlinkPaid(string $key): void
    {
        unset($this->links[$key]);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->create = true;
        $this->dispatch('pay-qr-load', url: $this->lastPayPlay()?->payQrUrl(), label: 'Saved from your last open play');
    }

    /** Edit an upcoming open play: the same sheet, pre-filled, ending on a review of what changed. */
    public function edit(int $id): void
    {
        $play = $this->editable($id);
        $this->resetForm();
        $this->editId = $play->id;
        $this->title = $play->title;
        $this->venue = $play->venue;
        $this->date = $play->starts_at->format('Y-m-d');
        $this->starts = $play->starts_at->format('H:i');
        $this->ends = $play->ends_at->format('H:i');
        $this->courts = $play->courts;
        $this->cap = $play->cap;
        $this->raceTo = $play->race_to;
        $this->rotation = $play->rotation;
        $this->visibility = $play->visibility;
        $this->fee = (string) $play->fee;
        [$this->paid, $this->links] = self::paidLines($play);
        $this->payMethod = $play->pay_method ?? $this->payMethod;
        $this->payName = $play->pay_name ?? $this->payName;
        $this->payNumber = $play->pay_number ?? $this->payNumber;
        $this->create = true;
        $this->dispatch('pay-qr-load', url: $play->payQrUrl(), label: "This open play's QR");
    }

    /** Submitting the sheet checks everything, then shows the review. Nothing is saved yet. */
    public function showReview(?string $qr = null): void
    {
        $form = $this->checkForm($qr);
        if (! $form) {
            $this->review = false;

            return;
        }

        $rows = $this->reviewRows($form, $qr);
        $changes = count(array_filter($rows, fn ($r) => $r['changed']));
        $linked = collect($form['rows'])->whereNotNull('user')->count();
        $notes = [];
        if ($this->editId) {
            $told = $this->editable($this->editId)->activeParticipants()->whereNotNull('user_id')->where('user_id', '!=', auth()->id())->count();
            if ($told && collect($rows)->contains('notify', true)) {
                $notes[] = $told.' '.($told === 1 ? 'player who joined gets' : 'players who joined get').' an email about what changed.';
            }
            [$out, $unpaid] = $form['dropped']->partition(fn ($p) => self::leavesWhenDropped($p));
            if ($out->isNotEmpty()) {
                $notes[] = 'Taken off the paid list, so they leave this open play: '.$out->map(fn ($p) => $p->fullName())->join(', ', ' and ').'.';
            }
            if ($unpaid->isNotEmpty()) {
                $notes[] = $unpaid->map(fn ($p) => $p->fullName())->join(', ', ' and ').' joined in the app, so they stay in and are marked as not paid.';
            }
            if (collect($rows)->contains(fn ($r) => $r['key'] === 'fee' && $r['changed']) && $form['rows']) {
                $notes[] = 'Players already marked as paid keep their Paid status. Settle any difference in the fee with them directly.';
            }
        } else {
            $notes[] = $this->visibility === 'invite' ? 'Only people you invite can see and join it after you publish.' : 'Members can see it and join as soon as you publish.';
            if ($linked) {
                $notes[] = $linked.' '.($linked === 1 ? 'paid player has' : 'paid players have').' a PickleX account and will get an email saying they are in.';
            }
        }
        if (! $form['png'] && $form['fee']) {
            $notes[] = "There's no payment QR, so players will pay you at the venue.";
        }
        if ($toReview = collect($form['rows'])->where('review', true)->count()) {
            $notes[] = $toReview.' '.($toReview === 1 ? "name has a possible account you haven't linked. It joins" : "names have possible accounts you haven't linked. They join").' as a guest unless you go back and link it.';
        }

        $this->reviewData = [
            'rows' => array_values(array_filter($rows, fn ($r) => $r['key'] !== 'title' || $r['changed'])),
            'changes' => $changes,
            'notes' => $notes,
            'hasQr' => (bool) $form['png'],
            'qrAlt' => (OpenPlay::PAY_METHODS[$this->payMethod][0] ?? 'Payment').' payment QR for '.trim($this->payName),
            'mon' => $form['starts']->format('M'),
            'day' => $form['starts']->format('j'),
            'dow' => $form['starts']->format('D'),
            'line' => OpenPlay::rangeLabel($form['starts'], $form['ends']).' · '.$this->venue,
            'paid' => collect($form['rows'])->map(fn ($r) => [
                'name' => $r['name'], 'guest' => ! $r['user'], 'color' => $r['user']?->color(), 'avatar' => $r['user']?->avatarUrl(),
            ])->all(),
        ];
        $this->review = true;
    }

    public function backToForm(): void
    {
        $this->review = false;
    }

    /**
     * Save the sheet (from the review): creates the open play, or updates the one being edited.
     * $qr: null (no payment QR), "saved" (keep the QR shown in the sheet: your last open play's,
     * or this open play's when editing), or the data URL of a newly uploaded QR image.
     */
    public function publish(?string $qr = null): void
    {
        $form = $this->checkForm($qr);
        if (! $form) {
            $this->review = false;

            return;
        }

        $this->editId ? $this->saveEdit($form, $qr) : $this->saveNew($form);
    }

    /**
     * Validate the sheet. Returns the normalised values, or null with $this->error set.
     *
     * @return array{starts: Carbon, ends: Carbon, minutes: int, fee: string, png: ?string, rows: array, newRows: array}|null
     */
    private function checkForm(?string $qr): ?array
    {
        $this->title = trim($this->title);
        $this->venue = trim($this->venue);
        $this->fee = trim($this->fee);
        $this->error = null;
        $missing = 'Add a title and a venue so members know where to go.';
        $play = $this->editId ? $this->editable($this->editId) : null;

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

            return null;
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

            return null;
        }
        if ($minutes > 720) {
            $this->error = 'Open plays can run up to 12 hours. Check the end time.';

            return null;
        }

        $fee = preg_match('/^\d+(\.\d{1,2})?$/', $this->fee) ? '₱'.$this->fee : $this->fee;
        if ($qr && ! $fee) {
            $this->error = 'Add the fee per player so people know how much to send to your QR.';

            return null;
        }
        if ($qr && ! trim($this->payName)) {
            $this->error = 'Add the account name on your QR so players can double-check before they pay.';

            return null;
        }
        $savedFrom = $play && $play->hasPaymentQr() ? $play : $this->lastPayPlay();
        $png = match (true) {
            ! $qr => null,
            $qr === 'saved' => $savedFrom ? DB::table('payment_qrs')->where('open_play_id', $savedFrom->id)->value('data') : null,
            default => self::cleanQr($qr),
        };
        if ($qr && ! $png) {
            $this->error = "We couldn't use that QR image. Upload a PNG, JPG or WebP screenshot of your QR.";

            return null;
        }

        $rows = $this->paidRows();
        if (count($rows) > $this->cap) {
            $this->error = 'You listed '.count($rows)." paid players but the cap is {$this->cap}. Raise the cap or trim the list.";

            return null;
        }

        // Editing: paid lines already in the open play aren't added twice, lines taken out drop those players,
        // and the cap can't drop below who's in.
        $newRows = $play ? self::newPaidRows($play, $rows) : $rows;
        $dropped = $play ? self::droppedPaid($play, $rows) : collect();
        $leaving = $dropped->filter(fn ($p) => self::leavesWhenDropped($p))->count();
        if ($play && ($in = $play->activeParticipants()->count() - $leaving + count($newRows)) > $this->cap) {
            $this->error = "{$in} players are already in this open play. Set the player cap to at least {$in}.";

            return null;
        }

        return compact('starts', 'ends', 'minutes', 'fee', 'png', 'rows', 'newRows', 'dropped');
    }

    private function saveNew(array $form): void
    {
        ['starts' => $starts, 'ends' => $ends, 'fee' => $fee, 'png' => $png, 'rows' => $rows] = $form;

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
        ] + $this->payFields($png));
        $this->storeQr($play, $png);

        [$added, $linked] = $play->addPaidPlayers($rows);
        $this->announce($play);
        // Paid players with an account are in, so they get the same "You're in" email as members who join.
        collect($rows)->pluck('user')->filter()->unique('id')->reject(fn ($u) => $u->id === $user->id)
            ->each(fn ($u) => Emails::send($u, Emails::joined($play, $u)));

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

    private function saveEdit(array $form, ?string $qr): void
    {
        $play = $this->editable($this->editId);
        $changes = collect($this->reviewRows($form, $qr))->where('changed', true);
        if ($changes->isEmpty()) {
            $this->review = false;
            $this->create = false;

            return;
        }

        $keepQr = $qr === 'saved' && $play->hasPaymentQr();
        $qrVersion = $play->pay_qr_version;
        $play->update([
            'title' => $this->title,
            'venue' => $this->venue,
            'starts_at' => $form['starts'],
            'ends_at' => $form['ends'],
            'courts' => $this->courts,
            'cap' => $this->cap,
            'race_to' => $this->raceTo,
            'rotation' => $this->rotation,
            'visibility' => $this->visibility,
            'fee' => $form['fee'] ?: null,
        ] + $this->payFields($form['png']));
        // Same QR as before: keep its stored image and version (so its URL stays cached).
        if ($keepQr) {
            $play->forceFill(['pay_qr_version' => $qrVersion])->save();
        } else {
            $this->storeQr($play, $form['png']);
        }
        foreach ($form['dropped'] as $p) {
            if (self::leavesWhenDropped($p)) {
                $p->update(['left_at' => now()]);
            } else {
                $p->update(['paid' => false]);
                DB::table('pay_claims')->where(['open_play_id' => $play->id, 'user_id' => $p->user_id])->delete();
            }
        }
        $play->addPaidPlayers($form['newRows']);
        collect($form['newRows'])->pluck('user')->filter()->unique('id')->reject(fn ($u) => $u->id === auth()->id())
            ->each(fn ($u) => Emails::send($u, Emails::joined($play, $u)));

        // Tell the players who joined about changes that affect them (not cap, visibility or the paid list).
        $notify = $changes->where('notify', true)->values()->all();
        $told = 0;
        if ($notify) {
            $play->activeParticipants()->whereNotNull('user_id')->where('user_id', '!=', auth()->id())->with('user')->get()
                ->each(function ($p) use ($play, $notify, &$told) {
                    Emails::send($p->user, Emails::playUpdated($play, $p->user, $notify));
                    $told++;
                });
        }

        $this->resetForm();
        $this->create = false;
        $this->dispatch('toast', message: "{$play->title} updated".($told ? " · {$told} ".Str::plural('player', $told).' notified' : ''));
    }

    /** Pay columns for a saved or removed QR. */
    private function payFields(?string $png): array
    {
        return [
            'pay_method' => $png ? $this->payMethod : null,
            'pay_name' => $png ? trim($this->payName) : null,
            'pay_number' => $png ? (trim($this->payNumber) ?: null) : null,
            'pay_qr_version' => $png ? substr(sha1($png), 0, 12) : null,
        ];
    }

    private function storeQr(OpenPlay $play, ?string $png): void
    {
        $png
            ? DB::table('payment_qrs')->updateOrInsert(['open_play_id' => $play->id], ['data' => $png, 'updated_at' => now(), 'created_at' => now()])
            : DB::table('payment_qrs')->where('open_play_id', $play->id)->delete();
    }

    /**
     * The review list: one row per detail as [key, label, value, was, changed, notify].
     * When editing, a row is "changed" if it differs from the saved open play, and "notify" if
     * players who joined should hear about it.
     */
    private function reviewRows(array $form, ?string $qr): array
    {
        $play = $this->editId ? $this->editable($this->editId) : null;
        $method = OpenPlay::PAY_METHODS[$this->payMethod][0] ?? 'Payment';
        $payment = fn ($has, $label, $name, $number, $fee) => $has
            ? "{$label} QR · ".($name ?: 'no account name').($number ? " · {$number}" : '')
            : ($fee ? 'No QR, players pay at the venue' : 'Not needed');
        $paidTotals = function (array $rows, int $base = 0, int $baseLinked = 0) {
            $linked = $baseLinked + collect($rows)->whereNotNull('user')->count();
            $n = $base + count($rows);

            return $n ? "{$n} · {$linked} linked, ".($n - $linked).' '.Str::plural('guest', $n - $linked) : 'None yet';
        };

        $now = [
            'title' => ['Title', $this->title],
            'when' => ['When', $form['starts']->format('l, M j').' · '.OpenPlay::rangeLabel($form['starts'], $form['ends']).' · '.OpenPlay::lengthLabel($form['minutes'])],
            'venue' => ['Venue', $this->venue],
            'format' => ['Format', self::format($this->courts, $this->raceTo, $this->rotation)],
            'cap' => ['Player cap', "{$this->cap} players"],
            'vis' => ['Who can join', $this->visibility === 'invite' ? 'Invite only' : 'Any member'],
            'fee' => ['Fee', $form['fee'] ? "{$form['fee']} per player" : 'Free'],
            'pay' => ['Payment', $payment((bool) $form['png'], $method, trim($this->payName), trim($this->payNumber), $form['fee']).($play && $qr && $qr !== 'saved' ? ' · new QR image' : '')],
        ];

        if ($play) {
            $paidNow = $play->activeParticipants()->where('paid', true);
            $now['paid'] = ['Paid players', $paidTotals($form['newRows'], (clone $paidNow)->count() - $form['dropped']->count(), (clone $paidNow)->whereNotNull('user_id')->count() - $form['dropped']->whereNotNull('user_id')->count())];
            $was = [
                'title' => $play->title,
                'when' => $play->starts_at->format('l, M j').' · '.$play->timeRange().' · '.OpenPlay::lengthLabel((int) $play->starts_at->diffInMinutes($play->ends_at)),
                'venue' => $play->venue,
                'format' => self::format($play->courts, $play->race_to, $play->rotation),
                'cap' => "{$play->cap} players",
                'vis' => $play->visibility === 'invite' ? 'Invite only' : 'Any member',
                'fee' => $play->fee ? "{$play->fee} per player" : 'Free',
                'pay' => $payment($play->hasPaymentQr(), $play->payMethodLabel(), $play->pay_name, $play->pay_number, $play->fee),
                'paid' => $paidTotals([], (clone $paidNow)->count(), (clone $paidNow)->whereNotNull('user_id')->count()),
            ];
        } else {
            $now['paid'] = ['Paid players', $paidTotals($form['rows'])];
        }

        return collect($now)->map(fn ($r, $key) => [
            'key' => $key,
            'label' => $r[0],
            'value' => $r[1],
            'was' => $play ? $was[$key] : null,
            'changed' => $play && $was[$key] !== $r[1],
            'notify' => $play && $was[$key] !== $r[1] && in_array($key, ['when', 'venue', 'format', 'fee', 'pay']),
        ])->values()->all();
    }

    private static function format(int $courts, int $raceTo, string $rotation): string
    {
        return $courts.' '.Str::plural('court', $courts)." · race to {$raceTo} · ".($rotation === 'winners' ? 'winners stay (2 max)' : 'everyone rotates');
    }

    private function paidRows(): array
    {
        return PaidList::rows($this->paid, $this->links, auth()->user()->club);
    }

    /** The paid list as it would be typed (emails for linked players, names for guests) and its links. */
    private static function paidLines(OpenPlay $play): array
    {
        $paid = $play->activeParticipants()->where('paid', true)->with('user')->get();

        return [
            $paid->map(fn ($p) => $p->user?->email ?? $p->name)->join("\n"),
            $paid->mapWithKeys(fn ($p) => [PaidList::key($p->user?->email ?? $p->name) => $p->user_id ?? 'guest'])->all(),
        ];
    }

    /** Paid-list rows that aren't already paid players in this open play. */
    private static function newPaidRows(OpenPlay $play, array $rows): array
    {
        $existing = $play->activeParticipants()->where('paid', true)->get();
        $users = $existing->pluck('user_id')->filter()->all();
        $guests = $existing->whereNull('user_id')->map(fn ($p) => mb_strtolower($p->name))->all();

        return array_values(array_filter($rows, fn ($r) => $r['user'] ? ! in_array($r['user']->id, $users) : ! in_array(mb_strtolower($r['line']), $guests)));
    }

    /** Paid players in this open play whose line was taken out of the paid list. */
    private static function droppedPaid(OpenPlay $play, array $rows): Collection
    {
        $users = collect($rows)->pluck('user')->filter()->pluck('id')->all();
        $guests = collect($rows)->whereNull('user')->map(fn ($r) => mb_strtolower($r['line']))->all();

        return $play->activeParticipants()->where('paid', true)->with('user')->get()
            ->reject(fn ($p) => $p->user_id ? in_array($p->user_id, $users) : in_array(mb_strtolower($p->name), $guests))->values();
    }

    /** Guests and accounts added from the paid list leave when their line goes; members who joined in the app stay, marked not paid. */
    private static function leavesWhenDropped(Participant $p): bool
    {
        return ! $p->user_id || $p->from_list;
    }

    /** An upcoming open play of yours that can still be edited or deleted. */
    private function editable(int $id): OpenPlay
    {
        return auth()->user()->openPlays()->status('upcoming')->findOrFail($id);
    }

    public function askDelete(int $id): void
    {
        $this->deleteId = $this->editable($id)->id;
        $this->create = false;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function openShare(int $id): void
    {
        $this->share = $this->runnable($id)->id;
    }

    /** An upcoming open play you host or co-host: start it, share it, see who's in. */
    private function runnable(int $id): OpenPlay
    {
        return OpenPlay::runBy(auth()->user())->status('upcoming')->findOrFail($id);
    }

    // ------------------------------------------------------------ co-hosts (the host only)

    /** Upcoming open play whose Co-hosts dialog is showing, and its account search. */
    public ?int $cohostFor = null;

    public string $cohostQ = '';

    public function openCohosts(int $id): void
    {
        $this->cohostFor = $this->editable($id)->id;
        $this->cohostQ = '';
    }

    public function closeCohosts(): void
    {
        $this->cohostFor = null;
    }

    public function addCohost(int $userId): void
    {
        $play = $this->editable((int) $this->cohostFor);
        $user = $this->cohostCandidates($play, '', all: true)->whereKey($userId)->firstOrFail();
        if ($play->cohosts()->count() >= OpenPlay::MAX_COHOSTS) {
            $this->dispatch('toast', message: 'Up to '.OpenPlay::MAX_COHOSTS.' co-hosts per open play');

            return;
        }
        $play->cohosts()->attach($user);
        $this->cohostQ = '';
        Emails::send($user, Emails::cohost($play, $user, true));
        $this->dispatch('toast', message: "{$user->name} can start and run {$play->title} · we emailed them");
    }

    public function removeCohost(int $userId): void
    {
        $play = $this->editable((int) $this->cohostFor);
        $user = $play->cohosts()->whereKey($userId)->firstOrFail();
        $play->cohosts()->detach($user);
        Emails::send($user, Emails::cohost($play, $user, false));
        $still = $play->activeParticipants()->where('user_id', $user->id)->exists();
        $this->dispatch('toast', message: "{$user->name} is no longer a co-host · we emailed them".($still ? ', and they stay on the player list' : ''));
    }

    /**
     * Who can co-host: any active account except the host, admins and current co-hosts. With no search yet,
     * players who joined this open play first, then organizers.
     */
    private function cohostCandidates(OpenPlay $play, string $q, bool $all = false)
    {
        $query = User::query()->whereKeyNot($play->organizer_id)->where('role', '!=', 'admin')->whereNull('suspended_at')
            ->whereNotIn('id', $play->cohosts()->pluck('users.id'));
        if ($all) {
            return $query;
        }
        $term = mb_strtolower(trim($q));
        if ($term !== '') {
            return $query->where(fn ($w) => $w->whereRaw('lower(name) like ?', ["%{$term}%"])->orWhereRaw('lower(email) like ?', ["%{$term}%"])->orWhereRaw('lower(club) like ?', ["%{$term}%"]))
                ->orderBy('name');
        }
        $joined = $play->activeParticipants()->whereNotNull('user_id')->orderBy('created_at')->pluck('user_id')->all();

        return $query->where(fn ($w) => $w->whereIn('id', $joined ?: [0])->orWhere('organizer_status', 'approved'))
            ->orderByRaw('case when id in ('.(implode(',', array_map('intval', $joined)) ?: '0').') then 0 else 1 end')->orderBy('name');
    }

    private function cohostsDialog(): ?array
    {
        if (! $this->cohostFor) {
            return null;
        }
        $play = $this->editable($this->cohostFor);
        $joined = $play->activeParticipants()->whereNotNull('user_id')->pluck('user_id')->all();

        return [
            'play' => $play,
            'now' => $play->cohosts()->orderBy('open_play_cohosts.created_at')->get(),
            'cands' => $this->cohostCandidates($play, $this->cohostQ)->limit(5)->get(),
            'joined' => $joined,
        ];
    }

    public function closeShare(): void
    {
        $this->share = null;
    }

    public function setShareVisibility(string $visibility): void
    {
        $play = $this->editable((int) $this->share);
        if (! in_array($visibility, ['public', 'invite']) || $play->visibility === $visibility) {
            return;
        }
        $play->update(['visibility' => $visibility]);
        $this->dispatch('toast', message: $visibility === 'invite' ? "{$play->title} is invite only · only people with the link can find it" : "{$play->title} shows in Open plays for everyone");
    }

    public function toggleLinkGuests(): void
    {
        $play = $this->editable((int) $this->share);
        $play->update(['link_guests' => ! $play->link_guests]);
    }

    public function resetShareLink(): void
    {
        $this->editable((int) $this->share)->resetLink();
        $this->dispatch('toast', message: 'New link ready · the old link stops working');
    }

    public function rosterEdit(): void
    {
        $id = $this->rosterId;
        $this->rosterId = null;
        $this->edit($id);
    }

    /** Mark someone on the open play as paid or not paid. Not paid also clears their "I've sent the payment". */
    public function setPaid(int $participantId, bool $paid): void
    {
        $play = $this->editable((int) $this->rosterId);
        abort_unless($play->fee, 404);
        $p = $play->activeParticipants()->findOrFail($participantId);
        $p->update(['paid' => $paid]);
        if (! $paid && $p->user_id) {
            DB::table('pay_claims')->where(['open_play_id' => $play->id, 'user_id' => $p->user_id])->delete();
        }
        $this->dispatch('toast', message: $p->firstName().($paid ? ' marked as paid' : ' marked as not paid'));
    }

    /** Delete an upcoming open play; everyone who joined gets a cancellation email. */
    public function deletePlay(): void
    {
        $play = $this->editable((int) $this->deleteId)->loadMissing('organizer');
        $players = $play->activeParticipants()->whereNotNull('user_id')->where('user_id', '!=', auth()->id())->with('user')->get();
        // Built before the delete: the emails go out after the response.
        $mails = $players->map(fn ($p) => [$p->user, Emails::playCancelled($play, $p->user, (bool) $p->paid)]);

        $title = $play->title;
        $play->delete();
        $mails->each(fn ($m) => Emails::send($m[0], $m[1]));

        $this->deleteId = null;
        if ($this->editId === $play->id) {
            $this->resetForm();
        }
        $n = $mails->count();
        $this->dispatch('toast', message: "{$title} deleted".($n ? " · {$n} ".Str::plural('player', $n).' notified' : ''));
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
        $this->links = [];
        $this->visibility = 'public';
        $this->error = null;
        $this->editId = null;
        $this->review = false;
        $this->reviewData = [];
        $last = $this->lastPayPlay();
        $this->payMethod = $last?->pay_method ?? 'gcash';
        $this->payName = $last?->pay_name ?? '';
        $this->payNumber = $last?->pay_number ?? '';
    }

    /**
     * Email a new public open play to the club: members who have played at one of this
     * organizer's open plays before, aren't in this one yet and keep "New open plays" on.
     */
    protected function announce(OpenPlay $play): void
    {
        if ($play->visibility !== 'public') {
            return;
        }

        User::whereIn('id', Participant::whereIn('open_play_id', auth()->user()->openPlays()->whereKeyNot($play->id)->select('id'))->whereNotNull('user_id')->select('user_id'))
            ->whereKeyNot(auth()->id())
            ->whereNotIn('id', $play->participants()->whereNotNull('user_id')->select('user_id'))
            ->get()
            ->filter(fn (User $u) => $u->pref('notify.newPlays'))
            ->each(fn (User $u) => Emails::send($u, Emails::newPlay($play, $u)));
    }

    /** Totals across the organizer's concluded open plays: sessions, games, player check-ins, unique players. */
    protected function hostedTotals(): array
    {
        $ids = auth()->user()->openPlays()->status('done')->pluck('id');
        $played = Participant::whereIn('id', GamePlayer::query()
            ->join('games', 'games.id', '=', 'game_players.game_id')
            ->whereIn('games.open_play_id', $ids)
            ->select('game_players.participant_id'))->get(['user_id', 'name']);

        return [
            'sessions' => $ids->count(),
            'games' => Game::whereIn('open_play_id', $ids)->count(),
            'checkins' => $played->count(),
            'unique' => $played->unique(fn ($p) => $p->user_id ?: 'guest:'.Str::lower($p->name))->count(),
        ];
    }

    public function render()
    {
        $query = fn () => auth()->user()->openPlays()->withCount(['activeParticipants', 'games']);
        // Open plays you co-host show here too, with what a co-host can do.
        $plays = OpenPlay::runBy(auth()->user())->withCount(['activeParticipants', 'games'])->with(['organizer', 'cohosts', 'starter'])->whereIn('status', ['live', 'upcoming'])->withCount([
            'activeParticipants as paid_count' => fn ($q) => $q->where('paid', true),
            'activeParticipants as paid_linked_count' => fn ($q) => $q->where('paid', true)->whereNotNull('user_id'),
        ])->with(['activeParticipants' => fn ($q) => $q->with('user')->orderBy('created_at')->orderBy('id')])->orderBy('starts_at')->get();

        return view('livewire.organizer.open-plays', [
            'club' => auth()->user()->club ?? 'Your club',
            // A just-approved organizer with no open plays gets a welcome with first steps instead of empty lists.
            'hasPlays' => OpenPlay::runBy(auth()->user())->exists(),
            'live' => $plays->where('status', 'live'),
            'upcoming' => $plays->where('status', 'upcoming'),
            'past' => $query()->status('done')->latest('starts_at')->paginate(5, pageName: 'pastPage'),
            'hosted' => $this->hostedTotals(),
            'paidPreview' => $this->create ? $this->paidRows() : [],
            'lastQrUrl' => $this->lastPayPlay()?->payQrUrl(),
            'deleting' => $this->deleteId ? $plays->firstWhere('id', $this->deleteId) : null,
            'roster' => $this->roster(),
            'sharePlay' => $this->share ? OpenPlay::runBy(auth()->user())->status('upcoming')->find($this->share) : null,
            'cohosts' => $this->cohostsDialog(),
        ]);
    }
}
