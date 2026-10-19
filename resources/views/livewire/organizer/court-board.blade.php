<div wire:poll.15s x-data="{
    confirmEnd: false,
    share: false,
    rotm: false,
    wum: false,
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
                <h1 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] md:text-[38px] xl:text-[44px]">No live session</h1>
                <p class="m-0 text-ink2">{{ ! auth()->user()->isOrganizer() || session('mode') === 'member' ? 'The court board opens here when an open play you co-host is live.' : (auth()->user()->openPlays()->exists() ? 'Start an open play when players arrive and the court board opens here.' : 'Create an open play, then start it on the day.') }}</p>
                <a href="{{ route(auth()->user()->mode() === 'organizer' ? 'organizer.plays' : 'member.plays') }}" wire:navigate class="inline-flex h-12 items-center rounded-full bg-ink px-5 font-bold text-bg transition hover:brightness-96 active:scale-97">Go to open plays</a>
            </div>
        @else
            {{-- Header --}}
            <div class="flex flex-wrap items-end justify-between gap-3.5">
                <div class="flex flex-col gap-2">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="inline-flex h-7 items-center gap-1.75 rounded-full bg-ink px-2.75 font-mono text-[11px] tracking-widest text-bg"><span class="size-1.75 animate-live rounded-full bg-lime"></span>LIVE</span>
                        <span class="text-[13px] text-ink3">{{ $started }}, {{ $play->rotationLabel() }}, Race to {{ $play->race_to }}, {{ $play->warmupLabel() }}</span>
                    </div>
                    <h1 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] md:text-[38px] xl:text-[44px]">{{ $play->title }}</h1>
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
                    <button type="button" wire:click="openCourts" aria-haspopup="dialog" aria-label="Courts: {{ count($courts) }} {{ Str::plural('court', count($courts)) }} tonight. Add or remove a court"
                            class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-line2 bg-surface pr-2 pl-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                        <x-icon name="court" :size="16" :stroke="1.9" />Courts<span class="inline-flex h-6.5 min-w-6.5 items-center justify-center rounded-full bg-surface2 px-1.5 font-mono text-xs">{{ count($courts) }}</span>
                    </button>
                    <button type="button" id="wum-btn" x-on:click="wum = ! wum" x-bind:aria-expanded="wum" aria-controls="wum" aria-label="Warmup: {{ $play->warmupLabel() }}. Change it"
                            class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-line2 bg-surface pr-3 pl-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2h4M12 14l3-3M12 22a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" /></svg>Warmup<span class="inline-flex h-6.5 min-w-6.5 items-center justify-center rounded-full border border-line bg-surface2 px-2 font-mono text-sm font-semibold">{{ $play->warmup ? $play->warmup.' min' : 'Off' }}</span><x-icon name="chevron-down" :size="12" :stroke="2.4" class="transition-transform" x-bind:class="wum && 'rotate-180'" />
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

            <div id="wum" x-show="wum" x-cloak x-on:click.outside="if (! $event.target.closest('#wum-btn')) wum = false" role="radiogroup" aria-label="Warmup before each game"
                 class="flex animate-fade flex-wrap items-center gap-x-3.5 gap-y-2.5 rounded-[18px] border border-line bg-surface2 px-4 py-3.5">
                <div class="flex min-w-0 flex-[1_1_280px] flex-col gap-1">
                    <span class="font-mono text-[11px] font-semibold tracking-[.12em] text-ink3 uppercase">Warmup: changes from the next game</span>
                    <span class="text-[13px] leading-[1.45] text-ink2">{{ $play->warmup ? "Each new foursome gets {$play->warmup} minutes to warm up before the game clock starts. " : 'Games start the moment a foursome goes on court. ' }}Winners who stay on skip it. Changes apply to the next game.</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (App\Models\OpenPlay::WARMUPS as $m => $label)
                        @php($on = $play->warmup === $m)
                        <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" wire:click="setWarmup({{ $m }})" x-on:click="wum = false"
                                @class(['h-10 min-w-16 cursor-pointer rounded-full border-[1.5px] px-3.5 text-sm font-bold transition hover:brightness-96 active:scale-97', 'border-ink bg-ink text-bg' => $on, 'border-line2 bg-surface text-ink' => ! $on])>{{ $label }}</button>
                    @endforeach
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
            <div role="tablist" aria-label="Court board view" class="grid grid-cols-3 rounded-full border border-line bg-surface p-1 md:hidden">
                @foreach (['courts' => 'Courts '.$busy.'/'.count($courts), 'queue' => 'Queue '.$inQueue, 'stand' => 'Standings'] as $key => $label)
                    <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')"
                            @class(['h-11 cursor-pointer rounded-full text-sm font-bold transition-colors', 'bg-ink text-bg' => $tab === $key, 'text-ink2' => $tab !== $key])>{{ $label }}</button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_350px] xl:grid-cols-[minmax(0,1fr)_380px]">
                {{-- Courts column: courts, results and standings (standings has its own tab on mobile) --}}
                {{-- Courts go two across only when their column has room for it (a container query, not the screen width). --}}
                <div @class(['@container min-w-0 flex-col gap-4', 'flex' => $tab !== 'queue', 'hidden md:flex' => $tab === 'queue'])>
                    <div @class(['grid-cols-1 gap-3.5 @min-[600px]:grid-cols-2', 'grid' => $tab !== 'stand', 'hidden md:grid' => $tab === 'stand'])>
                        @foreach ($courts as $i => $court)
                            <div wire:key="court-{{ $court['n'] }}" class="flex flex-col gap-3 rounded-3xl border border-line bg-surface p-3.5 shadow-card">
                                <div class="flex flex-wrap items-center justify-between gap-x-2.5 gap-y-1 px-1 pt-0.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="font-display text-xl font-extrabold tracking-[-0.03em] whitespace-nowrap">Court {{ $court['n'] }}</span>
                                        @php($warming = App\Models\OpenPlay::isWarming($court))
                                        @if ($court['a'] && ! empty($court['closing']))
                                            <span class="inline-flex h-6 items-center rounded-full bg-ink px-2.25 text-xs font-bold text-bg">Last game</span>
                                        @elseif ($warming)
                                            <span class="inline-flex h-6 items-center gap-1.25 rounded-full bg-lime px-2.25 text-xs font-bold text-on-lime"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2h4M12 14l3-3M12 22a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" /></svg>Warmup</span>
                                        @elseif ($court['a'])
                                            <span class="inline-flex h-6 items-center rounded-full bg-lime-soft px-2.25 text-xs font-bold text-lime-text">In play</span>
                                        @else
                                            <span class="inline-flex h-6 items-center rounded-full bg-hot-soft px-2.25 text-xs font-bold text-hot">Open</span>
                                        @endif
                                    </div>
                                    @if ($court['a'])
                                        <span class="inline-flex items-center gap-1.5 font-mono text-[15px] font-semibold whitespace-nowrap text-ink2">
                                            <x-icon name="clock" :size="15" class="lg:max-xl:hidden" /><x-live-timer :start="now()->timestamp - $court['started_at']" :refresh="$warming" />
                                        </span>
                                    @endif
                                </div>

                                <x-live-court :play="$play" :court="$court" :badge="false" :locks="true" :index="$i" class="h-41 md:aspect-[21/10] md:h-auto" player-class="size-8.5 text-xs lg:size-9.5" />

                                @if ($court['a'])
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 px-1">
                                        @foreach (['a', 'b'] as $side)
                                            @if ($side === 'b')
                                                <span class="font-mono text-[11px] text-ink3">VS</span>
                                            @endif
                                            <div @class(['flex min-w-0 flex-col gap-0.5', 'items-end' => $side === 'b'])>
                                                <span class="font-mono text-[10px] tracking-[.12em] text-ink3">TEAM {{ strtoupper($side) }}</span>
                                                <span @class(['flex min-w-0 max-w-full items-center gap-1.5 text-[15px] leading-tight font-bold', 'flex-row-reverse' => $side === 'b'])>
                                                    {{-- One line per player, so full names fit. --}}
                                                    <span @class(['flex min-w-0 flex-col', 'items-end text-right' => $side === 'b'])>
                                                        @foreach ($court[$side]['ids'] as $pid)
                                                            <span class="max-w-full truncate">{{ $play->participant($pid)?->name ?? 'Guest' }}</span>
                                                        @endforeach
                                                    </span>
                                                    @if ($court[$side]['locked'])
                                                        <x-icon name="lock" :size="12" :stroke="2.6" class="text-lime-text" />
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if (! empty($court['closing']))
                                        <div class="flex items-center gap-2.5 rounded-[14px] border border-line2 bg-surface2 py-1.5 pr-1.5 pl-3">
                                            <span class="flex min-w-0 flex-1 items-start gap-1.5 text-xs leading-[1.4] font-semibold text-ink2"><x-icon name="logout" :size="15" class="mt-px shrink-0" />Court {{ $court['n'] }} closes after this game. Nobody new goes on it.</span>
                                            <button type="button" wire:click="keepCourt({{ $court['n'] }})" aria-label="Keep Court {{ $court['n'] }} open after this game"
                                                    class="h-9 shrink-0 cursor-pointer rounded-full border-[1.5px] border-line2 bg-surface px-3 text-xs font-bold text-ink transition hover:brightness-96 active:scale-97">Keep court</button>
                                        </div>
                                    @endif
                                    @if ($courtSubs[$court['n']])
                                        <span class="flex items-center gap-1.5 px-1 text-xs font-semibold text-ink2"><x-icon name="swap" :size="14" :stroke="2" class="shrink-0" /><span class="min-w-0">{{ $courtSubs[$court['n']] }}</span></span>
                                    @endif
                                    @if ($warming)
                                        @php($total = max(1, $court['started_at'] - ($court['w0'] ?? $court['started_at'] - 180)))
                                        <div class="flex flex-col gap-2 rounded-[14px] border border-line bg-surface2 px-3 py-2.5"
                                             x-data="{ end: {{ $court['started_at'] }}, total: {{ $total }}, left: {{ $court['started_at'] - now()->timestamp }}, t: null,
                                                tick() { this.left = Math.max(0, Math.ceil(this.end - Date.now() / 1000 + (window.serverSkew || 0))) },
                                                init() { this.tick(); this.t = setInterval(() => this.tick(), 1000) }, destroy() { clearInterval(this.t) } }">
                                            <div class="flex items-center justify-between gap-2.5">
                                                <span class="text-[13px] font-bold">Warming up</span>
                                                <span class="font-mono text-[13px] font-semibold text-ink2"><x-live-timer :start="now()->timestamp - $court['started_at']" after=" left" /></span>
                                            </div>
                                            <div role="progressbar" aria-label="Warmup on Court {{ $court['n'] }}" aria-valuemin="0" :aria-valuemax="total" :aria-valuenow="total - left" class="h-2 overflow-hidden rounded-full bg-line2">
                                                <div class="h-full rounded-full bg-lime-text transition-[width] duration-1000 ease-linear" :style="{ width: Math.round((total - left) / total * 100) + '%' }" style="width: {{ round(($total - ($court['started_at'] - now()->timestamp)) / $total * 100) }}%"></div>
                                            </div>
                                            <span class="text-xs leading-[1.4] text-ink3">Game clock starts at 0:00, or tap Start game now.</span>
                                        </div>
                                    @endif
                                    <div class="flex gap-2">
                                        @if ($warming)
                                            <button type="button" wire:click="startNow({{ $court['n'] }})" aria-label="Skip the rest of the warmup and start the game on Court {{ $court['n'] }}"
                                                    class="inline-flex h-11.5 flex-1 cursor-pointer items-center justify-center gap-2 rounded-[14px] bg-lime text-[15px] font-extrabold text-on-lime transition hover:brightness-96 active:scale-97">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l12-7.5z" /></svg>Start game now
                                            </button>
                                        @else
                                            <button type="button" wire:click="openScore({{ $court['n'] }})"
                                                    class="inline-flex h-11.5 flex-1 cursor-pointer items-center justify-center gap-2 rounded-[14px] bg-ink text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">
                                                <x-icon name="scoreboard" :size="17" :stroke="1.9" />Enter score
                                            </button>
                                        @endif
                                        <button type="button" wire:click="openCourtSub({{ $court['n'] }})" aria-label="Sub a player in or out on Court {{ $court['n'] }}"
                                                class="inline-flex h-11.5 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-[14px] border-[1.5px] border-line2 bg-surface px-4 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">
                                            <x-icon name="swap" :size="16" :stroke="2" />Sub
                                        </button>
                                    </div>
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
                        <button type="button" wire:click="openCourts" aria-haspopup="dialog"
                                class="flex min-h-14 cursor-pointer items-center justify-center gap-2.5 rounded-3xl border-[1.5px] border-dashed border-line2 px-4 text-[15px] font-bold text-ink2 transition hover:border-ink hover:text-ink active:scale-99 @min-[600px]:col-span-2">
                            <span aria-hidden="true" class="flex size-7 items-center justify-center rounded-full bg-surface2"><x-icon name="plus" :size="14" :stroke="2.6" /></span>Got another court? Add it
                        </button>
                    </div>

                    @if ($results->isNotEmpty())
                        <div @class(['flex-col rounded-3xl border border-line bg-surface p-4.5', 'flex' => $tab !== 'stand', 'hidden md:flex' => $tab === 'stand'])>
                            <div class="mb-2 flex items-baseline justify-between gap-2.5">
                                <h2 class="m-0 font-display text-xl font-extrabold tracking-[-0.03em]">Latest results</h2>
                                <span class="text-[13px] text-ink3">{{ $games }} games logged tonight</span>
                            </div>
                            @foreach ($results as $r)
                                <div wire:key="result-{{ $r['id'] }}" @class(['flex flex-col gap-1.5 border-t border-line py-3', 'animate-snap' => $r['id'] === $freshGame])>
                                    <span class="font-mono text-[11px] tracking-[.08em] text-ink3 uppercase">Court {{ $r['court'] }}, {{ $r['ago'] }}, played {{ $r['played'] }}{{ $r['warmup'] ? ', warmup '.$r['warmup'] : '' }}</span>
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
                                    @if ($r['subs'])
                                        <span class="flex items-center justify-center gap-1.5 text-xs text-ink3"><x-icon name="swap" :size="14" :stroke="2" />{{ $r['subs'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div @class(['hidden md:block' => $tab === 'courts'])>
                        <x-standings :play="$play" :rows="$standings" :page="$standingsPage" :fresh="$podNew" heading="standings-board" class="rounded-3xl" />
                    </div>
                </div>

                {{-- Queue column --}}
                <div @class(['flex-col gap-3.5 rounded-3xl border border-line bg-surface p-4.5 shadow-card lg:sticky lg:top-5 lg:max-h-260 lg:overflow-y-auto lg:[scrollbar-width:thin] [&>*]:shrink-0', 'flex' => $tab === 'queue', 'hidden md:flex' => $tab !== 'queue'])>
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5">
                            <h2 class="m-0 font-display text-[22px] font-extrabold tracking-[-0.03em]">Queue</h2>
                            <span class="inline-flex h-6.5 items-center rounded-full border border-line bg-surface2 px-2.5 font-mono text-xs whitespace-nowrap">{{ $inQueue }} players</span>
                        </div>
                        <button type="button" id="rotm-btn" x-on:click="rotm = ! rotm" x-bind:aria-expanded="rotm" aria-controls="rotm" aria-label="Game mode: {{ $play->rotationLabel() }}. Change it"
                                class="inline-flex h-8.5 cursor-pointer items-center gap-1.5 rounded-full border-[1.5px] border-line2 bg-surface px-3 text-xs font-bold whitespace-nowrap text-ink transition hover:brightness-96 active:scale-97">
                            <x-icon name="rotate" :size="14" />{{ $play->rotationLabel() }}<x-icon name="chevron-down" :size="12" :stroke="2.4" class="transition-transform" x-bind:class="rotm && 'rotate-180'" />
                        </button>
                    </div>
                    <div id="rotm" x-show="rotm" x-cloak x-on:click.outside="if (! $event.target.closest('#rotm-btn')) rotm = false" role="radiogroup" aria-label="Game mode" class="flex animate-fade flex-col gap-2 rounded-[18px] border border-line bg-surface2 p-3">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Game mode: changes from the next game</span>
                        @foreach (['rotate' => ['Everyone rotates', 'Fewest games play first, and partners mix every game.'], 'winners' => ['Winners stay (2 max)', 'Winners stay on for up to 2 games; challengers come from the front of the line.']] as $key => [$label, $desc])
                            @php($on = ($play->rotation === 'winners' ? 'winners' : 'rotate') === $key)
                            <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" wire:click="setRotation('{{ $key }}')" x-on:click="rotm = false"
                                    @class(['flex cursor-pointer items-start gap-3 rounded-[14px] border-[1.5px] p-3 text-left transition hover:brightness-96 active:scale-99', 'border-ink bg-surface' => $on, 'border-line bg-transparent' => ! $on])>
                                <span aria-hidden="true" @class(['mt-0.5 flex size-4.5 shrink-0 items-center justify-center rounded-full border-2', 'border-ink' => $on, 'border-line2' => ! $on])><span @class(['size-2 rounded-full', 'bg-ink' => $on])></span></span>
                                <span class="flex min-w-0 flex-col gap-0.5"><span class="text-sm font-bold">{{ $label }}</span><span class="text-xs leading-[1.4] text-ink2">{{ $desc }}</span></span>
                            </button>
                        @endforeach
                        <span class="text-xs text-ink3">Games on court finish as they are.</span>
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
                            @if ($why)
                                <span class="flex items-start gap-1.5 text-xs leading-[1.4] font-semibold text-ink2"><x-icon name="shuffle" :size="13" :stroke="2.2" class="mt-px shrink-0" /><span>{{ $why['mix'] }}</span></span>
                                @if ($why['wait'])
                                    <span class="text-xs leading-[1.4] text-ink3">{{ $why['wait'] }}</span>
                                @endif
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
                                        <div class="grid grid-cols-2 gap-1.5 border-t border-line pt-2.5">
                                            <span class="col-span-2 text-xs font-semibold text-ink2">Only one of them leaving?</span>
                                            @foreach ($subOut['ids'] as $id)
                                                @php($of = $play->participant($id)?->firstName() ?? 'Guest')
                                                <button type="button" wire:click="subOne({{ $subOut['id'] }}, {{ $id }})" aria-label="Unlock the pair and sub out only {{ $of }}"
                                                        class="inline-flex h-8.5 min-w-0 cursor-pointer items-center justify-center gap-1.25 rounded-full border-[1.5px] border-line2 px-2.75 text-xs font-bold text-ink transition hover:brightness-96 active:scale-97">
                                                    <x-icon name="unlock" :size="13" :stroke="2.2" class="shrink-0" /><span class="truncate">Only {{ $of }}</span>
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
                                <input id="walkin" type="text" wire:model.live.debounce.300ms="walkin" maxlength="60" autocomplete="off" autocorrect="off" spellcheck="false" enterkeyhint="go"
                                       x-on:focus="matchMedia('(pointer: coarse)').matches && setTimeout(() => scrollBy({ top: $el.getBoundingClientRect().top - 96, behavior: 'smooth' }), 300)" placeholder="Add a walk-in by name or email" aria-describedby="walkin-hint"
                                       class="h-11.5 min-w-0 flex-1 rounded-[14px] border-[1.5px] border-line bg-surface2 px-3.5 text-sm text-ink outline-none transition-colors placeholder:text-ink3 focus:border-ink">
                                <button type="submit" class="inline-flex h-11.5 cursor-pointer items-center gap-1.5 rounded-[14px] bg-ink px-4 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">
                                    <x-icon name="user-plus" :size="16" :stroke="1.9" />Add
                                </button>
                            </div>
                            <span id="walkin-hint" class="text-xs leading-[1.45] text-ink3">Type a name to find their PickleX account. Nothing links until you confirm.</span>
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
                                            <span class="text-xs text-ink3">{{ $walkinUser->club ? $walkinUser->club.', ' : '' }}{{ $wg }} {{ Str::plural('game', $wg) }} on PickleX</span>
                                        </span>
                                    </div>
                                    <span class="text-xs leading-[1.45] text-ink2">Check with them first. Tonight's games count toward this account, and they're checked in now.</span>
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
                                                <span class="truncate text-xs text-ink3">{{ $mu->email }}{{ $mu->club ? ', '.$mu->club : '' }}</span>
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
                            @php($meta = $notHere ? 'Not checked in yet' : ($paused ? 'Stepped out, keeps spot' : ($pair ? 'Locked pair, '.$gamesTxt : $gamesTxt.($p1?->paid ? ', Paid' : ''))))
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
        <div wire:key="score-{{ $scoring['n'] }}" class="fixed inset-0 z-65 flex items-end justify-center md:items-center md:p-6"
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
                  class="relative flex max-h-full w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-160 md:max-w-full md:rounded-[28px]">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex flex-col gap-0.5">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Court {{ $scoring['n'] }}, Race to {{ $to }}, <x-live-timer :start="now()->timestamp - $scoring['started_at']" /></span>
                        <h2 id="score-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">Enter the score</h2>
                    </div>
                    <button type="button" wire:click="closeScore" aria-label="Close" class="flex size-11 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-3.5 overflow-y-auto px-4 py-5 lg:px-6">
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @foreach (['a', 'b'] as $side)
                            @php($K = strtoupper($side))
                            <div class="flex flex-col items-center gap-2.5 rounded-[22px] border-2 px-3 py-3 transition-colors lg:gap-3 lg:px-3.5 lg:py-4"
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
                                <div class="flex flex-wrap items-center justify-center gap-x-2.5 gap-y-3 lg:flex-col">
                                <div class="flex items-center gap-1.5 lg:gap-2.5">
                                    <button type="button" x-on:click="step('{{ $side }}', -1)" aria-label="Lower Team {{ $K }} score"
                                            class="flex size-11 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-surface text-ink lg:size-12 transition hover:brightness-96 active:scale-97">
                                        <x-icon name="minus" :size="18" :stroke="2.4" />
                                    </button>
                                    <input type="number" min="0" max="{{ $to }}" inputmode="numeric" enterkeyhint="done" aria-label="Team {{ $K }} score"
                                           x-bind:value="{{ $side }} ?? ''" x-on:input="type('{{ $side }}', $el)" x-on:focus="$el.select()"
                                           class="field-lg h-16 w-17 lg:w-21 [appearance:textfield] rounded-2xl bg-transparent p-0 text-center font-display text-[52px] font-extrabold tracking-[-0.04em] text-ink tabular-nums outline-none lg:h-20 lg:w-24 lg:text-[64px] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                    <button type="button" x-on:click="step('{{ $side }}', 1)" aria-label="Raise Team {{ $K }} score"
                                            class="flex size-11 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-surface text-ink lg:size-12 transition hover:brightness-96 active:scale-97">
                                        <x-icon name="plus" :size="18" :stroke="2.4" />
                                    </button>
                                </div>
                                <div class="flex min-h-10 items-center justify-center gap-2">
                                    <button type="button" x-show="win !== '{{ $side }}'" x-on:click="reach('{{ $side }}')"
                                            class="h-10 cursor-pointer rounded-full border-[1.5px] border-line2 px-3 text-[13px] lg:px-3.5 font-bold text-ink transition hover:brightness-96 active:scale-97">They reached {{ $to }}</button>
                                    <span x-show="win === '{{ $side }}'" x-cloak class="inline-flex h-8 animate-snap items-center gap-1.5 rounded-full bg-lime px-3 text-xs font-extrabold tracking-[.04em] text-on-lime">
                                        <x-icon name="trophy" :size="14" :stroke="2.2" />WINNER
                                    </span>
                                </div>
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
    {{-- Add or remove courts during a live session. Removing a court with a game on asks what happens to the game. --}}
    @if ($play && $courtsDialog)
        @php($cd = $courtsDialog)
        @php($rc = $cd['remove'])
        <div wire:key="courts-{{ $rc['n'] ?? 'list' }}" x-data x-on:keydown.escape.window="$wire.{{ $rc ? "set('crt.step', 'list')" : "set('crt', null)" }}" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <button type="button" wire:click="$set('crt', null)" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

            <div role="dialog" aria-modal="true" aria-labelledby="crt-title" x-trap.noscroll="true"
                 class="relative flex max-h-[92%] w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:max-h-full md:w-170 md:max-w-full md:rounded-[28px]">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $rc ? 'Live session: remove a court' : 'Live session: courts' }}</span>
                        <h2 id="crt-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">{{ $rc ? "Remove Court {$rc['n']}?" : 'Add or remove courts' }}</h2>
                        <span class="truncate text-[13px] text-ink3">
                            @if ($rc)
                                A game is on, {{ count($courts) - 1 }} {{ Str::plural('court', count($courts) - 1) }} left after this
                            @else
                                {{ count($courts) }} {{ Str::plural('court', count($courts)) }}, {{ $cd['busy'] }} in play{{ $cd['closing'] ? ', '.$cd['closing'].' closing' : '' }}
                            @endif
                        </span>
                    </div>
                    <button type="button" wire:click="$set('crt', null)" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto px-6 py-5">
                    @if (! $rc)
                        <section aria-labelledby="crt-add" class="flex flex-col gap-3">
                            <h3 id="crt-add" class="m-0 text-[15px] font-extrabold">Add a court</h3>
                            <div class="flex items-center gap-3">
                                <button type="button" wire:click="stepCourtNumber(-1)" @disabled(! $cd['prev']) aria-label="{{ $cd['prev'] ? "Court {$cd['prev']} instead" : 'No lower court number free' }}"
                                        class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-surface text-ink transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-40"><x-icon name="minus" :size="18" :stroke="2.4" /></button>
                                <div class="flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-[18px] border border-line bg-surface py-2">
                                    <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Court number</span>
                                    <span role="status" aria-live="polite" class="font-display text-[28px] leading-none font-extrabold tracking-[-0.03em]">Court {{ $cd['num'] }}</span>
                                </div>
                                <button type="button" wire:click="stepCourtNumber(1)" @disabled(! $cd['next']) aria-label="{{ $cd['next'] ? "Court {$cd['next']} instead" : 'No higher court number free' }}"
                                        class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-surface text-ink transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-40"><x-icon name="plus" :size="18" :stroke="2.4" /></button>
                            </div>
                            <p class="m-0 text-[13px] text-ink3">Use the number on the court. Numbers in use are skipped.</p>
                            <p @class(['m-0 flex items-start gap-2 rounded-[14px] border px-3.5 py-3 text-[13px] leading-[1.45] font-semibold', 'border-lime bg-lime-soft text-ink' => $cd['ready'], 'border-line bg-surface text-ink2' => ! $cd['ready']])>
                                <x-icon name="arrow-right" :size="16" class="mt-px shrink-0" /><span>{{ $cd['hint'] }}</span>
                            </p>
                            <button type="button" wire:click="addCourt"
                                    class="inline-flex h-12.5 cursor-pointer items-center justify-center gap-2 rounded-full border-2 border-cta-border bg-lime text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">
                                <x-icon name="plus" :size="16" :stroke="2.6" />Add Court {{ $cd['num'] }}
                            </button>
                        </section>

                        <section aria-labelledby="crt-list" class="flex flex-col gap-2.5">
                            <div class="flex items-baseline justify-between gap-2.5">
                                <h3 id="crt-list" class="m-0 text-[15px] font-extrabold">Courts tonight</h3>
                                <span class="text-[13px] text-ink3">{{ $cd['busy'] }} of {{ count($courts) }} in play</span>
                            </div>
                            <ul class="m-0 flex list-none flex-col overflow-hidden rounded-[18px] border border-line bg-surface p-0">
                                @foreach ($courts as $c)
                                    @php($closing = $c['a'] && ! empty($c['closing']))
                                    <li wire:key="crt-row-{{ $c['n'] }}" class="flex items-center gap-3 border-t border-line px-3.5 py-3 first:border-t-0">
                                        <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-surface2 font-display text-lg font-extrabold">{{ $c['n'] }}</span>
                                        <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                            <span class="flex flex-wrap items-center gap-2">
                                                <strong class="text-[15px]">Court {{ $c['n'] }}</strong>
                                                <span @class(['inline-flex h-5.5 items-center rounded-full px-2 text-[11px] font-bold', 'bg-ink text-bg' => $closing, 'bg-lime-soft text-lime-text' => $c['a'] && ! $closing, 'bg-hot-soft text-hot' => ! $c['a']])>
                                                    @if ($c['a'])
                                                        {{ $closing ? 'Last game, ' : (App\Models\OpenPlay::isWarming($c) ? '' : 'In play, ') }}<x-live-timer :start="now()->timestamp - $c['started_at']" before="Warmup " />
                                                    @else
                                                        Open
                                                    @endif
                                                </span>
                                            </span>
                                            <span class="truncate text-xs text-ink3">{{ $closing ? 'Closes after ' : '' }}{{ $c['a'] ? $play->names($c['a']['ids']).' vs '.$play->names($c['b']['ids']) : 'Nobody on it, removing it is instant' }}</span>
                                        </span>
                                        @if ($closing)
                                            <button type="button" wire:click="keepCourt({{ $c['n'] }})" aria-label="Keep Court {{ $c['n'] }} open after this game"
                                                    class="h-9 shrink-0 cursor-pointer rounded-full border-[1.5px] border-line2 px-3 text-xs font-bold text-ink transition hover:brightness-96 active:scale-97">Keep court</button>
                                        @else
                                            @php($can = $cd['canRemove']($c))
                                            <button type="button" wire:click="askRemoveCourt({{ $c['n'] }})" @disabled(! $can) aria-label="Remove Court {{ $c['n'] }}" @if (! $can) aria-describedby="crt-last" @endif
                                                    class="h-9 shrink-0 cursor-pointer rounded-full border-[1.5px] border-hot-line px-3 text-xs font-bold text-hot transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-40">Remove</button>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            @if ($cd['lastOne'])
                                <p id="crt-last" class="m-0 flex items-start gap-2 text-[13px] text-ink2"><x-icon name="alert" :size="15" class="mt-px shrink-0" /><span>One court has to stay. To stop, end the session.</span></p>
                            @endif
                            <p class="m-0 text-[13px] leading-[1.45] text-ink3">Open courts close right away. If a game is on, you choose whether it finishes first.</p>
                        </section>
                    @else
                        @php($four = $play->names([...$rc['a']['ids'], ...$rc['b']['ids']]))
                        @php($locks = collect([$rc['a'], $rc['b']])->filter(fn ($t) => $t['locked'] && count($t['ids']) === 2))
                        <div class="flex flex-col gap-2 rounded-[18px] border border-line bg-surface p-3.5">
                            <span class="flex items-center gap-2 font-mono text-[11px] tracking-[.1em] text-ink3 uppercase"><span class="rounded-full bg-lime-soft px-2 py-0.5 font-sans text-[11px] font-bold tracking-normal text-lime-text normal-case">In play</span>Game on Court {{ $rc['n'] }}, <x-live-timer before="Warmup " :start="now()->timestamp - $rc['started_at']" /></span>
                            <span class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[15px] font-bold"><span>{{ $play->names($rc['a']['ids']) }}</span><span class="font-mono text-[11px] text-ink3">VS</span><span>{{ $play->names($rc['b']['ids']) }}</span></span>
                        </div>
                        <section aria-labelledby="crt-how" class="flex flex-col gap-2.5">
                            <h3 id="crt-how" class="m-0 text-[15px] font-extrabold">What happens to this game?</h3>
                            <div role="radiogroup" aria-labelledby="crt-how" class="flex flex-col gap-2">
                                @foreach ([
                                    'finish' => ['Let this game finish', "Court {$rc['n']} closes after the score is entered. The result counts.", true],
                                    'now' => ['Stop the game now', "The game doesn't count. {$four} go back to the front of the line".($locks->isNotEmpty() ? ', with '.$locks->map(fn ($t) => $play->names($t['ids']))->join(' and ').' still locked together' : '').'.', false],
                                ] as $key => [$label, $desc, $rec])
                                    @php($on = ($this->crt['how'] ?? 'finish') === $key)
                                    <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" wire:click="$set('crt.how', '{{ $key }}')"
                                            @class(['flex cursor-pointer items-start gap-3 rounded-[14px] border-[1.5px] p-3.5 text-left transition hover:brightness-96 active:scale-99', 'border-ink bg-surface' => $on, 'border-line bg-transparent' => ! $on])>
                                        <span aria-hidden="true" @class(['mt-0.5 flex size-4.5 shrink-0 items-center justify-center rounded-full border-2', 'border-ink' => $on, 'border-line2' => ! $on])><span @class(['size-2 rounded-full', 'bg-ink' => $on])></span></span>
                                        <span class="flex min-w-0 flex-col gap-0.5">
                                            <span class="flex flex-wrap items-center gap-2"><span class="text-sm font-bold">{{ $label }}</span>
                                                @if ($rec)
                                                    <span class="inline-flex h-5 items-center rounded-full bg-lime px-1.75 text-[10px] font-extrabold text-on-lime">Recommended</span>
                                                @endif
                                            </span>
                                            <span class="text-xs leading-[1.4] text-ink2">{{ $desc }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                        <p class="m-0 flex items-start gap-2 text-[13px] text-ink3"><x-icon name="alert" :size="16" class="mt-px shrink-0" /><span>Players see Court {{ $rc['n'] }} leave the live view once it closes. You can add it back any time.</span></p>
                    @endif
                </div>

                <div class="flex shrink-0 gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
                    @if (! $rc)
                        <button type="button" wire:click="$set('crt', null)" class="h-13 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">Done</button>
                    @else
                        @php($finish = ($this->crt['how'] ?? 'finish') !== 'now')
                        <button type="button" wire:click="$set('crt.step', 'list')" class="h-13 cursor-pointer rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Back</button>
                        <button type="button" wire:click="removeCourt"
                                @class(['h-13 min-w-0 flex-1 cursor-pointer truncate rounded-full px-4 text-[15px] font-extrabold transition hover:brightness-96 active:scale-97', 'bg-ink text-bg' => $finish, 'bg-hot text-white' => ! $finish])>{{ $finish ? "Close Court {$rc['n']} after this game" : "Stop the game and remove Court {$rc['n']}" }}</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Sub on a live court: who comes off, who goes on, and what happens to them. The game keeps going. --}}
    @if ($play && $subDialog)
        @php($ct = $subDialog['court'])
        @php($outs = $this->csub['out'])
        @php($opt = fn ($on) => $on ? 'border-ink bg-surface' : 'border-line bg-transparent')
        @php($dot = fn ($on) => '<span aria-hidden="true" class="ml-auto flex size-4.5 shrink-0 items-center justify-center rounded-full border-2 '.($on ? 'border-ink' : 'border-line2').'"><span class="size-2 rounded-full '.($on ? 'bg-ink' : '').'"></span></span>')
        <div wire:key="csub-{{ $ct['n'] }}" x-data x-on:keydown.escape.window="$wire.set('csub', null)" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <button type="button" wire:click="$set('csub', null)" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

            <div role="dialog" aria-modal="true" aria-labelledby="cs-title" x-trap.noscroll="true"
                 class="relative flex max-h-[92%] w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:max-h-full md:w-190 md:max-w-full md:rounded-[28px]">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Live court: substitution</span>
                        <h2 id="cs-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">Sub on Court {{ $ct['n'] }}</h2>
                        <span class="truncate text-[13px] text-ink3">{{ $play->names($ct['a']['ids']) }} vs {{ $play->names($ct['b']['ids']) }}, <x-live-timer :start="now()->timestamp - $ct['started_at']" before="warming up, " after=" left" />{{ App\Models\OpenPlay::isWarming($ct) ? '' : ' played' }}</span>
                    </div>
                    <button type="button" wire:click="$set('csub', null)" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto px-6 py-5">
                    <section aria-labelledby="cs-off" class="flex flex-col gap-2.5">
                        <h3 id="cs-off" class="m-0 text-[15px] font-extrabold">1. Who's coming off?</h3>
                        <div role="radiogroup" aria-labelledby="cs-off" class="grid grid-cols-1 gap-2.5 lg:grid-cols-2">
                            @foreach (['a' => 'Team A', 'b' => 'Team B'] as $side => $label)
                                @php($t = $ct[$side])
                                <div class="flex flex-col gap-1.5 rounded-[18px] border border-line bg-surface2 p-2.5">
                                    <span class="flex items-center gap-2 px-1 font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $label }}
                                        @if ($t['locked'])
                                            <span class="inline-flex h-5 items-center gap-1 rounded-full bg-lime-soft px-1.75 font-sans text-[10px] font-extrabold tracking-normal text-lime-text normal-case"><x-icon name="lock" :size="10" :stroke="2.6" />Locked pair</span>
                                        @endif
                                    </span>
                                    @foreach ($t['ids'] as $id)
                                        @php($p = $play->participant($id))
                                        @php($on = count($outs) === 1 && $outs[0] === $id)
                                        <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $p?->name }} comes off" wire:click="pickCourtSub('out', [{{ $id }}])"
                                                class="flex h-12 cursor-pointer items-center gap-2.5 rounded-[14px] border-[1.5px] px-2 text-left transition hover:brightness-96 active:scale-99 {{ $opt($on) }}">
                                            <x-avatar :src="$p?->avatarUrl()" :frame="$p?->frameClass()" :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" class="size-8 text-[11px]" />
                                            <span class="min-w-0 truncate text-sm font-bold">{{ $p?->name }}</span>{!! $dot($on) !!}
                                        </button>
                                    @endforeach
                                    @if (count($t['ids']) === 2)
                                        @php($on = count($outs) === 2 && ! array_diff($t['ids'], $outs))
                                        <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $play->names($t['ids']) }} both come off" wire:click="pickCourtSub('out', @js($t['ids']))"
                                                class="flex h-11 cursor-pointer items-center gap-2.5 rounded-[14px] border-[1.5px] px-3 text-left transition hover:brightness-96 active:scale-99 {{ $opt($on) }}">
                                            <span class="min-w-0 truncate text-[13px] font-bold">Both: {{ $play->names($t['ids']) }}</span>{!! $dot($on) !!}
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($subDialog['lockNote'])
                            <p class="m-0 flex items-start gap-2 text-[13px] text-ink2"><x-icon name="unlock" :size="15" class="mt-px shrink-0" /><span>{{ $subDialog['lockNote'] }}</span></p>
                        @endif
                    </section>

                    <section aria-labelledby="cs-on" class="flex flex-col gap-2.5">
                        <h3 id="cs-on" class="m-0 text-[15px] font-extrabold">2. Who's going on?</h3>
                        @if (! $outs)
                            <p class="m-0 text-sm text-ink2">Pick who's coming off. Checked-in players are listed, next in line first.</p>
                        @elseif ($subDialog['cands']->isNotEmpty())
                            <div role="radiogroup" aria-labelledby="cs-on" class="grid grid-cols-1 gap-2 lg:grid-cols-2">
                                @foreach ($subDialog['cands'] as $cd)
                                    @php($eids = collect($cd['es'])->pluck('id')->all())
                                    @php($pids = collect($cd['es'])->flatMap(fn ($e) => $e['ids'])->all())
                                    @php($on = $eids == $this->csub['in'])
                                    <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $play->names($pids) }} {{ count($pids) > 1 ? 'go on' : 'goes on' }}" wire:click="pickCourtSub('in', @js($eids))"
                                            wire:key="cs-in-{{ implode('-', $eids) }}" class="flex min-h-12 cursor-pointer items-center gap-2 rounded-[14px] border-[1.5px] px-2 py-1.5 text-left transition hover:brightness-96 active:scale-99 {{ $opt($on) }}">
                                        <span class="flex shrink-0">
                                            @foreach ($pids as $j => $id)
                                                @php($cp = $play->participant($id))
                                                <x-avatar :src="$cp?->avatarUrl()" :frame="$cp?->frameClass()" :name="$cp?->name ?? '?'" :color="$cp?->color() ?? '#FFFFFF'" @class(['size-8 border-2 border-bg text-[11px]', '-ml-2' => $j]) />
                                            @endforeach
                                        </span>
                                        <span class="min-w-0 truncate text-sm font-bold">{{ $play->names($pids) }}</span>
                                        <span class="shrink-0 font-mono text-[11px] text-ink3">{{ collect($cd['es'])->map(fn ($e) => '#'.$e['pos'])->join(' ') }}</span>
                                        @if ($cd['tag'])
                                            <span class="inline-flex h-5 shrink-0 items-center rounded-full bg-lime px-1.75 text-[10px] font-extrabold text-on-lime">{{ $cd['tag'] }}</span>
                                        @endif
                                        {!! $dot($on) !!}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="m-0 text-sm font-semibold text-hot">{{ count($outs) === 2 ? 'No two checked-in players are free to go on right now.' : 'No checked-in players are free to go on right now.' }}</p>
                        @endif
                    </section>

                    @if ($outs)
                        <section aria-labelledby="cs-after" class="flex flex-col gap-2.5">
                            <h3 id="cs-after" class="m-0 text-[15px] font-extrabold">3. What about {{ $subDialog['off'] }}?</h3>
                            <div role="radiogroup" aria-labelledby="cs-after" class="grid grid-cols-1 gap-2 lg:grid-cols-3">
                                @foreach (['out' => ['Taking a break', "Stepped out at the back of the line. Tap I'm back when they're ready."], 'back' => ['Back in line now', 'Joins the back of the line, ready to play.'], 'leave' => ['Done for the night', "Leaves the queue. Tonight's games stay on the record."]] as $key => [$label, $desc])
                                    @php($on = $this->csub['after'] === $key)
                                    <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" wire:click="pickCourtSub('after', '{{ $key }}')"
                                            class="flex cursor-pointer items-start gap-2.5 rounded-[14px] border-[1.5px] p-3 text-left transition hover:brightness-96 active:scale-99 {{ $opt($on) }}">
                                        <span class="flex min-w-0 flex-1 flex-col gap-0.5"><span class="text-sm font-bold">{{ $label }}</span><span class="text-xs leading-[1.4] text-ink2">{{ $desc }}</span></span>{!! $dot($on) !!}
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <p class="m-0 flex items-start gap-2 text-[13px] leading-[1.45] text-ink3"><x-icon name="clock" :size="16" class="mt-px shrink-0" /><span>The game and timer keep going. The result counts for the players who finish.</span></p>
                </div>

                <div class="flex shrink-0 gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
                    <button type="button" wire:click="$set('csub', null)" class="h-13 cursor-pointer rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Cancel</button>
                    <button type="button" wire:click="courtSub" @disabled(! $subDialog['go'])
                            class="inline-flex h-13 min-w-0 flex-1 cursor-pointer items-center justify-center gap-2 rounded-full border-2 border-cta-border bg-lime px-4 text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:border-transparent disabled:bg-surface2 disabled:text-ink3 disabled:shadow-none">
                        <x-icon name="swap" :size="16" :stroke="2" class="shrink-0" /><span class="truncate">{{ $subDialog['go'] ?? ($outs ? 'Pick who goes on' : 'Pick who comes off') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    @if ($play)
        {{-- Share view mode: bottom sheet on mobile, centered modal on desktop. --}}
        @if ($shareUrl)
            <div x-show="share" x-cloak x-on:keydown.escape.window="share = false" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
                <button type="button" x-on:click="share = false" aria-label="Close share" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

                <div role="dialog" aria-modal="true" aria-labelledby="share-title" x-trap.noscroll="share"
                     class="relative flex max-h-full w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-180 md:max-w-full md:rounded-[28px]">
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
                                <p class="m-0 text-base leading-[1.55] text-ink2">Scan to follow the courts and queue live. No login needed.</p>
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
        <div x-show="confirmEnd" x-cloak x-on:keydown.escape.window="confirmEnd = false" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <button type="button" x-on:click="confirmEnd = false" aria-label="Keep playing" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

            <div role="alertdialog" aria-modal="true" aria-labelledby="end-title" aria-describedby="end-desc" x-trap.noscroll="confirmEnd"
                 class="relative flex max-h-full w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-130 md:max-w-full md:rounded-[28px]">
                <div class="flex min-h-0 flex-col gap-4 overflow-y-auto px-6 pt-6.5 pb-4.5">
                    <span class="flex size-13 items-center justify-center rounded-2xl border border-hot-line bg-hot-soft text-hot"><x-icon name="stop" :size="22" :stroke="2.2" /></span>
                    <div class="flex flex-col gap-1.5">
                        <h2 id="end-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">End {{ $play->title }}?</h2>
                        <p id="end-desc" class="m-0 text-[15px] leading-normal text-ink2">Closes the court board and live view. Logged games and stats stay saved.</p>
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
