<div wire:poll.10s class="min-h-screen animate-screen bg-bg text-ink">
    <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-line bg-glass px-4 backdrop-blur-[14px] lg:h-19 lg:px-14">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('home') }}" wire:navigate aria-label="PickleX home" class="text-ink"><x-logo class="text-2xl" /></a>
            <span class="hidden h-7 items-center gap-1.5 rounded-full border border-line bg-surface2 px-2.5 font-mono text-[11px] tracking-widest text-ink2 uppercase lg:inline-flex">
                <x-icon name="eye" :size="15" />View only
            </span>
        </div>
        <div class="flex items-center gap-2">
            <x-theme-toggle />
            @auth
                <a href="{{ route(auth()->user()->homeRoute()) }}" wire:navigate class="inline-flex h-11 items-center rounded-full border-[1.5px] border-line2 px-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">Exit view</a>
            @else
                <a href="{{ route('login') }}" wire:navigate class="inline-flex h-11 items-center rounded-full bg-ink px-4 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">Log in</a>
            @endauth
        </div>
    </header>

    <div id="main" tabindex="-1">

    @unless ($live)
        <div class="px-4 pt-4 pb-8 lg:px-12 lg:pt-7 lg:pb-12">
            <div class="flex flex-col items-start gap-3 rounded-[28px] border-[1.5px] border-dashed border-line2 bg-surface p-8">
                <h1 class="m-0 font-display text-[32px] font-extrabold tracking-[-0.04em] lg:text-[44px]">This open play has ended</h1>
                <p class="m-0 text-ink2">Thanks for playing. Results are saved to everyone's stats.</p>
            </div>
        </div>
    @else
        <div class="flex max-w-350 flex-col gap-5 px-4 pt-4 pb-8 lg:px-12 lg:pt-7 lg:pb-12 2xl:max-w-430">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex h-7 items-center gap-1.75 rounded-full bg-ink px-2.75 font-mono text-[11px] tracking-widest text-bg">
                        <span class="size-1.75 animate-live rounded-full bg-lime"></span>LIVE · UPDATES AUTOMATICALLY
                    </span>
                    <span class="font-mono text-xs text-ink3">{{ $play->share_code }}</span>
                </div>
                <h1 class="m-0 font-display text-[28px] leading-[1.02] font-extrabold tracking-[-0.04em] lg:text-[40px]">{{ $play->title }}</h1>
                <span class="text-sm text-ink2">{{ $play->club }} · {{ $play->venue }} · Race to {{ $play->race_to }} · {{ $play->rotationLabel() }}</span>
                <div class="mt-0.5 flex flex-wrap gap-2">
                    @foreach ([$busy, $here, $started] as $chip)
                        <span class="inline-flex h-7.5 items-center rounded-full border border-line bg-surface px-3 text-[13px] font-bold">{{ $chip }}</span>
                    @endforeach
                </div>
            </div>

            <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
                {{-- Courts + results (second on mobile) --}}
                <div class="order-2 flex min-w-0 flex-col gap-4 lg:order-0">
                    <div class="grid gap-3 lg:grid-cols-2">
                        @foreach ($courts as $i => $court)
                            <div wire:key="court-{{ $court['n'] }}" class="flex flex-col gap-2.5 rounded-[22px] border border-line bg-surface p-3">
                                <div class="flex items-center justify-between gap-2.5 px-1 pt-0.5">
                                    <span class="font-display text-[19px] font-extrabold tracking-[-0.03em]">Court {{ $court['n'] }}</span>
                                    @if ($court['a'])
                                        <x-live-timer :start="now()->timestamp - $court['started_at']" class="font-mono text-sm font-semibold text-ink2" />
                                    @else
                                        <span class="inline-flex h-6 items-center rounded-full bg-hot-soft px-2.25 text-xs font-bold text-hot">Open</span>
                                    @endif
                                </div>

                                @if ($court['a'])
                                    <x-live-court :play="$play" :court="$court" :me="$me" :badge="false" :locks="true" :index="$i" class="h-41 rounded-2xl! lg:aspect-[21/10] lg:h-auto" player-class="size-8.5 text-xs lg:size-9.5" />
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 px-1 pb-0.5">
                                        <span class="flex min-w-0 items-center gap-1.25">@if ($court['a']['locked'] ?? false)<span role="img" aria-label="Locked partners" title="Locked partners" class="flex size-4.5 shrink-0 items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="lock" :size="10" :stroke="2.8" /></span>@endif<span class="truncate text-sm font-bold">{{ $play->names($court['a']['ids']) }}</span></span>
                                        <span class="font-mono text-[11px] text-ink3">VS</span>
                                        <span class="flex min-w-0 items-center justify-end gap-1.25"><span class="truncate text-right text-sm font-bold">{{ $play->names($court['b']['ids']) }}</span>@if ($court['b']['locked'] ?? false)<span role="img" aria-label="Locked partners" title="Locked partners" class="flex size-4.5 shrink-0 items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="lock" :size="10" :stroke="2.8" /></span>@endif</span>
                                    </div>
                                @else
                                    <x-court class="relative h-41 rounded-2xl lg:aspect-[21/10] lg:h-auto" :label="'Court '.$court['n'].' is open'">
                                        <div class="absolute inset-0 flex items-center justify-center bg-[rgba(8,20,14,.32)]">
                                            <span class="inline-flex h-8.5 items-center rounded-full bg-white/96 px-3.5 text-[13px] font-extrabold text-on-lime">Next group being called</span>
                                        </div>
                                    </x-court>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if ($results->isNotEmpty())
                        <div class="flex flex-col rounded-[22px] border border-line bg-surface p-4.5">
                            <h2 class="m-0 mb-2 font-display text-[19px] font-extrabold tracking-[-0.03em]">Latest results</h2>
                            @foreach ($results as $r)
                                <div class="flex flex-col gap-1.5 border-t border-line py-2.5">
                                    <span class="font-mono text-[11px] tracking-[.08em] text-ink3 uppercase">Court {{ $r['court'] }} · {{ $r['ago'] }}</span>
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                        <span @class(['truncate text-right text-sm', 'font-extrabold text-ink' => $r['aWon'], 'font-medium text-ink2' => ! $r['aWon']])>{{ $r['a'] }}</span>
                                        <span class="inline-flex items-center gap-1.5 rounded-[10px] bg-surface2 px-2.5 py-1 font-mono text-[15px] font-semibold whitespace-nowrap">
                                            <span @class(['text-ink' => $r['aWon'], 'text-ink2' => ! $r['aWon']])>{{ $r['sa'] }}</span><span class="text-ink3">-</span><span @class(['text-ink' => ! $r['aWon'], 'text-ink2' => $r['aWon']])>{{ $r['sb'] }}</span>
                                        </span>
                                        <span @class(['truncate text-sm', 'font-extrabold text-ink' => ! $r['aWon'], 'font-medium text-ink2' => $r['aWon']])>{{ $r['b'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <x-standings :play="$play" :rows="$standings" :me="$myPid" heading="standings-view" class="rounded-[22px]" />
                </div>

                {{-- Up next + queue (first on mobile) --}}
                <div class="order-1 flex min-w-0 flex-col gap-4 lg:order-0">
                    @if ($group)
                        @php($qById = collect($play->queue())->keyBy('id'))
                        @php($rows = $next
                            ? [['ids' => $next['a']['ids'], 'locked' => $next['a']['locked']], null, ['ids' => $next['b']['ids'], 'locked' => $next['b']['locked']]]
                            : collect($group['used'])->map(fn ($id) => ['ids' => $qById[$id]['ids'], 'locked' => $qById[$id]['locked'] && count($qById[$id]['ids']) === 2])->all())
                        <div class="flex flex-col gap-2.5 rounded-[22px] border-[1.5px] border-lime bg-lime-soft p-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-[11px] font-semibold tracking-[.12em] text-lime-text uppercase">Up next</span>
                                <span class="text-right text-xs text-ink2">{{ $where }}</span>
                            </div>
                            @foreach ($rows as $row)
                                @if (! $row)
                                    <span class="font-mono text-[10px] tracking-[.12em] text-ink3">VS</span>
                                    @continue
                                @endif
                                <div class="flex items-center gap-2.5">
                                    @php($framed = collect($row['ids'])->contains(fn ($id) => $play->participant($id)->frameClass()))
                                    <div class="flex">
                                        @foreach ($row['ids'] as $k => $id)
                                            @php($p = $play->participant($id))
                                            <x-avatar :src="$p->avatarUrl()" :frame="$p->frameClass()" :name="$p->name" :color="$p->color()" @class(['size-8.5 border-2 border-surface text-[11px]', '-mr-2' => ! $framed, 'mr-1.5' => $framed && $k < count($row['ids']) - 1]) />
                                        @endforeach
                                    </div>
                                    <span class="ml-2 min-w-0 flex-1 text-base font-extrabold">{{ $play->names($row['ids']) }}</span>
                                    @if ($row['locked'])
                                        <span class="inline-flex h-5.5 shrink-0 items-center gap-1 rounded-full bg-lime px-2 text-[11px] font-extrabold text-on-lime"><x-icon name="lock" :size="11" :stroke="2.6" />Locked</span>
                                    @endif
                                </div>
                            @endforeach
                            @if (! $next)
                                <span class="text-[13px] font-bold">Needs {{ $group['need'] }} more checked-in {{ Str::plural('player', $group['need']) }} to start a game</span>
                            @elseif (! str_contains($where, 'is open'))
                                <span class="text-[13px] text-ink2">They go on as soon as a court finishes.</span>
                            @endif
                            @if ($skipped[0])
                                <span class="text-xs text-ink3">Skipping {{ $skipped[0] }} {{ $skipped[1] ? 'until they are back.' : 'until they check in.' }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-col gap-0.5 rounded-[22px] border border-line bg-surface p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="m-0 font-display text-[19px] font-extrabold tracking-[-0.03em]">Queue</h2>
                            <span class="text-[13px] text-ink3">{{ $queueCount }} in line</span>
                        </div>
                        @forelse ($queue as $q)
                            @include('livewire.partials.live-queue-row')
                        @empty
                            <p class="m-0 text-sm text-ink2">Everyone is on court right now.</p>
                        @endforelse
                        @if ($more)
                            <div class="flex items-center gap-2.5 px-2 pt-2.5 pb-1.5 text-ink3">
                                <span aria-hidden="true" class="h-0 flex-1 border-t-[1.5px] border-dashed border-line2"></span>
                                <span class="font-mono text-[13px] tracking-[.06em]">+ {{ $more }} more {{ Str::plural('player', $more) }} in line</span>
                                <span aria-hidden="true" class="h-0 flex-1 border-t-[1.5px] border-dashed border-line2"></span>
                            </div>
                        @endif
                        @if ($meRow)
                            @include('livewire.partials.live-queue-row', ['q' => $meRow])
                        @endif
                    </div>

                    <p class="m-0 px-1 text-[13px] leading-normal text-ink3">Joined this open play? Check in from your PickleX app when you reach the courts so the organizer can call you.</p>
                </div>
            </div>
        </div>
    @endunless
    </div>
</div>
