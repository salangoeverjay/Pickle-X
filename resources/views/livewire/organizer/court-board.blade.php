<div wire:poll.15s x-data="{
    confirmEnd: false,
    share: false,
    copyLink(url) {
        try { navigator.clipboard.writeText(url).catch(() => {}) } catch (e) { /* clipboard can be blocked */ }
        this.$dispatch('toast', { message: 'View link copied' })
    },
}">
<x-app-shell>
    <x-page>
        @if (! $play)
            <div class="flex flex-col items-start gap-3.5 rounded-[28px] border-[1.5px] border-dashed border-line2 bg-surface p-8">
                <span class="flex size-14 items-center justify-center rounded-[18px] bg-surface2"><x-icon name="court" :size="26" :stroke="1.9" /></span>
                <h1 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[44px]">No live session</h1>
                <p class="m-0 text-ink2">{{ auth()->user()->openPlays()->exists() ? 'Start one from your open plays when players arrive, and the court board opens here.' : 'Your first live session opens here. Create an open play, then start it on the day when players arrive.' }}</p>
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
                    @unless ($autoEnd['soon'])
                        <span class="inline-flex items-center gap-1.5 text-[13px] text-ink2"><x-icon name="clock" :size="15" />{{ $autoEnd['note'] }}</span>
                    @endunless
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" role="switch" aria-checked="{{ $play->auto_fill ? 'true' : 'false' }}" wire:click="toggleAutoFill"
                            class="inline-flex h-11 cursor-pointer items-center gap-2.5 rounded-full border-[1.5px] border-line2 bg-surface pr-2.5 pl-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                        Auto-fill courts
                        <span @class(['relative h-6 w-10 rounded-full transition-colors', 'bg-lime' => $play->auto_fill, 'bg-line2' => ! $play->auto_fill])>
                            <span @class(['absolute top-0.75 size-4.5 rounded-full transition-[left]', 'left-4.75 bg-on-lime' => $play->auto_fill, 'left-0.75 bg-white' => ! $play->auto_fill])></span>
                        </span>
                    </button>
                    @if ($shareUrl)
                        <button type="button" x-on:click="share = true" aria-haspopup="dialog"
                                class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-line2 bg-surface px-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                            <x-icon name="qr" :size="16" :stroke="2" />Share view
                        </button>
                    @endif
                    <button type="button" x-on:click="confirmEnd = true" aria-haspopup="dialog"
                            class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-hot-line bg-hot-soft px-4 text-sm font-bold text-hot transition hover:brightness-96 active:scale-97">
                        <x-icon name="stop" :size="15" :stroke="2.2" />End session
                    </button>
                </div>
            </div>

            @if ($autoEnd['soon'])
                <div role="status" class="flex animate-fade flex-wrap items-center gap-x-3.5 gap-y-2.5 rounded-[18px] border border-hot-line bg-hot-soft px-4 py-3.5 text-hot">
                    <x-icon name="clock" :size="20" />
                    <span class="flex-[1_1_280px] text-sm leading-[1.45] font-semibold">{{ $autoEnd['warning'] }}</span>
                    <button type="button" x-on:click="confirmEnd = true" class="h-11 shrink-0 cursor-pointer rounded-full bg-hot px-4 text-sm font-extrabold text-bg transition hover:brightness-96 active:scale-97">End it now</button>
                </div>
            @endif

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-4">
                @foreach ([
                    ['On court', $busy * 4, 'border-line bg-surface text-ink'],
                    ['Ready in queue', "{$readyCount}/{$inQueue}", 'border-line bg-surface text-ink'],
                    ['Checked in', "{$here}/{$total}", $here < $total ? 'border-hot-line bg-hot-soft text-hot' : 'border-line bg-surface text-ink'],
                    ['Games logged', $games, 'border-ink bg-ink text-bg'],
                ] as [$label, $value, $tone])
                    <div class="flex flex-col gap-1 rounded-[18px] border px-4 py-3.5 {{ $tone }}">
                        <span class="font-mono text-[11px] tracking-widest uppercase">{{ $label }}</span>
                        <span class="font-display text-[30px] leading-[1.05] font-extrabold tracking-[-0.04em]">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Mobile column switch --}}
            <div role="tablist" aria-label="Court board view" class="grid grid-cols-3 rounded-full border border-line bg-surface p-1 lg:hidden">
                @foreach (['courts' => 'Courts · '.$busy.'/'.count($courts), 'queue' => 'Queue · '.$inQueue, 'stand' => 'Standings'] as $key => $label)
                    <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')"
                            @class(['h-11 cursor-pointer rounded-full text-sm font-bold transition-colors', 'bg-ink text-bg' => $tab === $key, 'text-ink2' => $tab !== $key])>{{ $label }}</button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
                {{-- Courts column: courts, results and standings (standings has its own tab on mobile) --}}
                <div @class(['min-w-0 flex-col gap-4', 'flex' => $tab !== 'queue', 'hidden lg:flex' => $tab === 'queue'])>
                    <div @class(['grid-cols-1 gap-3.5 lg:grid-cols-2', 'grid' => $tab !== 'stand', 'hidden lg:grid' => $tab === 'stand'])>
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

                                <x-live-court :play="$play" :court="$court" :badge="false" :locks="true" :index="$i" class="h-41 lg:aspect-[21/10] lg:h-auto" player-class="size-8.5 text-xs lg:size-9.5" />

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
                        <div @class(['flex-col rounded-3xl border border-line bg-surface p-4.5', 'flex' => $tab !== 'stand', 'hidden lg:flex' => $tab === 'stand'])>
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
                                            <span @class(['text-ink' => $r['aWon'], 'text-ink2' => ! $r['aWon']])>{{ $r['sa'] }}</span><span class="text-ink3">-</span><span @class(['text-ink' => ! $r['aWon'], 'text-ink2' => $r['aWon']])>{{ $r['sb'] }}</span>
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

                    <div @class(['hidden lg:block' => $tab === 'courts'])>
                        <x-standings :play="$play" :rows="$standings" :fresh="$podNew" heading="standings-board" class="rounded-3xl" />
                    </div>
                </div>

                {{-- Queue column --}}
                <div @class(['flex-col gap-3.5 rounded-3xl border border-line bg-surface p-4.5 shadow-card lg:sticky lg:top-5 lg:max-h-260 lg:overflow-y-auto lg:[scrollbar-width:thin] [&>*]:shrink-0', 'flex' => $tab === 'queue', 'hidden lg:flex' => $tab !== 'queue'])>
                    <div class="flex items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5">
                            <h2 class="m-0 font-display text-[22px] font-extrabold tracking-[-0.03em]">Queue</h2>
                            <span class="inline-flex h-6.5 items-center rounded-full border border-line bg-surface2 px-2.5 font-mono text-xs">{{ $inQueue }} players</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-xs text-ink3"><x-icon name="rotate" :size="14" />{{ $play->rotationLabel() }}</span>
                    </div>

                    @if ($group)
                        @php($sides = $next ? [$next['a']['eids'], $next['b']['eids']] : [$group['used']])
                        <div class="flex flex-col gap-2.5 rounded-[18px] border-[1.5px] border-lime bg-lime-soft p-3.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-[11px] font-semibold tracking-[.12em] text-lime-text uppercase">Up next</span>
                                <span class="text-right text-xs text-ink2">{{ $where }}</span>
                            </div>
                            @foreach ($sides as $side => $eids)
                                @if ($side === 1)
                                    <span class="font-mono text-[10px] tracking-[.12em] text-ink3">VS</span>
                                @endif
                                @foreach ($eids as $eid)
                                    @php($ne = collect($queue)->firstWhere('id', $eid))
                                    @php($neNames = $play->names($ne['ids']))
                                    <div wire:key="up-{{ $eid }}" class="flex items-center gap-2">
                                        @php($framed = collect($ne['ids'])->contains(fn ($id) => $play->participant($id)?->frameClass()))
                                        <div class="flex shrink-0">
                                            @foreach ($ne['ids'] as $k => $id)
                                                @php($p = $play->participant($id))
                                                <x-avatar :src="$p?->avatarUrl()" :frame="$p?->frameClass()" :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" @class(['size-8 border-2 border-surface text-[11px]', '-mr-2' => ! $framed, 'mr-1.5' => $framed && $k < count($ne['ids']) - 1]) />
                                            @endforeach
                                        </div>
                                        <div class="ml-2 flex min-w-0 flex-1 flex-col gap-px">
                                            <span class="truncate text-sm font-bold">{{ $neNames }}</span>
                                            @if ($ne['locked'] && count($ne['ids']) === 2)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-extrabold text-lime-text"><x-icon name="lock" :size="11" :stroke="2.6" />Locked pair</span>
                                            @endif
                                        </div>
                                        <button type="button" wire:click="$set('subFor', {{ $eid }})" aria-label="Sub out {{ $neNames }}"
                                                class="inline-flex h-8.5 shrink-0 cursor-pointer items-center gap-1 rounded-[10px] border-[1.5px] border-line2 bg-surface px-2.5 text-xs font-bold text-ink transition hover:brightness-96 active:scale-97">
                                            <x-icon name="swap" :size="13" :stroke="2.2" />Sub out
                                        </button>
                                    </div>
                                @endforeach
                            @endforeach
                            @if (! $next)
                                <span class="flex items-center gap-2 text-[13px] font-bold text-ink"><x-icon name="clock" :size="15" />Needs {{ $group['need'] }} more checked-in {{ Str::plural('player', $group['need']) }} to start a game</span>
                            @elseif (! str_contains($where, 'is open'))
                                <span class="text-xs text-ink2">They go on as soon as a court finishes.</span>
                            @endif
                            @if ($skipped[0])
                                <span class="text-xs text-ink3">Skipping {{ $skipped[0] }} {{ $skipped[1] ? 'until they are back.' : 'until they check in.' }}</span>
                            @endif

                            @if ($subOut)
                                @php($outNames = $play->names($subOut['ids']))
                                @php($outFirst = $play->participant($subOut['ids'][0])?->firstName() ?? 'Guest')
                                @php($outPair = $subOut['locked'] && count($subOut['ids']) === 2)
                                <div class="mt-1 flex animate-snap flex-col gap-2.5 rounded-[14px] border-[1.5px] border-ink bg-surface p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-extrabold">Who subs in for {{ $outNames }}?</span>
                                        <button type="button" wire:click="$set('subFor', null)" aria-label="Cancel substitution"
                                                class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-[10px] text-ink2 transition hover:bg-surface2 hover:text-ink">
                                            <x-icon name="x" :size="16" />
                                        </button>
                                    </div>
                                    <span class="text-xs leading-[1.45] text-ink2">{{ $outPair ? "{$outNames} are a locked pair, so they step out together and keep their spot in line." : "{$outFirst} stays in the queue and keeps their spot for when they're ready." }}</span>
                                    @if ($subCands->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($subCands as $k => $cd)
                                                @php($pids = collect($cd['es'])->flatMap(fn ($e) => $e['ids'])->all())
                                                @php($framed = collect($pids)->contains(fn ($id) => $play->participant($id)?->frameClass()))
                                                <button type="button" wire:key="cand-{{ collect($cd['es'])->pluck('id')->join('-') }}" wire:click="subIn({{ $subOut['id'] }}, @js(collect($cd['es'])->pluck('id')->all()))"
                                                        class="inline-flex h-10 max-w-full cursor-pointer items-center gap-1.5 rounded-full border-[1.5px] border-line2 bg-surface2 pr-3 pl-1 text-[13px] font-bold whitespace-nowrap text-ink transition hover:brightness-96 active:scale-97">
                                                    <span class="flex">
                                                        @foreach ($pids as $j => $id)
                                                            @php($cp = $play->participant($id))
                                                            <x-avatar :src="$cp?->avatarUrl()" :frame="$cp?->frameClass()" :name="$cp?->name ?? '?'" :color="$cp?->color() ?? '#FFFFFF'" @class(['size-7.5 border-2 border-surface2 text-[11px]', '-ml-2' => $j && ! $framed, 'ml-1.5' => $j && $framed]) />
                                                        @endforeach
                                                    </span>
                                                    <span class="ml-1 truncate">{{ $play->names($pids) }}</span>
                                                    <span class="font-mono text-[11px] font-medium text-ink3">{{ collect($cd['es'])->map(fn ($e) => '#'.$e['pos'])->join(' ') }}</span>
                                                    @if ($cd['tag'])
                                                        <span class="inline-flex h-5 items-center rounded-full bg-lime px-1.75 text-[10px] font-extrabold text-on-lime">{{ $cd['tag'] }}</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-[13px] font-semibold text-hot">No checked-in players are free to sub in right now.</span>
                                    @endif
                                    @if ($outPair)
                                        <div class="flex flex-wrap items-center gap-1.5 border-t border-line pt-2.5">
                                            <span class="text-xs font-semibold text-ink2">Only one of them leaving?</span>
                                            @foreach ($subOut['ids'] as $id)
                                                @php($of = $play->participant($id)?->firstName() ?? 'Guest')
                                                <button type="button" wire:click="subOne({{ $subOut['id'] }}, {{ $id }})" aria-label="Unlock the pair and sub out only {{ $of }}"
                                                        class="inline-flex h-8.5 cursor-pointer items-center gap-1.25 rounded-full border-[1.5px] border-line2 px-2.75 text-xs font-bold text-ink transition hover:brightness-96 active:scale-97">
                                                    <x-icon name="unlock" :size="13" :stroke="2.2" /><span>Only {{ $of }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                    <button type="button" wire:click="setPaused({{ $subOut['id'] }}, true)"
                                            class="h-9 cursor-pointer self-start px-0.5 text-[13px] font-semibold text-ink2 underline underline-offset-3">Just mark {{ $outPair ? $outNames : $outFirst }} as stepped out</button>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div role="group" aria-label="Add players" class="flex flex-col gap-2.5 border-b border-line pb-4">
                        <span class="font-mono text-[11px] font-semibold tracking-[.12em] text-ink3 uppercase">Add players</span>
                        <form wire:submit="addWalkin" class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <label for="walkin" class="sr-only">Walk-in player name or email</label>
                                <input id="walkin" type="text" wire:model.live.debounce.300ms="walkin" maxlength="60" autocomplete="off" placeholder="Add a walk-in by name or email" aria-describedby="walkin-hint"
                                       class="h-11.5 min-w-0 flex-1 rounded-[14px] border-[1.5px] border-line bg-surface2 px-3.5 text-sm text-ink outline-none transition-colors placeholder:text-ink3 focus:border-ink">
                                <button type="submit" class="inline-flex h-11.5 cursor-pointer items-center gap-1.5 rounded-[14px] bg-ink px-4 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">
                                    <x-icon name="user-plus" :size="16" :stroke="1.9" />Add
                                </button>
                            </div>
                            <span id="walkin-hint" class="text-xs leading-[1.45] text-ink3">Type a name to look for their PickleX account. Nothing is linked until you confirm it's them.</span>
                            @error('walkin')
                                <p class="m-0 text-[13px] font-semibold text-hot">{{ $message }}</p>
                            @enderror

                            @if ($walkinUser)
                                @php($wf = $walkinUser->firstName())
                                @php($wg = \App\Models\GamePlayer::where('user_id', $walkinUser->id)->count())
                                <div role="group" aria-label="Confirm the account" class="flex animate-snap flex-col gap-3 rounded-2xl border-[1.5px] border-ink bg-surface p-3.5">
                                    <span class="text-[13px] font-extrabold">Add this account to the queue?</span>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :name="$walkinUser->name" :color="$walkinUser->color()" :src="$walkinUser->avatarUrl()" :frame="$walkinUser->frameClass()" class="size-11.5 text-[15px]" />
                                        <span class="flex min-w-0 flex-1 flex-col gap-px">
                                            <span class="text-base font-extrabold">{{ $walkinUser->name }}</span>
                                            <span class="text-xs wrap-anywhere text-ink2">{{ $walkinUser->email }}</span>
                                            <span class="text-xs text-ink3">{{ $walkinUser->club ? $walkinUser->club.' · ' : '' }}{{ $wg }} {{ Str::plural('game', $wg) }} on PickleX</span>
                                        </span>
                                    </div>
                                    <span class="text-xs leading-[1.45] text-ink2">Check with them first. Their games tonight count toward this account's stats and rankings, and they're checked in right away.</span>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="$set('walkinPick', null)" class="h-10.5 cursor-pointer rounded-full border-[1.5px] border-line2 px-3.5 text-[13px] font-bold text-ink transition hover:brightness-96 active:scale-97">Not them</button>
                                        <button type="button" wire:click="addAccountWalkin" class="h-10.5 flex-1 cursor-pointer rounded-full bg-lime px-3.5 text-[13px] font-extrabold text-on-lime transition hover:brightness-96 active:scale-97">Yes, add {{ $wf }}</button>
                                    </div>
                                </div>
                            @elseif ($walkinMatches->isNotEmpty())
                                @php($wq = trim($walkin))
                                <div role="region" aria-label="Matching PickleX accounts" class="flex animate-fade flex-col gap-2 rounded-2xl border border-line bg-surface2 p-3">
                                    <span class="text-[13px] font-extrabold">{{ $walkinMatches->count() === 1 ? '1 PickleX account matches' : $walkinMatches->count().' PickleX accounts match' }} “{{ $wq }}”</span>
                                    @if ($walkinTried)
                                        <span role="alert" class="text-xs font-bold text-hot">Pick the account that's them, or add them as a guest.</span>
                                    @endif
                                    @foreach ($walkinMatches as $m)
                                        @php($mu = $m['user'])
                                        <div wire:key="wm-{{ $mu->id }}" class="flex items-center gap-2.5 rounded-[14px] border border-line bg-surface py-2 pr-2 pl-2.5">
                                            <x-avatar :name="$mu->name" :color="$mu->color()" :src="$mu->avatarUrl()" :frame="$mu->frameClass()" class="size-9 text-xs" />
                                            <span class="flex min-w-0 flex-1 flex-col gap-px">
                                                <span class="truncate text-sm font-bold">{{ $mu->name }}</span>
                                                <span class="text-[11px] font-bold text-ink2">{{ $m['why'] }}</span>
                                                <span class="truncate text-xs text-ink3">{{ $mu->email }}{{ $mu->club ? ' · '.$mu->club : '' }}</span>
                                                @if ($m['spot'])
                                                    <span class="text-xs font-bold text-hot">{{ $m['spot'] }}</span>
                                                @endif
                                            </span>
                                            @unless ($m['spot'])
                                                <button type="button" wire:click="pickWalkin({{ $mu->id }})" aria-label="Add {{ $mu->name }}, {{ $mu->email }}"
                                                        class="h-9 shrink-0 cursor-pointer rounded-[10px] border-[1.5px] border-ink bg-transparent px-2.5 text-[13px] font-bold whitespace-nowrap text-ink transition hover:brightness-96 active:scale-97">This is them</button>
                                            @endunless
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addGuestWalkin" class="flex min-h-10.5 cursor-pointer items-center justify-center gap-2 rounded-xl border-[1.5px] border-dashed border-line2 bg-transparent px-3 py-1.5 text-center text-[13px] font-bold text-ink transition hover:brightness-96 active:scale-97">
                                        <x-icon name="plus" :size="15" /><span>Not listed? Add “{{ $wq }}” as a guest</span>
                                    </button>
                                </div>
                            @endif
                        </form>
                        @if ($canAddMe)
                            <button type="button" wire:click="addMe"
                                    class="flex h-11.5 cursor-pointer items-center justify-center gap-2 rounded-[14px] border-[1.5px] border-dashed border-line2 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                                <x-icon name="user-plus" :size="17" :stroke="1.9" />Add me to the queue
                            </button>
                        @endif
                    </div>

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

                    @if ($qt['big'])
                        <div role="search" aria-label="Find in the queue" class="flex flex-col gap-2.5">
                            <div class="relative">
                                <x-icon name="search" :size="17" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink3" />
                                <input type="search" wire:model.live.debounce.250ms="qq" placeholder="Find a player in the queue" aria-label="Find a player in the queue" autocomplete="off"
                                       class="h-10.5 w-full rounded-xl border-[1.5px] border-line bg-surface2 pr-10 pl-9.5 text-sm text-ink outline-none focus:border-ink [&::-webkit-search-cancel-button]:hidden">
                                @if (trim($qq) !== '')
                                    <button type="button" wire:click="$set('qq', '')" aria-label="Clear search" class="absolute top-1/2 right-1 flex size-8.5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-ink2"><x-icon name="x" :size="16" /></button>
                                @endif
                            </div>
                            <div role="tablist" aria-label="Show" class="flex flex-wrap gap-1.5">
                                @foreach ($qt['tabs'] as $f => [$label, $n])
                                    @php($on = $qt['f'] === $f)
                                    <button type="button" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}" wire:click="$set('qf', '{{ $f }}')"
                                            @class(['inline-flex h-8.5 shrink-0 cursor-pointer items-center gap-1.5 rounded-full border-[1.5px] border-line px-3 text-[13px] font-bold whitespace-nowrap transition-colors duration-200', 'bg-ink text-bg' => $on, 'text-ink2' => ! $on])>
                                        <span>{{ $label }}</span><span class="font-mono text-xs opacity-75">{{ $n }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-col gap-2">
                        @forelse ($queueRows as $entry)
                            @php($pair = $entry['locked'] && count($entry['ids']) === 2)
                            @php($on = in_array($entry['id'], $selected))
                            @php($p1 = $play->participant($entry['ids'][0]))
                            @php($label = $pair ? $play->names($entry['ids']) : ($p1?->name ?? 'Guest'))
                            @php($up = $group && in_array($entry['id'], $group['used']))
                            @php(['notHere' => $notHere, 'paused' => $paused, 'avail' => $avail] = $entry)
                            @php($framed = collect($entry['ids'])->contains(fn ($id) => $play->participant($id)?->frameClass()))
                            @php($rowBg = collect($entry['ids'])->map(fn ($id) => $play->participant($id)?->queueBgClass())->first(fn ($c) => $c))
                            @php($g = (int) ($gamesTonight[$entry['ids'][0]] ?? 0))
                            @php($gamesTxt = $g.($g === 1 ? ' game' : ' games').' tonight')
                            @php($meta = $notHere ? 'Not checked in yet' : ($paused ? 'Stepped out · keeps spot' : ($pair ? 'Locked pair · '.$gamesTxt : $gamesTxt.($p1?->paid ? ' · Paid' : ''))))
                            @php($metaClass = $avail ? ($pair ? 'font-bold text-lime-text' : 'font-medium text-ink3') : 'font-bold text-hot')
                            <div wire:key="entry-{{ $entry['id'] }}" @class([
                                'flex items-center gap-2 rounded-2xl border-[1.5px] py-1 pr-1 pl-2.5 transition-colors',
                                (string) $rowBg => (bool) $rowBg,
                                'border-ink bg-surface2' => $on,
                                'border-dashed border-line2 bg-transparent' => ! $on && ! $avail,
                                'border-lime bg-lime-soft' => ! $on && $avail && $pair,
                                'border-line bg-surface' => ! $on && $avail && ! $pair,
                            ])>
                                <span class="w-5 shrink-0 font-mono text-xs text-ink3">{{ $entry['pos'] }}</span>
                                @if ($pair)
                                    <div class="flex min-h-12.5 min-w-0 flex-1 items-center gap-2.5">
                                        <div class="relative flex shrink-0">
                                            @foreach ($entry['ids'] as $k => $id)
                                                @php($p = $play->participant($id))
                                                <x-avatar :src="$p?->avatarUrl()" :frame="$p?->frameClass()" :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" class="size-9 border-2 border-surface text-xs {{ $k ? ($framed ? 'ml-2' : '-ml-2.5') : '' }}" />
                                            @endforeach
                                            <span aria-hidden="true" class="absolute -bottom-1 z-3 {{ $framed ? 'left-7.75' : 'left-5.5' }} flex size-4.5 items-center justify-center rounded-full border-2 border-surface bg-lime text-on-lime"><x-icon name="lock" :size="9" :stroke="2.8" /></span>
                                        </div>
                                        <span @class(['flex min-w-0 flex-1 flex-col', 'opacity-78' => ! $avail])>
                                            <span class="truncate text-sm font-bold">{{ $label }}</span>
                                            <span class="flex min-w-0 items-center gap-1.5">@if ($up && $avail)<span class="inline-flex h-4.25 shrink-0 items-center rounded-full bg-lime px-1.5 text-[9.5px] font-extrabold tracking-[.04em] text-on-lime">NEXT</span>@endif<span class="truncate text-xs {{ $metaClass }}">{{ $meta }}</span></span>
                                        </span>
                                    </div>
                                @else
                                    <button type="button" wire:click="toggleSelect({{ $entry['id'] }})" aria-pressed="{{ $on ? 'true' : 'false' }}" aria-label="{{ ($on ? 'Deselect ' : 'Select ').$label }}"
                                            class="flex min-h-12.5 min-w-0 flex-1 cursor-pointer items-center gap-2.5 text-left text-ink">
                                        <span @class(['flex size-5.5 shrink-0 items-center justify-center rounded-[7px] border-2 text-bg transition-colors', 'border-ink bg-ink' => $on, 'border-line2' => ! $on])>
                                            @if ($on)<x-icon name="check" :size="13" :stroke="3.2" />@endif
                                        </span>
                                        <x-avatar :name="$label" :color="$p1?->color() ?? '#FFFFFF'" :src="$p1?->avatarUrl()" :frame="$p1?->frameClass()" class="size-9 text-xs" />
                                        <span @class(['flex min-w-0 flex-1 flex-col', 'opacity-78' => ! $avail])>
                                            <span class="truncate text-sm font-bold">{{ $label }}</span>
                                            <span class="flex min-w-0 items-center gap-1.5">@if ($up && $avail)<span class="inline-flex h-4.25 shrink-0 items-center rounded-full bg-lime px-1.5 text-[9.5px] font-extrabold tracking-[.04em] text-on-lime">NEXT</span>@endif<span class="truncate text-xs {{ $metaClass }}">{{ $meta }}</span></span>
                                        </span>
                                    </button>
                                @endif
                                @if ($notHere)
                                    <button type="button" wire:click="checkInEntry({{ $entry['id'] }})"
                                            class="h-9 shrink-0 cursor-pointer rounded-xl bg-ink px-2.5 text-xs font-bold text-bg transition hover:brightness-96 active:scale-97">Check in</button>
                                @elseif ($paused)
                                    <button type="button" wire:click="setPaused({{ $entry['id'] }}, false)"
                                            class="h-9 shrink-0 cursor-pointer rounded-xl bg-ink px-2.5 text-xs font-bold text-bg transition hover:brightness-96 active:scale-97">Back in</button>
                                @endif
                                @if ($pair)
                                    <button type="button" wire:click="unlockEntry({{ $entry['id'] }})" aria-label="Unlock partners" title="Unlock partners"
                                            class="flex size-9.5 shrink-0 cursor-pointer items-center justify-center rounded-xl border-[1.5px] border-line2 text-ink transition hover:brightness-96 active:scale-97">
                                        <x-icon name="unlock" :size="16" :stroke="2.2" />
                                    </button>
                                @else
                                    <button type="button" wire:click="removeEntry({{ $entry['id'] }})" aria-label="Remove {{ $label }} from the queue"
                                            class="flex size-9.5 shrink-0 cursor-pointer items-center justify-center rounded-xl text-ink3 transition hover:bg-surface2 hover:text-ink">
                                        <x-icon name="x" :size="16" />
                                    </button>
                                @endif
                            </div>
                        @empty
                            @if ($queue)
                                <p role="status" class="m-0 rounded-2xl border-[1.5px] border-dashed border-line2 px-4 py-3.5 text-sm text-ink2">
                                    {{ trim($qq) !== '' ? 'No one in the queue matches “'.trim($qq).'”.' : match ($qt['f']) { 'away' => 'Everyone in the queue is checked in.', 'out' => 'No one has stepped out.', default => 'No one is ready right now.' } }}
                                </p>
                            @else
                                <p class="m-0 rounded-2xl border-[1.5px] border-dashed border-line2 p-4 text-sm text-ink2">The queue is empty. Add walk-ins above or wait for games to finish.</p>
                            @endif
                        @endforelse
                        @if ($qt['rest'])
                            <div class="flex flex-col items-center gap-2 pt-1">
                                <span class="font-mono text-xs tracking-[.06em] text-ink3">Showing {{ $qt['shown'] }} of {{ $qt['hits'] }} {{ Str::plural('spot', $qt['hits']) }}</span>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="showMore" class="h-10 cursor-pointer rounded-full border-[1.5px] border-line2 bg-surface px-4 text-[13px] font-bold text-ink transition hover:brightness-96 active:scale-97">Show {{ min($qt['rest'], App\Livewire\Organizer\CourtBoard::QUEUE_PAGE) }} more</button>
                                    <button type="button" wire:click="showMore({{ $qt['hits'] }})" class="h-10 cursor-pointer rounded-full px-3.5 text-[13px] font-bold text-ink2 underline underline-offset-3">Show all {{ $qt['hits'] }}</button>
                                </div>
                            </div>
                        @endif
                    </div>

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
                get score() { return Math.max(this.a, this.b) + '-' + Math.min(this.a, this.b) },
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
                                                <x-avatar :src="$p?->avatarUrl()" :frame="$p?->frameClass()" :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" class="-mr-2 size-8 border-2 border-surface text-[11px]" />
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
    @if ($play)
        {{-- Share view mode: bottom sheet on mobile, centered modal on desktop. --}}
        @if ($shareUrl)
            <div x-show="share" x-cloak x-on:keydown.escape.window="share = false" class="fixed inset-0 z-66 flex items-end justify-center lg:items-center lg:p-6">
                <button type="button" x-on:click="share = false" aria-label="Close share" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

                <div role="dialog" aria-modal="true" aria-labelledby="share-title" x-trap.noscroll="share"
                     class="relative flex max-h-full w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] lg:w-180 lg:rounded-[28px]">
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span class="truncate font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $play->title }}</span>
                            <h2 id="share-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em] whitespace-nowrap">Share view mode</h2>
                        </div>
                        <a href="{{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Preview view mode"
                           class="inline-flex h-11 min-w-11 shrink-0 items-center justify-center gap-2 rounded-full border border-line bg-surface px-3.5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                            <x-icon name="eye" :size="18" :stroke="2" /><span class="hidden lg:inline">Preview</span>
                        </a>
                        <button type="button" x-on:click="share = false" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                            <x-icon name="x" :size="18" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-7 pt-7 pb-7.5">
                        <div class="flex flex-col items-center gap-8 lg:flex-row">
                            <div class="flex shrink-0 flex-col items-center gap-2 rounded-3xl border border-[#DEDCD0] bg-white px-3.5 pt-3.5 pb-3 shadow-[0_16px_36px_-18px_rgba(0,0,0,.35)]">
                                <div role="img" aria-label="QR code that opens {{ preg_replace('#^https?://#', '', $shareUrl) }}" class="size-55 fill-[#12170F] lg:size-60 [&>svg]:size-full">{!! $qr !!}</div>
                                <span class="font-mono text-xs font-semibold tracking-[.14em] text-[#12170F]">{{ $play->share_code }}</span>
                            </div>
                            {{-- Poster PNG for Download / Send (resources/js/share-poster.js). --}}
                            <div x-data="sharePoster(@js($poster))" x-effect="share && prep()" wire:ignore class="flex w-full min-w-0 flex-1 flex-col gap-5.5">
                                <p class="m-0 text-base leading-[1.55] text-ink2">Members scan this to follow the courts, who's up next and the queue live. No login needed.</p>
                                <div class="flex flex-col gap-2.5">
                                    <a x-show="img" x-cloak x-bind:href="img" download="{{ $poster['file'] }}" x-on:click="saved()"
                                       class="flex h-13 items-center justify-center gap-2 rounded-full border-2 border-cta-border bg-lime px-4.5 text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">
                                        <x-icon name="download" :size="18" :stroke="2.2" />Download QR image
                                    </a>
                                    <span x-show="!img" role="status" class="flex h-13 items-center justify-center rounded-full border-2 border-dashed border-line2 px-4.5 text-center text-sm font-bold text-ink3"
                                          x-text="failed ? 'The QR image could not be made here. Share the link instead.' : 'Preparing the QR image…'"></span>
                                    <button type="button" x-show="canSend" x-cloak x-on:click="send()"
                                            class="flex h-12 cursor-pointer items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 bg-transparent px-4.5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">
                                        <x-icon name="share" :size="18" />Send to members
                                    </button>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="share-url" class="text-xs font-bold text-ink3">Or share the link</label>
                                    <div class="flex gap-2">
                                        <input id="share-url" type="text" readonly value="{{ $shareUrl }}" x-on:focus="$el.select()"
                                               class="h-11 min-w-0 flex-1 rounded-xl border-[1.5px] border-line bg-surface2 px-3.5 font-mono text-[13px] text-ink outline-none">
                                        <button type="button" x-on:click="copyLink(@js($shareUrl))"
                                                class="h-11 shrink-0 cursor-pointer rounded-xl border-[1.5px] border-line2 bg-transparent px-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">Copy</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- End session confirm (same sheet/modal pattern). --}}
        <div x-show="confirmEnd" x-cloak x-on:keydown.escape.window="confirmEnd = false" class="fixed inset-0 z-66 flex items-end justify-center lg:items-center lg:p-6">
            <button type="button" x-on:click="confirmEnd = false" aria-label="Keep playing" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

            <div role="alertdialog" aria-modal="true" aria-labelledby="end-title" aria-describedby="end-desc" x-trap.noscroll="confirmEnd"
                 class="relative flex max-h-full w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] lg:w-130 lg:rounded-[28px]">
                <div class="flex min-h-0 flex-col gap-4 overflow-y-auto px-6 pt-6.5 pb-4.5">
                    <span class="flex size-13 items-center justify-center rounded-2xl border border-hot-line bg-hot-soft text-hot"><x-icon name="stop" :size="22" :stroke="2.2" /></span>
                    <div class="flex flex-col gap-1.5">
                        <h2 id="end-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">End {{ $play->title }}?</h2>
                        <p id="end-desc" class="m-0 text-[15px] leading-normal text-ink2">This closes the court board and the players' live view. Every logged game and stat stays saved.</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['Games' => $games, 'Checked in' => $here, 'Running' => $running] as $label => $value)
                            <div class="rounded-2xl border border-line bg-surface p-3">
                                <div class="font-mono text-[10px] tracking-widest text-ink3 uppercase">{{ $label }}</div>
                                <div class="font-display text-[26px] font-extrabold tracking-[-0.03em]">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if ($busy)
                        <div role="alert" class="flex items-start gap-2.5 rounded-[14px] border border-hot-line bg-hot-soft px-3.5 py-3 text-sm leading-[1.45] font-semibold text-hot">
                            <x-icon name="alert" :size="18" class="mt-px shrink-0" />
                            {{ $busy }} {{ $busy === 1 ? 'court is' : 'courts are' }} still mid-game. Those games won't count unless you enter their scores first.
                        </div>
                    @endif
                </div>
                <div class="flex shrink-0 gap-2.5 border-t border-line px-6 pt-3.5 pb-5.5">
                    <button type="button" x-on:click="confirmEnd = false" class="h-13 flex-1 cursor-pointer rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Keep playing</button>
                    <button type="button" wire:click="endSession" wire:loading.attr="disabled" class="h-13 flex-1 cursor-pointer rounded-full bg-hot text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97 disabled:opacity-50">End session</button>
                </div>
            </div>
        </div>
    @endif
</x-app-shell>
</div>
