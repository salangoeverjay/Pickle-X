<div class="overflow-x-clip">

    {{-- ============ NAV ============ --}}
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-line bg-glass px-4 backdrop-blur-[14px] lg:h-19 lg:px-14">
        <a href="{{ route('home') }}" wire:navigate aria-label="PickleX home" class="text-ink">
            <x-logo class="text-[27px]" />
        </a>

        <nav aria-label="Sections" class="hidden items-center gap-1 lg:flex">
            @foreach (['how' => 'How it works', 'organizers' => 'For organizers', 'players' => 'For players'] as $id => $label)
                <a href="#{{ $id }}" class="rounded-full px-4 py-2.5 text-sm font-semibold text-ink2 no-underline transition-colors hover:bg-surface2 hover:text-ink">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <x-theme-toggle />
            @auth
                <a href="{{ route(auth()->user()->homeRoute()) }}" wire:navigate class="inline-flex h-11 items-center rounded-full bg-ink px-5 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">Open PickleX</a>
            @else
                <a href="{{ route('login') }}" wire:navigate class="inline-flex h-11 items-center rounded-full border border-line2 px-4.5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">Log in</a>
                <a href="{{ route('signup') }}" wire:navigate class="hidden h-11 items-center rounded-full bg-ink px-5 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97 lg:inline-flex">Join PickleX</a>
            @endauth
        </div>
    </header>

    {{-- ============ HERO ============ --}}
    <section id="main" tabindex="-1" class="relative grid grid-cols-1 items-center gap-7.5 overflow-hidden px-4 pt-7 pb-11 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.08fr)] lg:gap-12 lg:px-14 lg:pt-14 lg:pb-24">
        <div class="relative flex flex-col gap-5 lg:gap-6.5">
            <div class="inline-flex h-9 animate-rise items-center gap-2.5 self-start rounded-full border border-line bg-surface pr-3.5 pl-3 text-[13px] font-semibold text-ink2">
                Open play, organized. No DUPR required.
            </div>

            <h1 class="m-0 animate-rise font-display text-[54px] leading-[.9] font-extrabold tracking-[-0.05em] text-ink [animation-delay:.08s] lg:text-[92px]">
                Show up.<br>Get queued.<br><span class="highlight-band -ml-2 px-2 text-hl-text">Play more.</span>
            </h1>

            <p class="m-0 max-w-135 animate-rise text-[17px] leading-[1.55] text-pretty text-ink2 [animation-delay:.16s] lg:text-[19px]">
                PickleX runs your open play: a fair rotating queue, partner locks, live court boards and stats from games you play.
            </p>

            <div class="flex animate-rise flex-wrap gap-3 [animation-delay:.24s]">
                <a href="{{ route('signup') }}" wire:navigate class="inline-flex h-14.5 items-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-[17px] font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">
                    Join PickleX
                    <x-icon name="arrow-right" :stroke="2.2" />
                </a>
                <a href="{{ route('signup', ['role' => 'organizer']) }}" wire:navigate class="inline-flex h-14.5 items-center rounded-full border-[1.5px] border-line2 bg-surface px-6 text-base font-semibold text-ink transition hover:brightness-96 active:scale-97">
                    Host open plays
                </a>
            </div>

        </div>

        {{-- Hero visual: tilted court with floating cards --}}
        <div class="relative h-75 lg:h-140">
            <div class="absolute inset-x-2 top-13.5 h-49 animate-rise [animation-delay:.2s] lg:top-25 lg:right-0 lg:left-10 lg:h-87.5">
                <div class="relative size-full rounded-3xl shadow-[0_50px_80px_-40px_rgba(8,30,18,.75)] transform-[perspective(900px)_rotateX(24deg)_rotateZ(-5deg)] lg:transform-[perspective(1400px)_rotateX(30deg)_rotateZ(-7deg)]">
                    <x-court class="absolute inset-0 rounded-3xl" player-class="size-8 text-[11px] lg:size-12 lg:text-[15px]"
                             :players="[['RD', '#CDEBB0'], ['CM', '#F7C9DB'], ['PC', '#BCD9F7'], ['TR', '#FFD3A3']]" />
                </div>
            </div>

            {{-- Live court timer --}}
            <div class="absolute top-0 left-0 z-2 animate-float [animation-delay:-1s] lg:top-6">
                <div class="flex animate-rise items-center gap-3 rounded-[18px] border border-line bg-surface py-2.5 pr-4 pl-2.5 shadow-card [animation-delay:.5s]">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-ink text-bg">
                        <x-icon name="court" :stroke="1.9" />
                    </span>
                    <div class="flex flex-col gap-1">
                        <span class="flex items-center gap-1.5 font-mono text-[11px] leading-none font-semibold tracking-widest text-lime-text">
                            <span class="size-1.75 animate-live rounded-full bg-lime"></span>LIVE · COURT 2
                        </span>
                        <x-live-timer class="font-mono text-[22px] leading-none font-semibold text-ink" />
                    </div>
                </div>
            </div>

            {{-- Up next --}}
            <div class="absolute right-0 bottom-0 z-2 animate-float [animation-delay:-2.6s] lg:bottom-2">
                <div class="flex w-53 animate-rise flex-col gap-2.5 rounded-[20px] border border-line bg-surface p-3.5 shadow-card [animation-delay:.65s] lg:w-65.5">
                    <span class="font-mono text-[11px] leading-none font-medium tracking-[.12em] text-ink3 uppercase">Up next · Court 3</span>
                    <div class="flex items-center gap-2.5">
                        <div class="flex">
                            <span class="flex size-7.5 items-center justify-center rounded-full border-2 border-surface bg-[#E2D2FF] text-[11px] font-extrabold text-on-lime">BT</span>
                            <span class="-ml-2 flex size-7.5 items-center justify-center rounded-full border-2 border-surface bg-[#FFEFA3] text-[11px] font-extrabold text-on-lime">ML</span>
                        </div>
                        <span class="flex-1 text-sm font-bold">Ben &amp; Mika</span>
                        <span class="inline-flex h-6 items-center gap-1 rounded-full bg-lime px-2 text-[11px] font-bold text-on-lime">
                            <x-icon name="lock" :size="12" :stroke="2.4" />Locked
                        </span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex">
                            <span class="flex size-7.5 items-center justify-center rounded-full border-2 border-surface bg-[#CDEBB0] text-[11px] font-extrabold text-on-lime">RT</span>
                            <span class="-ml-2 flex size-7.5 items-center justify-center rounded-full border-2 border-surface bg-[#BCD9F7] text-[11px] font-extrabold text-on-lime">KS</span>
                        </div>
                        <span class="flex-1 text-sm font-bold">Ria &amp; Kai</span>
                        <span class="font-mono text-[11px] text-ink3">VS</span>
                    </div>
                </div>
            </div>

            {{-- Queue ticker (desktop) --}}
            <div class="absolute bottom-0 left-6 z-2 hidden animate-float [animation-delay:-3.8s] lg:block">
                <div class="w-59 animate-rise rounded-[20px] bg-ink px-3.5 pt-3.5 pb-2 text-bg shadow-card [animation-delay:.8s]">
                    <div class="mb-2 flex items-center justify-between font-mono text-[11px]">
                        <span class="tracking-[.12em]">QUEUE</span>
                        <span class="tracking-[.08em] text-lime-on-ink">{{ $waiting }} WAITING</span>
                    </div>
                    <div class="fade-y h-33 overflow-hidden">
                        <div class="flex animate-ticker flex-col">
                            @foreach ($ticker as $t)
                                <div class="flex h-11 shrink-0 items-center gap-2.5">
                                    <span class="w-5.5 font-mono text-xs opacity-70">{{ $t['pos'] }}</span>
                                    <span class="flex size-7 items-center justify-center rounded-full text-[11px] font-extrabold text-on-lime" style="background: {{ $t['color'] }}">{{ $t['initials'] }}</span>
                                    <span class="text-sm font-semibold">{{ $t['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ MARQUEE ============ --}}
    <div aria-hidden="true" class="relative z-3 -mx-7.5 -rotate-2 overflow-hidden border-y-2 border-on-lime bg-lime lg:-mx-10">
        <div class="flex w-max animate-marquee">
            @foreach ([...$marquee, ...$marquee] as $word)
                <span class="inline-flex items-center gap-6.5 px-3 py-3.5 font-display text-2xl leading-none font-extrabold tracking-[-0.02em] whitespace-nowrap text-on-lime uppercase lg:px-4 lg:py-5 lg:text-[38px]">
                    {{ $word }}<span class="inline-block size-3.5 rounded-full bg-on-lime"></span>
                </span>
            @endforeach
        </div>
    </div>

    {{-- ============ HOW IT WORKS ============ --}}
    <section id="how" class="grid grid-cols-1 gap-8 px-4 pt-18 pb-14 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16 lg:px-14 lg:pt-30 lg:pb-26">
        <h2 class="m-0 max-w-[14ch] font-display text-[38px] leading-[.98] font-extrabold tracking-[-0.04em] text-balance lg:sticky lg:top-28 lg:self-start lg:text-6xl">From sign-up to game point.</h2>
        <ol class="m-0 flex list-none flex-col p-0">
            @foreach ([
                ['calendar', 'An organizer opens a play', 'Venue, time, courts and a player cap. Only admin-approved organizers can host, so every session is legit.'],
                ['rotate', 'Players join and check in', 'Members join from their phone and check in at the courts. The queue rotates everyone fairly.'],
                ['chart', 'Every game counts', 'Winners are logged court-side. Stats, partners and rankings update on their own. No DUPR needed.'],
            ] as [$icon, $heading, $body])
                <li class="flex gap-5 border-t border-line py-7 first:border-t-0 first:pt-0 lg:gap-7 lg:py-9">
                    <span class="flex size-13 shrink-0 items-center justify-center rounded-2xl bg-lime text-on-lime lg:size-15"><x-icon :name="$icon" :size="24" :stroke="1.9" /></span>
                    <div class="flex flex-col gap-2 pt-1">
                        <h3 class="m-0 font-display text-[26px] leading-[1.1] font-bold tracking-[-0.03em] lg:text-[30px]">{{ $heading }}</h3>
                        <p class="m-0 max-w-[52ch] text-base leading-[1.6] text-ink2 lg:text-[17px]">{{ $body }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- ============ FOR ORGANIZERS ============ --}}
    <section id="organizers" class="p-4 lg:px-14 lg:py-6">
        <div class="relative grid grid-cols-1 items-center gap-8 overflow-hidden rounded-[28px] bg-court px-5 py-8 text-white lg:grid-cols-2 lg:gap-14 lg:rounded-[36px] lg:p-16">
            <div aria-hidden="true" class="pointer-events-none absolute -top-20 -right-30 h-90 w-130 -rotate-12 rounded-md border-[3px] border-white/12">
                <div class="absolute inset-y-0 left-1/2 border-l-[3px] border-white/12"></div>
                <div class="absolute inset-y-0 inset-x-[34%] border-x-[3px] border-white/12"></div>
            </div>

            <div class="relative flex flex-col gap-5.5">
                <span class="font-mono text-xs font-medium tracking-[.14em] text-white/88 uppercase">For organizers</span>
                <h2 class="m-0 font-display text-[38px] leading-[.98] font-extrabold tracking-[-0.04em] text-balance lg:text-6xl">Run the courts, not a spreadsheet.</h2>
                <div class="grid grid-cols-1 gap-x-6 gap-y-4.5 lg:grid-cols-2">
                    @foreach ([
                        ['lock', 'Partner lock', 'Pair two players so they always queue and play together.'],
                        ['court', 'Live court board', "See who is on, how long they've played, and who's next."],
                        ['rotate', 'Fair rotation', 'Everyone cycles through, so nobody sits out twice in a row.'],
                        ['user-plus', 'Walk-ins in seconds', "Type a name and they're in line. No account needed."],
                    ] as [$icon, $heading, $body])
                        <div class="flex items-start gap-3.5">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lime text-on-lime"><x-icon :name="$icon" /></span>
                            <div>
                                <div class="text-base font-bold">{{ $heading }}</div>
                                <div class="text-sm leading-normal text-white/88">{{ $body }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div>
                    <a href="{{ route('signup', ['role' => 'organizer']) }}" wire:navigate class="inline-flex h-13.5 items-center gap-2.5 rounded-full bg-lime px-6 text-base font-bold text-on-lime transition hover:brightness-96 active:scale-97">
                        Host open plays
                        <x-icon name="arrow-right" :size="18" :stroke="2.2" />
                    </a>
                </div>
            </div>

            {{-- Queue preview --}}
            <div class="relative flex flex-col gap-3 rounded-[26px] bg-surface p-5 text-ink shadow-[0_40px_70px_-30px_rgba(0,0,0,.5)]">
                <div class="flex items-center justify-between gap-2.5">
                    <div class="flex flex-col">
                        <span class="font-display text-lg font-bold tracking-[-0.02em]">Queue</span>
                        <span class="text-[13px] text-ink3">Monday Night Open Play</span>
                    </div>
                    <span class="inline-flex h-7 items-center gap-1.5 rounded-full bg-ink px-2.5 font-mono text-[11px] tracking-[.08em] text-bg">
                        <span class="size-1.75 animate-live rounded-full bg-lime"></span>LIVE
                    </span>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border-2 border-lime bg-lime-soft p-3">
                    <span class="w-4.5 font-mono text-xs text-ink3">1</span>
                    <div class="flex">
                        <span class="flex size-8 items-center justify-center rounded-full border-2 border-surface bg-[#E2D2FF] text-[11px] font-extrabold text-on-lime">BT</span>
                        <span class="-ml-2 flex size-8 items-center justify-center rounded-full border-2 border-surface bg-[#FFEFA3] text-[11px] font-extrabold text-on-lime">ML</span>
                    </div>
                    <div class="flex flex-1 flex-col">
                        <span class="text-sm font-bold">Ben &amp; Mika</span>
                        <span class="text-xs text-ink2">Locked partners</span>
                    </div>
                    <span class="flex size-7.5 animate-live items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="lock" :size="15" :stroke="2.3" /></span>
                </div>

                @foreach ([['2', 'RT', '#CDEBB0', 'Ria Torres'], ['3', 'KS', '#BCD9F7', 'Kai Santos']] as [$pos, $initials, $color, $name])
                    <div class="flex items-center gap-3 rounded-2xl border-[1.5px] border-ink bg-surface2 p-3">
                        <span class="w-4.5 font-mono text-xs text-ink3">{{ $pos }}</span>
                        <span class="flex size-5.5 items-center justify-center rounded-[7px] bg-ink text-bg"><x-icon name="check" :size="14" :stroke="3" /></span>
                        <span class="flex size-8 items-center justify-center rounded-full text-[11px] font-extrabold text-on-lime" style="background: {{ $color }}">{{ $initials }}</span>
                        <div class="flex flex-1 flex-col">
                            <span class="text-sm font-bold">{{ $name }}</span>
                            <span class="text-xs text-ink2">3 games tonight</span>
                        </div>
                    </div>
                @endforeach

                <div class="flex items-center justify-between gap-2.5 rounded-full bg-ink py-2 pr-2 pl-3.5 text-bg">
                    <span class="text-[13px] font-semibold">Ria &amp; Kai selected</span>
                    <span class="inline-flex h-9 items-center gap-1.5 rounded-full bg-lime px-3.5 text-[13px] font-bold text-on-lime">
                        <x-icon name="lock" :size="14" :stroke="2.3" />Lock as partners
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FOR PLAYERS ============ --}}
    <section id="players" class="grid grid-cols-1 items-center gap-8 px-4 pt-18 pb-14 lg:grid-cols-2 lg:gap-14 lg:px-14 lg:pt-30 lg:pb-24">
        {{-- Player card preview --}}
        <div class="order-2 flex flex-col gap-4.5 rounded-[28px] border border-line bg-surface p-6 shadow-card lg:order-0">
            <div class="flex items-center gap-3">
                <span class="flex size-12 items-center justify-center rounded-full bg-[#BCD9F7] font-extrabold text-on-lime">KS</span>
                <div class="flex flex-1 flex-col">
                    <span class="text-base font-bold">Kai Santos</span>
                    <span class="text-[13px] text-ink3">Panabo Dink Society</span>
                </div>
                <span class="inline-flex h-7.5 items-center gap-1.5 rounded-full bg-lime px-3 text-xs font-bold text-on-lime">
                    <x-icon name="trophy" :size="14" :stroke="2.2" />#3 this month
                </span>
            </div>

            <div class="grid grid-cols-3 gap-2.5">
                @foreach ([['Games', '48'], ['Win rate', '60%']] as [$label, $value])
                    <div class="rounded-[18px] border border-line bg-surface2 p-3.5">
                        <div class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $label }}</div>
                        <div class="font-display text-[32px] font-extrabold tracking-[-0.03em]">{{ $value }}</div>
                    </div>
                @endforeach
                <div class="rounded-[18px] bg-lime p-3.5 text-on-lime">
                    <div class="font-mono text-[11px] tracking-widest">STREAK</div>
                    <div class="font-display text-[32px] font-extrabold tracking-[-0.03em]">W3</div>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <span class="mb-1.5 font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Played with most</span>
                @foreach ([
                    ['RD', '#CDEBB0', 'Rico Dizon', '11 games · 8-3'],
                    ['ML', '#FFEFA3', 'Mika Lim', '7 games · 4-3'],
                    ['BC', '#E2D2FF', 'Bea Castillo', '6 games · 4-2'],
                ] as [$initials, $color, $name, $record])
                    <div @class(['flex items-center gap-3 py-2', 'border-t border-line' => ! $loop->first])>
                        <span class="flex size-8.5 items-center justify-center rounded-full text-xs font-extrabold text-on-lime" style="background: {{ $color }}">{{ $initials }}</span>
                        <span class="flex-1 font-semibold">{{ $name }}</span>
                        <span class="font-mono text-[13px] text-ink2">{{ $record }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col gap-5.5">
            <h2 class="m-0 font-display text-[38px] leading-[.98] font-extrabold tracking-[-0.04em] text-balance lg:text-6xl">Rankings without DUPR.</h2>
            <p class="m-0 max-w-130 text-[17px] leading-[1.55] text-pretty text-ink2 lg:text-[19px]">
                PickleX Points come from what happens on court, not a self-rating. See who you play best with and where you stand this month.
            </p>
            <div class="flex flex-wrap gap-2.5">
                @foreach ([['+10', 'per win'], ['+3', 'per game played'], ['+2', 'per new partner']] as [$points, $label])
                    <span class="inline-flex h-10 items-center gap-2 rounded-full border-[1.5px] border-line2 px-4 text-sm font-bold">
                        <span class="font-mono text-lime-text">{{ $points }}</span>{{ $label }}
                    </span>
                @endforeach
            </div>
            <div>
                <a href="{{ route('signup') }}" wire:navigate class="inline-flex h-13.5 items-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">
                    Join PickleX
                    <x-icon name="arrow-right" :size="18" :stroke="2.2" />
                </a>
            </div>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="p-4 lg:px-14 lg:py-6">
        <div class="relative flex flex-col items-start gap-5.5 overflow-hidden rounded-[28px] border-[1.5px] border-lime bg-lime-soft px-5.5 py-12 text-ink lg:rounded-[36px] lg:px-16 lg:py-20">
            <div aria-hidden="true" class="cta-ball absolute -top-27.5 -right-27.5 size-60 animate-spin-slow rounded-full lg:-top-22.5 lg:-right-22.5 lg:size-90"></div>
            <h2 class="relative m-0 max-w-195 font-display text-[44px] leading-[.95] font-extrabold tracking-[-0.045em] text-balance lg:text-7xl">Your next rally is already in the queue.</h2>
            <p class="relative m-0 max-w-130 text-[17px] leading-[1.55] text-ink2 lg:text-[19px]">Join as a player in under a minute, or apply to host open plays for your club.</p>
            <div class="relative flex flex-wrap gap-3">
                <a href="{{ route('signup') }}" wire:navigate class="inline-flex h-14 items-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">
                    Join PickleX
                    <x-icon name="arrow-right" :size="18" :stroke="2.2" />
                </a>
                <a href="{{ route('signup', ['role' => 'organizer']) }}" wire:navigate class="inline-flex h-14 items-center rounded-full border-[1.5px] border-line2 bg-surface px-5.5 text-base font-semibold text-ink transition hover:brightness-96 active:scale-97">
                    Host open plays
                </a>
            </div>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer class="flex flex-col items-start justify-between gap-4 border-t border-line px-4 pt-7 pb-9 lg:flex-row lg:items-center lg:px-14 lg:pt-10 lg:pb-12">
        <x-logo class="text-[22px]" role="img" aria-label="PickleX" />
        <span class="text-sm text-ink3">© {{ date('Y') }} PickleX · by <strong class="font-bold text-ink2">CreativeX Techlabs Inc.</strong></span>
        <div class="flex gap-1.5">
            <a href="{{ route('login') }}" wire:navigate class="inline-flex h-11 items-center rounded-full px-3.5 text-sm font-semibold text-ink2 transition-colors hover:bg-surface2 hover:text-ink">Log in</a>
            <a href="{{ route('signup') }}" wire:navigate class="inline-flex h-11 items-center rounded-full px-3.5 text-sm font-semibold text-ink2 transition-colors hover:bg-surface2 hover:text-ink">Join PickleX</a>
        </div>
    </footer>

</div>
