<?php

namespace App\Livewire\Organizer;

use App\Models\OpenPlay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Your open plays · PickleX')]
class OpenPlays extends Component
{
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

    public function publish(): void
    {
        $this->title = trim($this->title);
        $this->venue = trim($this->venue);
        $missing = 'Add a title and a venue so members know where to go.';

        $v = validator($this->all(), [
            'title' => 'required|max:120',
            'venue' => 'required|max:160',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'starts' => 'required|date_format:H:i',
            'ends' => 'required|date_format:H:i|after:starts',
            'courts' => 'integer|between:1,12',
            'cap' => 'integer|between:4,64',
            'raceTo' => 'in:11,15,21',
            'rotation' => 'in:rotate,winners',
            'visibility' => 'in:public,invite',
            'fee' => 'max:40',
        ], [
            'title.required' => $missing,
            'venue.required' => $missing,
            'date.after_or_equal' => 'Pick a date from today on.',
            'ends.after' => 'The session has to end after it starts.',
        ]);

        if ($v->fails()) {
            $this->error = $v->errors()->first();

            return;
        }

        $user = auth()->user();
        $user->openPlays()->create([
            'title' => $this->title,
            'club' => $user->club ?? 'Your club',
            'venue' => $this->venue,
            'starts_at' => Carbon::parse("{$this->date} {$this->starts}"),
            'ends_at' => Carbon::parse("{$this->date} {$this->ends}"),
            'courts' => $this->courts,
            'cap' => $this->cap,
            'race_to' => $this->raceTo,
            'rotation' => $this->rotation,
            'visibility' => $this->visibility,
            'fee' => trim($this->fee) ?: null,
            'status' => 'upcoming',
        ]);

        $this->resetForm();
        $this->create = false;
        $this->dispatch('toast', message: 'Open play published — members can join now');
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

        session()->flash('toast', "{$play->title} is live — courts filled from the queue");

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
        $this->title = $this->venue = $this->fee = '';
        $this->visibility = 'public';
        $this->error = null;
    }

    public function render()
    {
        $query = fn () => auth()->user()->openPlays()->withCount(['activeParticipants', 'games']);
        $plays = $query()->whereIn('status', ['live', 'upcoming'])->orderBy('starts_at')->get();

        return view('livewire.organizer.open-plays', [
            'club' => auth()->user()->club ?? 'Your club',
            'live' => $plays->where('status', 'live'),
            'upcoming' => $plays->where('status', 'upcoming'),
            // Recent history only; older sessions still count toward stats.
            'past' => $query()->status('done')->latest('starts_at')->limit(10)->get(),
        ]);
    }
}
