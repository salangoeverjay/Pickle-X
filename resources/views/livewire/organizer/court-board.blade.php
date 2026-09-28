<div wire:poll.15s>
<x-app-shell>
    <x-page class="max-w-350 lg:gap-5.5">
        @if (! $play)
            <div class="flex flex-col items-start gap-3.5 rounded-[28px] border-[1.5px] border-dashed border-line2 bg-surface p-8">
                <span class="flex size-14 items-center justify-center rounded-[18px] bg-surface2"><x-icon name="court" :size="26" :stroke="1.9" /></span>
                <h1 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[44px]">No live session</h1>
                <p class="m-0 text-ink2">Start one from your open plays when players arrive, and the court board opens here.</p>
                <a href="{{ route('organizer.plays') }}" wire:navigate class="inline-flex h-12 items-center rounded-full bg-ink px-5 font-bold text-bg transition hover:brightness-96 active:scale-97">Go to open plays</a>
            </div>
        @else
            {{-- Header --}}
            <div class="flex flex-wrap items-end justify-between gap-3.5">
                <div class="flex flex-col gap-2">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="inline-flex h-7 items-center gap-1.75 rounded-full bg-ink px-2.75 font-mono text-[11px] tracking-widest text-bg"><span class="size-1.75 animate-live rounded-full bg-lime"></span>LIVE</span>
                        <span class="text-[13px] text-ink3">{{ $started }} · {{ $play->rotationLabel() }} · Race to {{ $play->race_to }}</span>
                    </div>
                    <h1 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[44px]">{{ $play->title }}</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" role="switch" aria-checked="{{ $play->auto_fill ? 'true' : 'false' }}" wire:click="toggleAutoFill"
                            class="inline-flex h-11 cursor-pointer items-center gap-2.5 rounded-full border-[1.5px] border-line2 bg-surface pr-2.5 pl-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                        Auto-fill courts
                        <span @class(['relative h-6 w-10 rounded-full transition-colors', 'bg-lime' => $play->auto_fill, 'bg-line2' => ! $play->auto_fill])>
                            <span @class(['absolute top-0.75 size-4.5 rounded-full transition-[left]', 'left-4.75 bg-on-lime' => $play->auto_fill, 'left-0.75 bg-white' => ! $play->auto_fill])></span>
                        </span>
                    </button>
                    <button type="button" wire:click="end" wire:confirm="End this session? Players will be released."
                            class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-hot-line bg-hot-soft px-4 text-sm font-bold text-hot transition hover:brightness-96 active:scale-97">
                        <x-icon name="stop" :size="15" :stroke="2.2" />End session
                    </button>
                </div>
            </div>

            {{-- Stats --}}
            @php($open = count($courts) - $busy)
            <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-4">
                @foreach ([
                    ['On court', $busy * 4, 'border-line bg-surface text-ink'],
                    ['In queue', $inQueue, 'border-line bg-surface text-ink'],
                    ['Open courts', $open, $open ? 'border-hot-line bg-hot-soft text-hot' : 'border-line bg-surface text-ink'],
                    ['Games logged', $games, 'border-ink bg-ink text-bg'],
                ] as [$label, $value, $tone])
                    <div class="flex flex-col gap-1 rounded-[18px] border px-4 py-3.5 {{ $tone }}">
                        <span class="font-mono text-[11px] tracking-widest uppercase">{{ $label }}</span>
                        <span class="font-display text-[30px] leading-[1.05] font-extrabold tracking-[-0.04em]">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Mobile column switch --}}
            <div role="tablist" aria-label="Court board view" class="grid grid-cols-2 rounded-full border border-line bg-surface p-1 lg:hidden">
                @foreach (['courts' => 'Courts · '.$busy.'/'.count($courts), 'queue' => 'Queue · '.$inQueue] as $key => $label)
                    <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')"
                            @class(['h-11 cursor-pointer rounded-full text-sm font-bold transition-colors', 'bg-ink text-bg' => $tab === $key, 'text-ink2' => $tab !== $key])>{{ $label }}</button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
                {{-- Courts column --}}
                <div @class(['min-w-0 flex-col gap-4', 'flex' => $tab === 'courts', 'hidden lg:flex' => $tab !== 'courts'])>
                    <div class="grid grid-cols-1 gap-3.5 lg:grid-cols-2">
                        @foreach ($courts as $i => $court)
                            <div wire:key="court-{{ $court['n'] }}" class="flex flex-col gap-3 rounded-3xl border border-line bg-surface p-3.5 shadow-card">
                                <div class="flex items-center justify-between gap-2.5 px-1 pt-0.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="font-display text-xl font-extrabold tracking-[-0.03em]">Court {{ $court['n'] }}</span>
                                        @if ($court['a'])
                                            <span class="inline-flex h-6 items-center rounded-full bg-lime-soft px-2.25 text-xs font-bold text-lime-text">In play</span>
                                        @else
                                            <span class="inline-flex h-6 items-center rounded-full bg-hot-soft px-2.25 text-xs font-bold text-hot">Open</span>
                                        @endif
                                    </div>
                                    @if ($court['a'])
                                        <span class="inline-flex items-center gap-1.5 font-mono text-[15px] font-semibold text-ink2">
                                            <x-icon name="clock" :size="15" /><x-live-timer :start="now()->timestamp - $court['started_at']" />
                                        </span>
                                    @endif
                                </div>

                                <x-live-court :play="$play" :court="$court" :badge="false" :locks="true" :index="$i" class="h-41 lg:h-44.5" player-class="size-8.5 text-xs lg:size-9.5" />

                                @if ($court['a'])
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 px-1">
                                        @foreach (['a', 'b'] as $side)
                                            @if ($side === 'b')
                                                <span class="font-mono text-[11px] text-ink3">VS</span>
                                            @endif
                                            <div @class(['flex min-w-0 flex-col gap-0.5', 'items-end' => $side === 'b'])>
                                                <span class="font-mono text-[10px] tracking-[.12em] text-ink3">TEAM {{ strtoupper($side) }}</span>
                                                <span @class(['flex min-w-0 items-center gap-1.5 text-[15px] font-bold', 'flex-row-reverse' => $side === 'b'])>
                                                    <span class="truncate">{{ $play->names($court[$side]['ids']) }}</span>
                                                    @if ($court[$side]['locked'])
                                                        <x-icon name="lock" :size="12" :stroke="2.6" class="text-lime-text" />
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" wire:click="openScore({{ $court['n'] }})"
                                            class="inline-flex h-11.5 cursor-pointer items-center justify-center gap-2 rounded-[14px] bg-ink text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">
                                        <x-icon name="scoreboard" :size="17" :stroke="1.9" />Enter score
                                    </button>
                                @else
                                    <div class="flex flex-col gap-2.5 px-1">
                                        <span class="min-h-11 text-sm text-ink2">
                                            @if ($next)
                                                Next up: <strong class="text-ink">{{ $play->names($next['a']['ids']) }}</strong> vs <strong class="text-ink">{{ $play->names($next['b']['ids']) }}</strong>
                                            @else
                                                Waiting for 4 players in the queue.
                                            @endif
                                        </span>
                                        <button type="button" wire:click="fillCourt({{ $court['n'] }})" @disabled(! $next)
                                                class="inline-flex h-11.5 cursor-pointer items-center justify-center gap-2 rounded-[14px] bg-lime text-[15px] font-extrabold text-on-lime transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-50">
                                            <x-icon name="arrow-right" :size="17" :stroke="2.2" />Fill Court {{ $court['n'] }} from queue
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if ($results->isNotEmpty())
                        <div class="flex flex-col rounded-3xl border border-line bg-surface p-4.5">
                            <div class="mb-2 flex items-baseline justify-between gap-2.5">
                                <h2 class="m-0 font-display text-xl font-extrabold tracking-[-0.03em]">Latest results</h2>
                                <span class="text-[13px] text-ink3">{{ $games }} games logged tonight</span>
                            </div>
                            @foreach ($results as $r)
                                <div wire:key="result-{{ $r['id'] }}" @class(['flex flex-col gap-1.5 border-t border-line py-3', 'animate-snap' => $r['id'] === $freshGame])>
                                    <span class="font-mono text-[11px] tracking-[.08em] text-ink3 uppercase">Court {{ $r['court'] }} · {{ $r['ago'] }}</span>
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                        <span @class(['flex min-w-0 items-center justify-end gap-1.5 text-[15px]', 'font-extrabold text-ink' => $r['aWon'], 'font-medium text-ink2' => ! $r['aWon']])>
                                            @if ($r['aWon'])<x-icon name="trophy" :size="14" :stroke="2.2" />@endif
                                            <span class="truncate">{{ $r['a'] }}</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-[10px] bg-surface2 px-2.5 py-1 font-mono text-base font-semibold whitespace-nowrap">
                                            <span @class(['text-ink' => $r['aWon'], 'text-ink2' => ! $r['aWon']])>{{ $r['sa'] }}</span><span class="text-ink3">–</span><span @class(['text-ink' => ! $r['aWon'], 'text-ink2' => $r['aWon']])>{{ $r['sb'] }}</span>
                                        </span>
                                        <span @class(['flex min-w-0 items-center gap-1.5 text-[15px]', 'font-extrabold text-ink' => ! $r['aWon'], 'font-medium text-ink2' => $r['aWon']])>
                                            <span class="truncate">{{ $r['b'] }}</span>
                                            @unless ($r['aWon'])<x-icon name="trophy" :size="14" :stroke="2.2" />@endunless
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Queue column --}}
                <div @class(['flex-col gap-3.5 rounded-3xl border border-line bg-surface p-4.5 shadow-card lg:sticky lg:top-5', 'flex' => $tab === 'queue', 'hidden lg:flex' => $tab !== 'queue'])>
                    <div class="flex items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5">
                            <h2 class="m-0 font-display text-[22px] font-extrabold tracking-[-0.03em]">Queue</h2>
                            <span class="inline-flex h-6.5 items-center rounded-full border border-line bg-surface2 px-2.5 font-mono text-xs">{{ $inQueue }} players</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-xs text-ink3"><x-icon name="rotate" :size="14" />{{ $play->rotationLabel() }}</span>
                    </div>

                    @if ($next)
                        <div class="flex flex-col gap-2.5 rounded-[18px] border-[1.5px] border-lime bg-lime-soft p-3.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-[11px] font-semibold tracking-[.12em] text-lime-text uppercase">Up next</span>
                                <span class="text-xs text-ink2">{{ $where }}</span>
                            </div>
                            @foreach (['a', 'b'] as $side)
                                @if ($side === 'b')
                                    <span class="font-mono text-[10px] tracking-[.12em] text-ink3">VS</span>
                                @endif
                                <div class="flex items-center gap-2.5">
                                    <div class="flex">
                                        @foreach ($next[$side]['ids'] as $id)
                                            @php($p = $play->participant($id))
                                            <x-avatar :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" class="-mr-2 size-8 border-2 border-surface text-[11px]" />
                                        @endforeach
                                    </div>
                                    <span class="ml-2 flex-1 text-sm font-bold">{{ $play->names($next[$side]['ids']) }}</span>
                                    @if ($next[$side]['locked'])
                                        <span class="inline-flex h-5.5 items-center gap-1 rounded-full bg-lime px-2 text-[11px] font-extrabold text-on-lime"><x-icon name="lock" :size="11" :stroke="2.6" />Locked</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @php($selNames = collect($selected)->map(fn ($id) => $play->participant(collect($queue)->firstWhere('id', $id)['ids'][0])?->firstName())->filter()->join(' & '))
                    @if (count($selected) === 0)
                        <p class="m-0 flex items-center gap-2 text-[13px] text-ink2"><x-icon name="lock" :size="15" :stroke="2.4" />Tap two players to lock them as partners.</p>
                    @elseif (count($selected) === 1)
                        <p class="m-0 flex animate-fade items-center gap-2 text-[13px] font-semibold text-ink"><x-icon name="lock" :size="15" :stroke="2.4" />Pick one more player to pair with {{ $selNames }}.</p>
                    @else
                        <div class="flex animate-snap items-center gap-1.5 rounded-full bg-ink py-1.5 pr-1.5 pl-3.5 text-bg">
                            <span class="min-w-0 flex-1 truncate text-[13px] font-bold">{{ $selNames }}</span>
                            <button type="button" wire:click="clearSelection" class="h-9.5 cursor-pointer px-2.5 text-[13px] font-semibold">Clear</button>
                            <button type="button" wire:click="lockPartners" class="inline-flex h-10 cursor-pointer items-center gap-1.5 rounded-full bg-lime px-3.5 text-[13px] font-extrabold whitespace-nowrap text-on-lime transition hover:brightness-96 active:scale-97">
                                <x-icon name="lock" :size="15" :stroke="2.4" />Lock as partners
                            </button>
                        </div>
                    @endif

                    <div class="flex flex-col gap-2">
                        @forelse ($queue as $i => $entry)
                            @php($pair = $entry['locked'] && count($entry['ids']) === 2)
                            @php($on = in_array($entry['id'], $selected))
                            @php($p1 = $play->participant($entry['ids'][0]))
                            @php($label = $pair ? $play->names($entry['ids']) : ($p1?->name ?? 'Guest'))
                            @php($up = $next && in_array($entry['id'], $next['used']))
                            <div wire:key="entry-{{ $entry['id'] }}" @class([
                                'flex items-center gap-2 rounded-2xl border-[1.5px] py-1 pr-1 pl-2.5 transition-colors',
                                'border-ink bg-surface2' => $on,
                                'border-lime bg-lime-soft' => ! $on && $pair,
                                'border-line bg-surface' => ! $on && ! $pair,
                            ])>
                                <span class="w-5 shrink-0 font-mono text-xs text-ink3">{{ $i + 1 }}</span>
                                @if ($pair)
                                    <div class="flex min-h-12.5 min-w-0 flex-1 items-center gap-2.5">
                                        <span class="flex size-5.5 shrink-0 items-center justify-center rounded-[7px] bg-lime text-on-lime"><x-icon name="lock" :size="13" :stroke="2.6" /></span>
                                        <div class="flex shrink-0">
                                            @foreach ($entry['ids'] as $k => $id)
                                                @php($p = $play->participant($id))
                                                <x-avatar :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" class="size-9 border-2 border-surface text-xs {{ $k ? '-ml-2.5' : '' }}" />
                                            @endforeach
                                        </div>
                                        <span class="flex min-w-0 flex-1 flex-col">
                                            <span class="truncate text-sm font-bold">{{ $label }}</span>
                                            <span class="text-xs font-semibold text-lime-text">Locked partners</span>
                                        </span>
                                    </div>
                                    @if ($up)
                                        <span class="inline-flex h-5.5 shrink-0 items-center rounded-full bg-lime px-2 text-[11px] font-extrabold text-on-lime">NEXT</span>
                                    @endif
                                    <button type="button" wire:click="unlockEntry({{ $entry['id'] }})"
                                            class="inline-flex h-9 shrink-0 cursor-pointer items-center gap-1 rounded-xl border-[1.5px] border-line2 px-2.5 text-xs font-bold text-ink transition hover:brightness-96 active:scale-97">
                                        <x-icon name="unlock" :size="13" :stroke="2.2" />Unlock
                                    </button>
                                @else
                                    @php($g = (int) ($gamesTonight[$entry["ids"][0]] ?? 0))
                                    <button type="button" wire:click="toggleSelect({{ $entry['id'] }})" aria-pressed="{{ $on ? 'true' : 'false' }}" aria-label="{{ ($on ? 'Deselect ' : 'Select ').$label }}"
                                            class="flex min-h-12.5 min-w-0 flex-1 cursor-pointer items-center gap-2.5 text-left text-ink">
                                        <span @class(['flex size-5.5 shrink-0 items-center justify-center rounded-[7px] border-2 text-bg transition-colors', 'border-ink bg-ink' => $on, 'border-line2' => ! $on])>
                                            @if ($on)<x-icon name="check" :size="13" :stroke="3.2" />@endif
                                        </span>
                                        <x-avatar :name="$label" :color="$p1?->color() ?? '#FFFFFF'" class="size-9 text-xs" />
                                        <span class="flex min-w-0 flex-1 flex-col">
                                            <span class="truncate text-sm font-bold">{{ $label }}</span>
                                            <span class="text-xs text-ink3">{{ $g }} {{ $g === 1 ? 'game' : 'games' }} tonight</span>
                                        </span>
                                    </button>
                                    @if ($up)
                                        <span class="inline-flex h-5.5 shrink-0 items-center rounded-full bg-lime px-2 text-[11px] font-extrabold text-on-lime">NEXT</span>
                                    @endif
                                    <button type="button" wire:click="removeEntry({{ $entry['id'] }})" aria-label="Remove {{ $label }} from the queue"
                                            class="flex size-9.5 shrink-0 cursor-pointer items-center justify-center rounded-xl text-ink3 transition hover:bg-surface2 hover:text-ink">
                                        <x-icon name="x" :size="16" />
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p class="m-0 rounded-2xl border-[1.5px] border-dashed border-line2 p-4 text-sm text-ink2">The queue is empty. Add walk-ins below or wait for games to finish.</p>
                        @endforelse
                    </div>

                    <form wire:submit="addWalkin" class="flex flex-col gap-2">
                        <div class="flex gap-2">
                            <label for="walkin" class="sr-only">Walk-in player name</label>
                            <input id="walkin" type="text" wire:model="walkin" maxlength="60" placeholder="Add a walk-in by name"
                                   class="h-11.5 min-w-0 flex-1 rounded-[14px] border-[1.5px] border-line bg-surface2 px-3.5 text-sm text-ink outline-none transition-colors placeholder:text-ink3 focus:border-ink">
                            <button type="submit" class="inline-flex h-11.5 cursor-pointer items-center gap-1.5 rounded-[14px] bg-ink px-4 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">
                                <x-icon name="user-plus" :size="16" :stroke="1.9" />Add
                            </button>
                        </div>
                        @error('walkin')
                            <p class="m-0 text-[13px] font-semibold text-hot">{{ $message }}</p>
                        @enderror
                    </form>
                </div>
            </div>
        @endif
    </x-page>

    {{-- Score entry: bottom sheet on mobile, centered modal on desktop. --}}
    @if ($play && $scoring)
        @php($to = $play->race_to)
        <div wire:key="score-{{ $scoring['n'] }}" class="fixed inset-0 z-65 flex items-end justify-center lg:items-center lg:p-6"
             x-data="{
                a: $wire.entangle('scoreA'), b: $wire.entangle('scoreB'), to: {{ $to }}, names: @js(['a' => $play->names($scoring['a']['ids']), 'b' => $play->names($scoring['b']['ids'])]),
                n(v) { return v === null || v === '' ? null : Number(v) },
                get win() { const a = this.n(this.a), b = this.n(this.b); return a === null || b === null || (a === this.to) === (b === this.to) ? null : (a === this.to ? 'a' : 'b') },
                get score() { return Math.max(this.a, this.b) + '–' + Math.min(this.a, this.b) },
                get status() {
                    const a = this.n(this.a), b = this.n(this.b);
                    if (a === null || b === null) return 'Enter a score for both teams.';
                    if (a === this.to && b === this.to) return 'Only one team can reach ' + this.to + ' in a race.';
                    if (this.win) return this.names[this.win] + ' win ' + this.score;
                    return 'Race to ' + this.to + ': one team needs ' + this.to + ' points to finish.';
                },
                clamp(v) { return Math.max(0, Math.min(this.to, v)) },
                step(k, d) { this[k] = this.clamp((this.n(this[k]) ?? 0) + d) },
                type(k, el) { const v = parseInt(el.value, 10); this[k] = el.value === '' || isNaN(v) ? null : this.clamp(v); el.value = this[k] ?? '' },
                reach(k) { const o = k === 'a' ? 'b' : 'a'; this[k] = this.to; if ((this.n(this[o]) ?? 0) >= this.to) this[o] = this.to - 1 },
             }"
             x-on:keydown.escape.window="$wire.closeScore()">
            <button type="button" wire:click="closeScore" aria-label="Close score entry" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

            <form wire:submit="saveScore" role="dialog" aria-modal="true" aria-labelledby="score-title"
                  class="relative flex max-h-full w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] lg:w-160 lg:rounded-[28px]">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex flex-col gap-0.5">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Court {{ $scoring['n'] }} · Race to {{ $to }} · <x-live-timer :start="now()->timestamp - $scoring['started_at']" /></span>
                        <h2 id="score-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">Enter the score</h2>
                    </div>
                    <button type="button" wire:click="closeScore" aria-label="Close" class="flex size-11 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-3.5 overflow-y-auto px-6 py-5">
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @foreach (['a', 'b'] as $side)
                            @php($K = strtoupper($side))
                            <div class="flex flex-col items-center gap-3 rounded-[22px] border-2 px-3.5 py-4 transition-colors"
                                 x-bind:class="win === '{{ $side }}' ? 'border-lime bg-lime-soft' : 'border-line bg-surface'">
                                <div class="flex flex-col items-center gap-2 text-center">
                                    <span class="font-mono text-[10px] tracking-[.14em] text-ink3">TEAM {{ $K }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="flex">
                                            @foreach ($scoring[$side]['ids'] as $id)
                                                @php($p = $play->participant($id))
                                                <x-avatar :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" class="-mr-2 size-8 border-2 border-surface text-[11px]" />
                                            @endforeach
                                        </div>
                                        <span class="ml-2 text-[15px] font-bold">{{ $play->names($scoring[$side]['ids']) }}</span>
                                        @if ($scoring[$side]['locked'])
                                            <x-icon name="lock" :size="13" :stroke="2.6" class="text-lime-text" />
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <button type="button" x-on:click="step('{{ $side }}', -1)" aria-label="Lower Team {{ $K }} score"
                                            class="flex size-12 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-surface text-ink transition hover:brightness-96 active:scale-97">
                                        <x-icon name="minus" :size="18" :stroke="2.4" />
                                    </button>
                                    <input type="number" min="0" max="{{ $to }}" inputmode="numeric" aria-label="Team {{ $K }} score"
                                           x-bind:value="{{ $side }} ?? ''" x-on:input="type('{{ $side }}', $el)"
                                           class="h-16 w-21 [appearance:textfield] rounded-2xl bg-transparent p-0 text-center font-display text-[52px] font-extrabold tracking-[-0.04em] text-ink tabular-nums outline-none lg:h-20 lg:w-24 lg:text-[64px] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                    <button type="button" x-on:click="step('{{ $side }}', 1)" aria-label="Raise Team {{ $K }} score"
                                            class="flex size-12 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-surface text-ink transition hover:brightness-96 active:scale-97">
                                        <x-icon name="plus" :size="18" :stroke="2.4" />
                                    </button>
                                </div>
                                <div class="flex min-h-10 items-center justify-center gap-2">
                                    <button type="button" x-show="win !== '{{ $side }}'" x-on:click="reach('{{ $side }}')"
                                            class="h-10 cursor-pointer rounded-full border-[1.5px] border-line2 px-3.5 text-[13px] font-bold text-ink transition hover:brightness-96 active:scale-97">They reached {{ $to }}</button>
                                    <span x-show="win === '{{ $side }}'" x-cloak class="inline-flex h-8 animate-snap items-center gap-1.5 rounded-full bg-lime px-3 text-xs font-extrabold tracking-[.04em] text-on-lime">
                                        <x-icon name="trophy" :size="14" :stroke="2.2" />WINNER
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div role="status" aria-live="polite" class="flex items-center gap-2.5 rounded-[14px] px-3.5 py-3 text-sm font-bold"
                         x-bind:class="win ? 'bg-lime-soft text-lime-text' : 'bg-surface2 text-ink2'">
                        <x-icon name="check" :size="18" :stroke="2.4" x-show="win" x-cloak />
                        <x-icon name="alert" :size="18" x-show="! win" />
                        <span x-text="status"></span>
                    </div>
                    <p class="m-0 text-[13px] text-ink3">Race format: the first team to {{ $to }} wins. Saving logs the game, updates everyone's stats, and sends the players back to the queue.</p>
                </div>

                <div class="flex shrink-0 gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
                    <button type="button" wire:click="closeScore" class="h-13 cursor-pointer rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Cancel</button>
                    <button type="submit" x-bind:disabled="! win" x-text="win ? 'Save ' + score + ' result' : 'Save score'"
                            class="h-13 flex-1 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-50">Save score</button>
                </div>
            </form>
        </div>
    @endif
</x-app-shell>
</div>
