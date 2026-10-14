<x-app-shell>
    <x-page>
        <x-page-header :eyebrow="now()->format('l, M j')" :title="'Hi, '.$user->firstName()">
            <a href="{{ route('member.plays') }}" wire:navigate class="hidden h-12 items-center gap-2 rounded-full bg-ink px-5 text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97 lg:inline-flex">
                <x-icon name="calendar" :size="18" :stroke="1.9" />Find an open play
            </a>
        </x-page-header>

        {{-- display: contents keeps the poll wrapper out of the flex gap --}}
        <div wire:poll.10s class="contents">
            @if ($live)
                <a href="{{ route('member.plays') }}" wire:navigate class="flex items-center gap-4 rounded-3xl bg-ink px-5 py-4.5 text-left text-bg transition hover:brightness-96 active:scale-97 {{ auth()->user()->queueBgClass() }}">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="court" :size="22" :stroke="1.9" /></span>
                    <div class="flex min-w-0 flex-1 flex-col gap-0.75">
                        <span class="flex items-center gap-2 font-mono text-[11px] tracking-widest uppercase"><span class="size-1.75 animate-live rounded-full bg-lime"></span>Live · {{ $live['title'] }}</span>
                        <span class="font-display text-[22px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $live['head'] }}</span>
                        <span class="text-sm opacity-80">{{ $live['sub'] }}@isset($live['timer'])<x-live-timer :start="$live['timer']" before="warming up, game starts in " />@endisset</span>
                        @if ($live['standing'])
                            <span class="mt-1.5 inline-flex min-h-6.5 max-w-full items-center gap-1.5 self-start rounded-full bg-lime px-2.5 py-0.75 text-xs font-extrabold whitespace-nowrap text-on-lime">
                                <x-icon name="trophy" :size="13" :stroke="2.2" /><span>{{ $live['standing'] }}</span>
                            </span>
                        @endif
                    </div>
                    <x-icon name="chevron-right" :size="22" />
                </a>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="flex flex-col gap-2.5 rounded-[22px] border border-line bg-surface p-4 lg:p-5">
                <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">Games played</span>
                <span class="font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[42px]">{{ $stats->count() }}</span>
                <span class="text-[13px] text-ink2">{{ $stats->wins() }} wins · {{ $stats->losses() }} losses</span>
            </div>
            <div class="flex flex-col gap-2.5 rounded-[22px] border border-line bg-surface p-4 lg:p-5">
                <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">Win rate</span>
                <span class="font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[42px]">{{ $stats->count() ? $stats->rate().'%' : '-' }}</span>
                <div class="mt-1.25 h-1.5 overflow-hidden rounded-[3px] bg-surface2"><div class="h-full rounded-[3px] bg-ink" style="width: {{ $stats->rate() }}%"></div></div>
            </div>
            <div class="flex flex-col gap-2.5 rounded-[22px] border border-line bg-surface p-4 lg:p-5">
                <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">Streak</span>
                <span class="flex items-center gap-1.5 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[42px]">{{ $stats->streak() }}<x-icon name="flame" :size="24" :stroke="1.9" class="text-hot" /></span>
                <span class="text-[13px] text-ink2">{{ $stats->lastTen() }} in last 10</span>
            </div>
            <div class="flex flex-col gap-2.5 rounded-[22px] bg-lime p-4 text-on-lime lg:p-5">
                <span class="font-mono text-[11px] tracking-widest uppercase">PickleX points</span>
                <span class="font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[42px]">{{ $stats->points() }}</span>
                <span class="text-[13px] font-semibold">{{ $rankAll ? ($rankMonth ? "#{$rankMonth} this month" : 'Not ranked this month').' · #'.$rankAll.' all-time' : 'Not ranked yet' }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]">
            <div class="flex flex-col gap-5.5 rounded-3xl border border-line bg-surface p-4.5 lg:p-6">
                <div class="flex flex-col gap-3.5">
                    <div class="flex min-h-9 flex-wrap items-center justify-between gap-x-2 gap-y-1">
                        <h2 class="m-0 font-display text-xl font-bold tracking-[-0.02em]">Recent form</h2>
                        <span class="text-[13px] text-ink3">Last 10 games · newest first</span>
                    </div>
                    <div class="grid grid-cols-10 gap-1.5">
                        @forelse ($stats->form() as $r)
                            <span @class([
                                'flex h-10 items-center justify-center rounded-xl border-[1.5px] font-mono text-sm font-semibold',
                                'border-lime bg-lime text-on-lime' => $r === 'W',
                                'border-line2 text-ink2' => $r === 'L',
                            ])>{{ $r }}</span>
                        @empty
                            <p class="col-span-full m-0 text-sm text-ink2">No games logged yet. Join an open play to start your record.</p>
                        @endforelse
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <div class="flex items-baseline justify-between">
                        <h3 class="m-0 text-[15px] font-bold">Games per week</h3>
                        <span class="text-[13px] text-ink3">Last 8 weeks</span>
                    </div>
                    <div class="grid h-37.5 grid-cols-8 items-end gap-2">
                        @foreach ($stats->weeks() as $wk)
                            <div class="flex h-full min-w-0 flex-col items-center justify-end gap-1.5">
                                <span class="font-mono text-xs text-ink2">{{ $wk->v }}</span>
                                <div @class(['w-full max-w-9.5 rounded-[8px_8px_4px_4px]', 'bg-court' => $loop->last, 'bg-ink' => ! $loop->last]) style="height: {{ $wk->h }}px"></div>
                                <span class="font-mono text-[10px] whitespace-nowrap text-ink3">{{ $wk->label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-1.5 rounded-3xl border border-line bg-surface p-4.5 lg:p-6">
                <div class="mb-1.5 flex items-center justify-between">
                    <h2 class="m-0 font-display text-xl font-bold tracking-[-0.02em]">Top partners</h2>
                    <a href="{{ route('member.played-with') }}" wire:navigate class="inline-flex h-9 items-center rounded-full border border-line2 px-3.5 text-[13px] font-bold text-ink transition hover:brightness-96 active:scale-97">See all</a>
                </div>
                @forelse ($stats->partners->sortBy([['games', 'desc'], ['pct', 'desc']])->take(3) as $tp)
                    <div class="flex items-center gap-3 border-t border-line py-3">
                        <x-avatar :src="$tp->user->avatarUrl()" :frame="$tp->user->frameClass()" :name="$tp->user->shownName()" :color="$tp->user->color()" class="size-10 text-[13px]" />
                        <div class="flex min-w-0 flex-1 flex-col"><span class="font-bold">{{ $tp->user->shownName() }}</span><span class="text-[13px] text-ink3">{{ $tp->games }} games together</span></div>
                        <div class="flex flex-col items-end"><span class="font-mono font-semibold">{{ $tp->wins }}-{{ $tp->losses }}</span><span class="text-xs text-ink3">{{ $tp->pct }}% wins</span></div>
                    </div>
                @empty
                    <p class="m-0 border-t border-line py-3 text-sm text-ink2">Your partners show up here after your first logged game.</p>
                @endforelse
                @if ($best = $stats->bestPartner())
                    <div class="mt-2 flex items-center gap-3 rounded-2xl border border-lime bg-lime-soft p-3.5">
                        <x-icon name="trophy" :size="22" :stroke="1.9" class="text-lime-text" />
                        <span class="text-sm leading-[1.4]">Best duo: <strong>{{ $best->user->shownName() }}</strong>, {{ $best->wins }}-{{ $best->losses }} together ({{ $best->pct }}%)</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col rounded-3xl border border-line bg-surface p-4.5 lg:p-6">
            <h2 class="m-0 mb-3 font-display text-xl font-bold tracking-[-0.02em]">Recent matches</h2>
            @forelse ($stats->recent() as $rm)
                <div class="flex items-center gap-3.5 border-t border-line py-3">
                    <span @class([
                        'flex size-9.5 shrink-0 items-center justify-center rounded-xl border-[1.5px] font-mono font-semibold',
                        'border-lime bg-lime text-on-lime' => $rm->won,
                        'border-line2 text-ink2' => ! $rm->won,
                    ])>{{ $rm->won ? 'W' : 'L' }}</span>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <span class="font-semibold">@if ($rm->partner)with {{ $rm->partner }} @endif<span class="font-medium text-ink3">vs</span> {{ $rm->opponents }}</span>
                        <span class="text-[13px] text-ink3">{{ $rm->when }}</span>
                    </div>
                    <span class="font-mono text-[15px] font-semibold">{{ $rm->score }}</span>
                </div>
            @empty
                <p class="m-0 border-t border-line py-3 text-sm text-ink2">No matches yet. Scores logged at open plays show up here.</p>
            @endforelse
        </div>
    </x-page>
</x-app-shell>
