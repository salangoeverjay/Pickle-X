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
        <div class="flex max-w-350 flex-col gap-5 px-4 pt-4 pb-8 lg:px-12 lg:pt-7 lg:pb-12">
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
                    @foreach ([$busy, $here, "Started {$ago} ago"] as $chip)
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
                                    <x-live-court :play="$play" :court="$court" :me="$me" :badge="false" :index="$i" class="h-41 rounded-2xl! lg:h-44.5" player-class="size-8.5 text-xs lg:size-9.5" />
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 px-1 pb-0.5">
                                        <span class="truncate text-sm font-bold">{{ $play->names($court['a']['ids']) }}</span>
                                        <span class="font-mono text-[11px] text-ink3">VS</span>
                                        <span class="truncate text-right text-sm font-bold">{{ $play->names($court['b']['ids']) }}</span>
                                    </div>
                                @else
                                    <x-court class="relative h-41 rounded-2xl lg:h-44.5" :label="'Court '.$court['n'].' is open'">
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
                    @if ($next)
                        <div class="flex flex-col gap-2.5 rounded-[22px] border-[1.5px] border-lime bg-lime-soft p-4">
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
                                            <x-avatar :src="$p->avatarUrl()" :name="$p->name" :color="$p->color()" class="-mr-2 size-8.5 border-2 border-surface text-[11px]" />
                                        @endforeach
                                    </div>
                                    <span class="ml-2 flex-1 text-base font-extrabold">{{ $play->names($next[$side]['ids']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex flex-col gap-0.5 rounded-[22px] border border-line bg-surface p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="m-0 font-display text-[19px] font-extrabold tracking-[-0.03em]">Queue</h2>
                            <span class="text-[13px] text-ink3">{{ $queueCount }} in line</span>
                        </div>
                        @forelse ($queue as $q)
                            <div wire:key="q-{{ $q['pos'] }}" @class(['flex items-center gap-2.5 rounded-xl p-2', 'bg-lime-soft' => $q['you']])>
                                <span class="w-5 shrink-0 font-mono text-xs text-ink3">{{ $q['pos'] }}</span>
                                <div class="flex shrink-0">
                                    @foreach ($q['people'] as $k => $p)
                                        <x-avatar :src="$p->avatarUrl()" :name="$p->name" :color="$p->color()" @class(['size-8 border-2 border-surface text-[11px]', '-ml-2.5' => $k > 0]) />
                                    @endforeach
                                </div>
                                <span class="flex min-w-0 flex-1 flex-col">
                                    <span class="flex min-w-0 items-center gap-1.5 text-sm font-bold">
                                        <span class="truncate">{{ $q['label'] }}</span>
                                        @if ($q['you'])
                                            <span class="inline-flex h-5 shrink-0 items-center rounded-full bg-lime px-1.75 text-[10px] font-extrabold text-on-lime">YOU</span>
                                        @endif
                                    </span>
                                    @if ($q['meta'])
                                        <span class="text-xs font-semibold {{ $q['metaClass'] }}">{{ $q['meta'] }}</span>
                                    @endif
                                </span>
                            </div>
                        @empty
                            <p class="m-0 text-sm text-ink2">Everyone is on court right now.</p>
                        @endforelse
                    </div>

                    <p class="m-0 px-1 text-[13px] leading-normal text-ink3">Joined this open play? Check in from your PickleX app when you reach the courts so the organizer can call you.</p>
                </div>
            </div>
        </div>
    @endunless
    </div>
</div>
