<x-app-shell>
    <x-page wire:poll.10s>
        <x-page-header eyebrow="Open plays" title="Find your next game" description="Join a session and the organizer's queue handles the rest. You'll see here when you're up." />

        @if ($my)
            @php
                $s = $my['status'];
                $play = $my['play'];
                $head = match ($s['state']) {
                    'court' => "You're on Court {$s['court']}",
                    'next' => "You're up next!",
                    default => "#{$s['position']} in line",
                };
                $courts = count($play->boardCourts());
            @endphp
            <div class="grid animate-fade items-center gap-5 rounded-[26px] border-2 border-lime bg-surface p-4.5 shadow-card lg:grid-cols-2 lg:p-6">
                <div class="flex flex-col gap-3">
                    <span class="inline-flex h-7 items-center gap-2 self-start rounded-full bg-ink px-2.75 font-mono text-[11px] tracking-[.08em] text-bg uppercase">
                        <span class="size-1.75 animate-live rounded-full bg-lime"></span>Live · {{ $play->title }}
                    </span>
                    <h2 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[44px]">{{ $head }}</h2>
                    <p class="m-0 text-[15px] text-ink2">
                        @switch($s['state'])
                            @case('court')
                                {{ $s['partner'] ? "With {$s['partner']} vs " : 'Against ' }}{{ $s['opponents'] }} · <x-live-timer :start="now()->timestamp - $s['started_at']" />
                                @break
                            @case('next')
                                {{ $s['partner'] ? "With {$s['partner']} vs " : 'Against ' }}{{ $s['opponents'] }} — grab your paddle.
                                @break
                            @default
                                {{ $s['ahead'] }} {{ $s['ahead'] === 1 ? 'player' : 'players' }} ahead of you · {{ $play->busyCourtCount() }} of {{ $courts }} courts in play
                        @endswitch
                    </p>

                    @if ($s['state'] === 'queued')
                        <div aria-hidden="true" class="mt-0.5 flex flex-wrap items-center gap-1.5">
                            @for ($i = 0; $i < min($s['ahead'], 16); $i++)
                                <span class="size-3.5 rounded-full bg-line2"></span>
                            @endfor
                            <span class="inline-flex h-6 items-center rounded-full bg-lime px-2.5 text-xs font-extrabold text-on-lime">You</span>
                        </div>
                    @endif

                    <div class="mt-1 flex flex-wrap gap-2">
                        <button type="button" wire:click="leave({{ $play->id }})" class="h-11 cursor-pointer rounded-full border-[1.5px] border-line2 bg-transparent px-4.5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">Leave open play</button>
                    </div>
                </div>

                @if ($my['court'])
                    <div class="flex flex-col gap-2">
                        <x-live-court :play="$play" :court="$my['court']" :index="$my['index']" :me="auth()->user()" class="h-42.5 lg:h-52.5" />
                        <span class="text-[13px] text-ink3">
                            {{ $s['state'] === 'court' ? "That's you with the lime ring." : "Court {$my['court']['n']} has played the longest, so it is likely the next to open." }}
                        </span>
                    </div>
                @endif
            </div>
        @endif

        <section class="flex flex-col gap-3.5">
            <h2 class="m-0 flex items-center gap-2.5 font-display text-[22px] font-bold tracking-[-0.02em]">
                <span class="size-2.5 animate-live rounded-full bg-lime outline-[1.5px] outline-on-lime"></span>Live now
            </h2>
            @if ($live->isEmpty())
                <p class="m-0 rounded-[20px] border-[1.5px] border-dashed border-line2 p-5 text-ink2">No open plays are live right now. Check the upcoming sessions below.</p>
            @endif
            <div class="grid gap-3.5 lg:grid-cols-2">
                @foreach ($live as $p)
                    <x-plays.card :play="$p" :joined="in_array($p->id, $joined)" wire:key="live-{{ $p->id }}" />
                @endforeach
            </div>
        </section>

        <section class="flex flex-col gap-3.5">
            <h2 class="m-0 font-display text-[22px] font-bold tracking-[-0.02em]">Upcoming</h2>
            <div class="grid gap-3.5 lg:grid-cols-2">
                @foreach ($upcoming as $p)
                    <x-plays.card :play="$p" :joined="in_array($p->id, $joined)" wire:key="up-{{ $p->id }}" />
                @endforeach
            </div>
        </section>
    </x-page>
</x-app-shell>
