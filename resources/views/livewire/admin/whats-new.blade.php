@php
    $kinds = App\Models\News::KINDS;
    $auds = App\Models\News::AUDIENCES;
    $chip = 'inline-flex h-10.5 cursor-pointer items-center gap-1.5 rounded-full border-[1.5px] px-3.5 text-sm font-bold transition active:scale-97';
    $on = 'border-ink bg-ink text-bg';
    $off = 'border-line2 bg-surface text-ink';
    $label = 'text-[13px] font-bold text-ink2';
    $count = 'font-mono text-xs text-ink3';
    $field = 'w-full rounded-[14px] border-[1.5px] border-line bg-surface px-4 text-[15px] font-medium text-ink outline-none focus:border-ink';
    $ghost = 'h-13 flex-1 cursor-pointer rounded-full border-[1.5px] border-line2 bg-transparent text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97';
    $cta = 'h-13 flex-1 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97';
    $people = fn ($n) => $n.' '.($n === 1 ? 'person' : 'people');
@endphp

<x-app-shell>
    <x-page>
        <x-page-header eyebrow="Admin" title="What's new" description="Tell everyone what changed: new features, enhancements and bug fixes. A published update shows once to everyone in its audience who hasn't read it, then stays in What's new in their account menu.">
            <button type="button" wire:click="edit" aria-haspopup="dialog" class="inline-flex h-12.5 shrink-0 cursor-pointer items-center gap-2 rounded-full border-2 border-cta-border bg-lime px-5 text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">
                <x-icon name="plus" :size="18" :stroke="2.4" />New update
            </button>
        </x-page-header>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($tiles as [$tl, $n, $lit])
                <div @class(['flex flex-col gap-2 rounded-[22px] border p-4 lg:p-5', 'border-lime-soft bg-lime-soft text-lime-text' => $lit, 'border-line bg-surface' => ! $lit])>
                    <span class="font-mono text-xs tracking-widest uppercase">{{ $tl }}</span>
                    <span class="font-display text-[34px] leading-none font-extrabold tracking-[-0.04em]">{{ $n }}</span>
                </div>
            @endforeach
        </div>

        <div role="tablist" aria-label="Show updates" class="inline-flex max-w-full gap-1 self-start overflow-x-auto rounded-full border border-line bg-surface p-1 [scrollbar-width:none]">
            @foreach ($tabs as $key => [$tl, $n])
                @php($sel = $tab === $key)
                <button type="button" role="tab" aria-selected="{{ $sel ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')"
                        @class(['inline-flex h-10 shrink-0 cursor-pointer items-center gap-2 rounded-full px-3.5 text-sm font-bold whitespace-nowrap transition-colors', 'bg-ink text-bg' => $sel, 'text-ink2' => ! $sel])>
                    {{ $tl }}<span @class(['inline-flex h-5 min-w-5.5 items-center justify-center rounded-full px-1.5 font-mono text-xs font-semibold', 'bg-lime text-on-lime' => $sel, 'bg-surface2 text-ink2' => ! $sel])>{{ $n }}</span>
                </button>
            @endforeach
        </div>

        <section aria-label="Updates" class="overflow-hidden rounded-3xl border border-line bg-surface">
            @forelse ($rows as $n)
                @php([$kl, $icon, $tone] = $kinds[$n->kind] ?? $kinds['feature'])
                <div wire:key="an-{{ $n->id }}" class="flex flex-wrap items-center gap-x-6 gap-y-3.5 border-t border-line px-5 py-4.5 first:border-t-0">
                    <div class="flex min-w-0 flex-[1_1_420px] items-start gap-3.5">
                        <span aria-hidden="true" class="flex size-10.5 shrink-0 items-center justify-center rounded-[14px] border {{ $tone }}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}" /></svg>
                        </span>
                        <div class="flex min-w-0 flex-1 flex-col gap-1.25">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex h-6 items-center gap-1.25 rounded-full border px-2.25 text-xs font-extrabold whitespace-nowrap {{ $tone }}"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}" /></svg>{{ $kl }}</span>
                                @if ($n->audience !== 'all')
                                    <span class="inline-flex h-6 items-center rounded-full border border-line bg-surface2 px-2.25 text-xs font-bold whitespace-nowrap text-ink2">{{ $auds[$n->audience] }}</span>
                                @endif
                                @unless ($n->isLive())
                                    <span class="inline-flex h-6 items-center rounded-full border-[1.5px] border-dashed border-line2 px-2.25 text-xs font-extrabold text-ink2">Draft</span>
                                @endunless
                            </div>
                            <span class="text-base leading-[1.3] font-bold">{{ $n->title }}</span>
                            <span class="line-clamp-2 text-sm leading-[1.45] text-ink2">{{ $n->body }}</span>
                            <span class="text-[12.5px] text-ink3">{{ $auds[$n->audience] }} · {{ $n->isLive() ? 'Published '.$n->dateLabel().' by '.($n->publisher?->name ?? 'an admin') : 'Draft · saved '.($n->updated_at->isToday() ? 'Today' : $n->updated_at->format('M j')) }}</span>
                        </div>
                    </div>
                    @if ($n->isLive())
                        @php($r = $read($n))
                        @php($of = $sizes[$n->audience])
                        @php($p = $pct($r, $of))
                        <div role="img" aria-label="{{ $r }} of {{ $of }} people in the audience have read it" class="flex flex-[0_0_190px] flex-col gap-1.5">
                            <div class="flex justify-between gap-2 text-[13px]"><span class="font-bold">{{ $r }} of {{ $of }} read</span><span class="font-mono font-semibold text-ink3">{{ $p }}%</span></div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-ink" style="width: {{ $p }}%"></div></div>
                        </div>
                    @endif
                    <div class="flex shrink-0 gap-2">
                        @unless ($n->isLive())
                            <button type="button" wire:click="publish({{ $n->id }})" aria-label="Publish {{ $n->title }}" class="inline-flex h-10 cursor-pointer items-center gap-1.5 rounded-full bg-lime px-3.5 text-[13.5px] font-extrabold text-on-lime transition hover:brightness-96 active:scale-97"><x-icon name="arrow-right" :size="15" :stroke="2.2" />Publish</button>
                        @endunless
                        <button type="button" wire:click="edit({{ $n->id }})" aria-label="Edit {{ $n->title }}" aria-haspopup="dialog" class="inline-flex h-10 cursor-pointer items-center gap-1.5 rounded-full border-[1.5px] border-line2 px-3.5 text-[13.5px] font-bold text-ink transition hover:bg-surface2 active:scale-97"><x-icon name="edit" :size="16" :stroke="2" />Edit</button>
                        <button type="button" wire:click="$set('deleting', {{ $n->id }})" aria-label="Delete {{ $n->title }}" aria-haspopup="dialog" class="flex size-10 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-hot-line bg-hot-soft text-hot transition hover:brightness-96 active:scale-97"><x-icon name="trash" :size="16" :stroke="2" /></button>
                    </div>
                </div>
            @empty
                <p class="m-0 px-5 py-6 text-ink2">{{ $tab === 'live' ? 'Nothing published yet. Write your first update.' : 'No drafts. Save an update as a draft to finish it later.' }}</p>
            @endforelse
        </section>
    </x-page>

    {{-- Write or edit an update --}}
    @if ($editing !== null)
        <div wire:key="an-edit" x-data x-on:keydown.escape.window="$wire.closeEditor()" class="fixed inset-0 z-67 flex items-end justify-center md:items-center md:p-6">
            <button type="button" wire:click="closeEditor" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
            <div role="dialog" aria-modal="true" aria-labelledby="an-title" x-trap.noscroll="true" class="relative flex max-h-full w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-135 md:max-w-full md:rounded-[28px]">
                <div class="flex shrink-0 items-start justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex min-w-0 flex-col gap-1">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ ! $editing ? "Admin · What's new" : ($editingLive ? 'Edit a published update' : 'Edit a draft') }}</span>
                        <h2 id="an-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $editing ? 'Edit update' : 'New update' }}</h2>
                    </div>
                    <button type="button" wire:click="closeEditor" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink"><x-icon name="x" :size="18" /></button>
                </div>
                <div class="flex min-h-0 flex-1 flex-col gap-4.5 overflow-y-auto px-6 py-4.5">
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between gap-2"><label for="an-t" class="{{ $label }}">Title</label><span class="{{ $count }}" x-text="($wire.title || '').length + '/60'">{{ mb_strlen($title) }}/60</span></div>
                        <input id="an-t" type="text" maxlength="60" wire:model.live.debounce.300ms="title" placeholder="e.g. Warmup countdown on every court" class="{{ $field }} h-12.5">
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="{{ $label }}">Type</span>
                        <div role="radiogroup" aria-label="Type of update" class="flex flex-wrap gap-2">
                            @foreach ($kinds as $k => [$kl, $icon])
                                <button type="button" role="radio" aria-checked="{{ $kind === $k ? 'true' : 'false' }}" wire:click="pick('kind', '{{ $k }}')" class="{{ $chip }} {{ $kind === $k ? $on : $off }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}" /></svg>{{ $kl }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between gap-2"><label for="an-b" class="{{ $label }}">What changed</label><span class="{{ $count }}" x-text="($wire.body || '').length + '/280'">{{ mb_strlen($body) }}/280</span></div>
                        <textarea id="an-b" rows="4" maxlength="280" wire:model.live.debounce.300ms="body" placeholder="A sentence or two in plain words: what's different and why it helps." class="{{ $field }} resize-y py-3 leading-normal"></textarea>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="{{ $label }}">Who sees it</span>
                        <div role="radiogroup" aria-label="Who sees it" class="flex flex-wrap gap-2">
                            @foreach ($auds as $a => $al)
                                <button type="button" role="radio" aria-checked="{{ $audience === $a ? 'true' : 'false' }}" wire:click="pick('audience', '{{ $a }}')" class="{{ $chip }} {{ $audience === $a ? $on : $off }}">{{ $al }}</button>
                            @endforeach
                        </div>
                        <span class="text-[13px] leading-[1.45] text-ink3">Shows once to {{ $people($sizes[$audience]) }}{{ ['organizer' => ' who organize', 'member' => ' who only play'][$audience] ?? '' }} after they sign in or open PickleX, then stays in What's new in their account menu.</span>
                    </div>
                    @if ($error)
                        <p role="alert" class="m-0 rounded-[14px] border border-hot-line bg-hot-soft px-3.5 py-3 text-sm font-semibold text-hot">{{ $error }}</p>
                    @endif
                    <div class="flex flex-col gap-2">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Preview · how people see it</span>
                        <x-news-card :news="$preview" :fresh="true" />
                    </div>
                    @if ($editingLive)
                        <p class="m-0 text-[13px] text-ink3">Edits don't show it again to people who already read it.</p>
                    @endif
                </div>
                <div class="flex shrink-0 gap-2.5 border-t border-line px-6 pt-3.5 pb-5.5">
                    @if ($editingLive)
                        <button type="button" wire:click="closeEditor" class="{{ $ghost }}">Cancel</button>
                        <button type="button" wire:click="save" class="{{ $cta }}">Save changes</button>
                    @else
                        <button type="button" wire:click="save" class="{{ $ghost }}">Save draft</button>
                        <button type="button" wire:click="save(true)" class="{{ $cta }}">Publish</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Delete an update --}}
    @if ($toDelete)
        @php($unread = $toDelete->isLive() ? $sizes[$toDelete->audience] - $read($toDelete) : 0)
        <div wire:key="an-del-{{ $toDelete->id }}" x-data x-on:keydown.escape.window="$wire.set('deleting', null)" class="fixed inset-0 z-68 flex items-end justify-center md:items-center md:p-6">
            <button type="button" wire:click="$set('deleting', null)" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
            <div role="alertdialog" aria-modal="true" aria-labelledby="and-title" x-trap.noscroll="true" class="relative flex max-h-full w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-120 md:max-w-full md:rounded-[28px]">
                <div class="flex flex-col gap-3.5 px-6 pt-6.5 pb-4.5">
                    <span class="flex size-13 items-center justify-center rounded-2xl border border-hot-line bg-hot-soft text-hot"><x-icon name="trash" :size="22" /></span>
                    <h2 id="and-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">Delete "{{ $toDelete->title }}"?</h2>
                    <p class="m-0 text-[15px] leading-normal text-ink2">{{ ! $toDelete->isLive() ? 'Only admins have seen this draft.' : "It comes off What's new for everyone. ".($unread ? ($unread === 1 ? "1 person who hasn't" : "{$unread} people who haven't")." read it yet won't see it." : 'Everyone in its audience has already read it.') }}</p>
                    <p class="m-0 text-[13px] text-ink3">This can't be undone.</p>
                </div>
                <div class="flex shrink-0 gap-2.5 border-t border-line px-6 pt-3.5 pb-5.5">
                    <button type="button" wire:click="$set('deleting', null)" class="{{ $ghost }}">Keep it</button>
                    <button type="button" wire:click="delete" class="h-13 flex-1 cursor-pointer rounded-full bg-hot text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">{{ $toDelete->isLive() ? 'Delete update' : 'Delete draft' }}</button>
                </div>
            </div>
        </div>
    @endif
</x-app-shell>
