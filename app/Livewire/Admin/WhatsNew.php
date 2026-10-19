<?php

namespace App\Livewire\Admin;

use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin › What's new: write updates (new features, enhancements, bug fixes) for everyone, players only or organizers only.
 * Save a draft or publish; a published update pops up once for everyone in its audience who hasn't read it.
 */
#[Title("What's new | PickleX")]
class WhatsNew extends Component
{
    #[Url(except: 'live')]
    public string $tab = 'live'; // live | draft

    // The editor: null closed, 0 a new update, else the update's id.
    public ?int $editing = null;

    public string $title = '';

    public string $body = '';

    public string $kind = 'feature';

    public string $audience = 'all';

    public string $error = '';

    public ?int $deleting = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function edit(?int $id = null): void
    {
        $n = $id ? News::findOrFail($id) : null;
        $this->editing = $n?->id ?? 0;
        $this->title = $n->title ?? '';
        $this->body = $n->body ?? '';
        $this->kind = $n->kind ?? 'feature';
        $this->audience = $n->audience ?? 'all';
        $this->error = '';
    }

    public function closeEditor(): void
    {
        $this->reset('editing', 'title', 'body', 'kind', 'audience', 'error');
    }

    public function pick(string $field, string $value): void
    {
        if (($field === 'kind' && isset(News::KINDS[$value])) || ($field === 'audience' && isset(News::AUDIENCES[$value]))) {
            $this->$field = $value;
        }
    }

    /** Save the editor: a draft, a published update's changes, or publish (a new update or a draft). */
    public function save(bool $publish = false): void
    {
        $title = Str::limit(trim($this->title), 60, '');
        $body = Str::limit(trim($this->body), 280, '');
        $this->error = match (true) {
            $title === '' => 'Give the update a title.',
            $body === '' => 'Add a short description of what changed.',
            default => '',
        };
        if ($this->error) {
            return;
        }

        $n = $this->editing ? News::findOrFail($this->editing) : new News;
        $wasLive = $n->exists && $n->isLive();
        $goLive = $publish && ! $wasLive;
        $n->fill(['title' => $title, 'body' => $body, 'kind' => $this->kind, 'audience' => $this->audience]);
        if ($goLive) {
            $n->fill(['published_at' => now(), 'published_by' => auth()->id()]);
        }
        $n->save();

        $this->closeEditor();
        $this->tab = $goLive || $wasLive ? 'live' : 'draft';
        $this->dispatch('toast', message: match (true) {
            $goLive => $this->reach($n->audience),
            $wasLive => "Update saved, people who already read it won't see it again",
            default => 'Draft saved, only admins can see it',
        });
    }

    public function publish(int $id): void
    {
        $n = News::whereNull('published_at')->findOrFail($id);
        $n->update(['published_at' => now(), 'published_by' => auth()->id()]);
        $this->tab = 'live';
        $this->dispatch('toast', message: $this->reach($n->audience));
    }

    public function delete(): void
    {
        $n = News::findOrFail($this->deleting);
        $n->delete();
        $this->deleting = null;
        $this->dispatch('toast', message: ($n->isLive() ? "Update deleted, it's gone from everyone's What's new, " : 'Draft deleted, ').$n->title);
    }

    public function render()
    {
        $all = News::with('publisher')->orderByDesc(DB::raw('published_at is null'))->latest('published_at')->latest('id')->get();
        [$live, $drafts] = $all->partition(fn ($n) => $n->isLive());
        $sizes = collect(News::AUDIENCES)->map(fn ($l, $a) => News::audience($a)->count());
        $read = fn (News $n) => $n->readers()->whereIn('users.id', News::audience($n->audience)->select('users.id'))->count();
        $latest = $live->first();
        // Everyone with at least one published update they haven't read.
        // ponytail: one query per person; make it a single join if the user count gets large.
        $waiting = $live->isEmpty() ? 0 : News::audience('all')->get()->filter(fn (User $u) => News::unreadBy($u)->exists())->count();
        $editing = $this->editing ? $all->firstWhere('id', $this->editing) : null;

        return view('livewire.admin.whats-new', [
            'rows' => ($this->tab === 'draft' ? $drafts : $live)->values(),
            'tabs' => ['live' => ['Published', $live->count()], 'draft' => ['Drafts', $drafts->count()]],
            'tiles' => [
                ['Published', $live->count(), false],
                ['Drafts', $drafts->count(), false],
                ['Latest read by', $latest ? $this->pct($read($latest), $sizes[$latest->audience]).'%' : '-', false],
                ['Still to see one', $waiting, $waiting > 0],
            ],
            'sizes' => $sizes,
            'read' => $read,
            'pct' => fn ($a, $b) => $this->pct($a, $b),
            'editingLive' => (bool) $editing?->isLive(),
            'preview' => new News(['title' => trim($this->title) ?: 'Your title', 'body' => trim($this->body) ?: 'What changed, in a sentence or two.', 'kind' => $this->kind, 'audience' => $this->audience]),
            'toDelete' => $this->deleting ? $all->firstWhere('id', $this->deleting) : null,
        ]);
    }

    private function pct(int $read, int $of): int
    {
        return $of ? (int) round($read / $of * 100) : 0;
    }

    /** "Published, shows once to 23 people next time they open PickleX". */
    private function reach(string $audience): string
    {
        $n = News::audience($audience)->count();

        return 'Published, shows once to '.$n.' '.($n === 1 ? 'person' : 'people').' next time they open PickleX';
    }
}
