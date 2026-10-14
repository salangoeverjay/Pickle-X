<x-app-shell>
    <x-page wire:poll.10s>
        <x-page-header eyebrow="Open plays" title="Find your next game" description="Join a session and the organizer's queue handles the rest. You'll see here when you're up." />

        @if ($firstTime)
            <div class="flex animate-rise flex-wrap items-center gap-x-4.5 gap-y-3.5 rounded-[22px] border-[1.5px] border-lime bg-lime-soft px-5 py-4">
                <span aria-hidden="true" class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-lime text-on-lime"><x-icon name="calendar" :size="22" :stroke="2" /></span>
                <div class="flex min-w-0 flex-[1_1_280px] flex-col gap-0.5">
                    <span class="text-base font-extrabold">New here? Join any open play below.</span>
                    <span class="text-sm leading-[1.45] text-ink2">Pay by QR if there's a fee, tap Check in when you reach the courts, and the organizer's queue calls you up. Your first game puts you on the rankings.</span>
                </div>
            </div>
        @endif

        @if ($my)
            @php
                $s = $my['status'];
                $play = $my['play'];
                $head = match ($s['state']) {
                    'court' => "You're on Court {$s['court']}",
                    'next' => "You're up next",
                    'waiting' => "You're next in line",
                    'checkin' => 'Check in to get on court',
                    'paused' => 'You stepped out',
                    default => "#{$s['position']} in line",
                };
                $courts = count($play->boardCourts());
            @endphp
            <div class="grid animate-fade items-center gap-5 rounded-[26px] border-2 border-lime bg-surface p-4.5 shadow-card lg:grid-cols-2 lg:p-6 {{ auth()->user()->queueBgClass() }}">
                <div class="flex flex-col gap-3">
                    <span class="inline-flex h-7 items-center gap-2 self-start rounded-full bg-ink px-2.75 font-mono text-[11px] tracking-[.08em] text-bg uppercase">
                        <span class="size-1.75 animate-live rounded-full bg-lime"></span>Live · {{ $play->title }}
                    </span>
                    <h2 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[44px]">{{ $head }}</h2>
                    <p class="m-0 text-[15px] text-ink2">
                        @switch($s['state'])
                            @case('court')
                                {{ $s['partner'] ? "With {$s['partner']} vs " : 'Against ' }}{{ $s['opponents'] }} · <x-live-timer :start="now()->timestamp - $s['started_at']" before="warming up, game starts in " />
                                @break
                            @case('next')
                                {{ $s['partner'] ? "With {$s['partner']} vs " : 'Against ' }}{{ $s['opponents'] }}{{ $s['court'] ? " on Court {$s['court']}. Grab your paddle." : '. You go on as soon as a court finishes.' }}
                                @break
                            @case('waiting')
                                Waiting for {{ $s['need'] }} more checked-in {{ $s['need'] === 1 ? 'player' : 'players' }} to fill a game. Stay close to the courts.
                                @break
                            @case('checkin')
                                You're #{{ $s['position'] }} in line. Organizers only call players who are checked in, so tap Check in when you reach the courts.
                                @break
                            @case('paused')
                                Someone subbed in for you. Tap I'm back when you're ready. You kept your spot at #{{ $s['position'] }}.
                                @break
                            @default
                                {{ $s['ahead'] }} {{ $s['ahead'] === 1 ? 'player' : 'players' }} ahead of you · {{ $play->busyCourtCount() }} of {{ $courts }} courts in play
                        @endswitch
                    </p>

                    @if ($mates = $my['mates'])
                        @php($uniq = array_values(array_unique($mates)))
                        <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-2">
                            <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">Partners tonight{{ $my['others'] ? ' · '.count($uniq).' of '.$my['others'].' players' : '' }}</span>
                            <span class="flex">
                                @foreach (array_slice($uniq, -8) as $id)
                                    @php($mp = $play->participant($id))
                                    <x-avatar :src="$mp?->avatarUrl()" :frame="$mp?->frameClass()" :name="$mp?->name ?? '?'" :color="$mp?->user ? $mp->color() : '#E6E3D6'" title="{{ $mp?->name }}" class="-mr-1.5 size-7.5 border-2 border-surface text-[10px]" />
                                @endforeach
                            </span>
                            <span class="basis-full text-[13px] leading-[1.4] text-ink2">
                                {{ count($uniq) }} different {{ Str::plural('partner', count($uniq)) }} in {{ count($mates) }} {{ Str::plural('game', count($mates)) }} · {{ count($uniq) === count($mates) ? 'everyone rotates, so you keep meeting new partners' : 'repeats only when the line is short' }}
                            </span>
                        </div>
                    @endif

                    @if ($s['state'] === 'queued' && $s['checked_in_at'])
                        <div aria-hidden="true" class="mt-0.5 flex flex-wrap items-center gap-1.5">
                            @for ($i = 0; $i < min($s['ahead'], 16); $i++)
                                <span class="size-3.5 rounded-full bg-line2"></span>
                            @endfor
                            <span class="inline-flex h-6 items-center rounded-full bg-lime px-2.5 text-xs font-extrabold text-on-lime">You</span>
                        </div>
                    @endif

                    @if ($s['checked_in_at'])
                        <span class="inline-flex h-7.5 items-center gap-1.5 self-start rounded-full bg-lime-soft px-3 text-[13px] font-bold text-lime-text">
                            <x-icon name="check" :size="16" />Checked in {{ $s['checked_in_at']->format('g:i A') }}
                        </span>
                    @endif

                    <div class="mt-1 flex flex-wrap items-center gap-2.5">
                        @if ($s['state'] === 'checkin')
                            <button type="button" wire:click="checkInPlay({{ $play->id }})" class="inline-flex h-12.5 grow cursor-pointer items-center justify-center gap-2 rounded-full border-2 border-cta-border bg-lime px-5.5 text-[15px] font-extrabold text-on-lime shadow-cta lg:grow-0 transition hover:brightness-96 active:scale-97">
                                <x-icon name="pin" :size="18" />I'm at the courts
                            </button>
                        @elseif ($s['state'] === 'paused')
                            <button type="button" wire:click="stepBackIn({{ $play->id }})" class="inline-flex h-12.5 grow cursor-pointer items-center justify-center rounded-full border-2 border-cta-border bg-lime px-5.5 text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 lg:grow-0">I'm back</button>
                        @endif
                        @if ($play->share_code)
                            <a href="{{ route('live', $play->share_code) }}" wire:navigate class="inline-flex h-12.5 grow items-center justify-center gap-2 rounded-full border-2 border-line2 px-5.5 text-[15px] font-extrabold text-ink transition hover:brightness-96 active:scale-97 lg:grow-0">
                                <x-icon name="eye" :size="18" />Live board
                            </a>
                        @endif
                        <button type="button" wire:click="leavePlay({{ $play->id }})" class="h-12.5 grow cursor-pointer rounded-full border-0 bg-transparent px-3.5 text-sm font-semibold text-ink2 underline lg:grow-0 underline-offset-3 transition hover:brightness-96 active:scale-97">Leave open play</button>
                    </div>
                </div>

                @if ($my['court'])
                    <div class="flex flex-col gap-2">
                        <x-live-court :play="$play" :court="$my['court']" :index="$my['index']" :me="auth()->user()" class="h-42.5 lg:aspect-[12/5] lg:h-auto" />
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
                    <x-plays.card :play="$p" :joined="in_array($p->id, $joined)" :paid="in_array($p->id, $paidIds)" :claimed="in_array($p->id, $claimedIds)" wire:key="live-{{ $p->id }}" />
                @endforeach
            </div>
        </section>

        <section class="flex flex-col gap-3.5">
            <h2 class="m-0 font-display text-[22px] font-bold tracking-[-0.02em]">Upcoming</h2>
            <div class="grid gap-3.5 lg:grid-cols-2 2xl:grid-cols-3">
                @foreach ($upcoming as $p)
                    <x-plays.card :play="$p" :joined="in_array($p->id, $joined)" :paid="in_array($p->id, $paidIds)" :claimed="in_array($p->id, $claimedIds)" wire:key="up-{{ $p->id }}" />
                @endforeach
            </div>
        </section>

        @if ($history['rows']->isNotEmpty())
            @php($medals = App\Support\Results::MEDALS)
            @php($games = $history['w'] + $history['l'])
            <section aria-labelledby="past-title" class="flex flex-col gap-3.5">
                <div class="flex items-baseline justify-between gap-2.5">
                    <h2 id="past-title" class="m-0 font-display text-[22px] font-bold tracking-[-0.02em]">Past open plays</h2>
                    <span class="font-mono text-xs text-ink3">{{ $history['rows']->count() }} concluded</span>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <x-stat-tile label="Sessions" :value="$history['rows']->count()" :sub="$games.' '.Str::plural('game', $games).' played'" />
                    <x-stat-tile label="Record" :value="$history['w'].'-'.$history['l']" :sub="round($history['w'] * 100 / max(1, $games)).'% win rate'" />
                    <div class="flex flex-col gap-2 rounded-[22px] bg-lime p-4 text-on-lime lg:p-5">
                        <span class="font-mono text-[11px] tracking-widest uppercase">Podium finishes</span>
                        <span class="font-display text-[34px] leading-none font-extrabold tracking-[-0.04em] lg:text-[40px]">{{ array_sum($history['medals']) }}</span>
                        <span class="flex flex-wrap gap-1.5">
                            @foreach ($history['medals'] as $i => $n)
                                <span class="inline-flex h-6 items-center gap-1.25 rounded-full bg-[rgba(18,23,15,.1)] pr-2 pl-1 text-xs font-extrabold" aria-label="{{ $n }} {{ $medals[$i][1] }}">
                                    <x-medal :rank="$i + 1" :size="15" :number="false" :label="false" />{{ $n }}
                                </span>
                            @endforeach
                        </span>
                    </div>
                    <x-stat-tile label="Best finish" :value="'#'.$history['best']['row']['rank']" :sub="$history['best']['play']->title.' · '.$history['best']['play']->starts_at->format('D, M j')" />
                </div>
                <div class="flex flex-col gap-2.5">
                    @foreach ($historyPage as ['play' => $p, 'row' => $r])
                        @php($top = $r['rank'] <= 3)
                        <div wire:key="past-{{ $p->id }}" class="flex flex-wrap items-center gap-x-4.5 gap-y-3.5 rounded-[22px] border border-line bg-surface p-3.5 transition duration-300 hover:-translate-y-1 hover:shadow-card lg:px-5 lg:py-4">
                            <div class="flex min-w-0 flex-[1_1_260px] items-center gap-3.5">
                                <x-date-tile :date="$p->starts_at" />
                                <div class="flex min-w-0 flex-1 flex-col gap-1">
                                    <span class="font-display text-lg leading-[1.2] font-bold tracking-[-0.02em]">{{ $p->title }}</span>
                                    <span class="text-[13px] leading-[1.4] text-ink3">{{ $p->club }} · {{ $p->timeRange() }} · {{ $r['of'] }} players</span>
                                </div>
                            </div>
                            <div class="flex flex-auto flex-wrap items-center justify-between gap-x-3.5 gap-y-2.5 lg:justify-end">
                                <div class="flex items-center gap-3.5">
                                    <span @class(['inline-flex h-8.5 items-center gap-2 rounded-full border text-[13px] font-extrabold whitespace-nowrap', 'border-lime bg-lime-soft pr-3 pl-1.5' => $top, 'border-line bg-surface2 px-3' => ! $top])>
                                        @if ($top)
                                            <x-medal :rank="$r['rank']" :size="18" :label="false" />
                                            <span class="sr-only">{{ $medals[$r['rank'] - 1][1] }},</span>
                                        @endif
                                        {{ App\Support\Results::ordinal($r['rank']) }} of {{ $r['of'] }}
                                    </span>
                                    <span class="flex min-w-14 flex-col">
                                        <span class="font-mono text-[15px] font-bold">{{ $r['w'] }}-{{ $r['l'] }}</span>
                                        <span @class(['font-mono text-xs font-semibold', 'text-lime-text' => $r['diff'] > 0, 'text-hot' => $r['diff'] < 0, 'text-ink3' => ! $r['diff']])>{{ App\Support\Results::signed($r['diff']) }}</span>
                                    </span>
                                </div>
                                <x-results-button :play="$p" />
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-pager :page="$historyPage" noun="open plays" class="pt-0.5" />
            </section>
        @endif
    </x-page>

    <livewire:past-results />

    @if ($who)
        @include('livewire.member.partials.who')
    @endif

    @if ($sharePlay)
        @include('livewire.partials.share-link', ['manage' => false])
    @endif

    {{-- A member co-host sees the host's player list (view only). --}}
    @if ($roster)
        @include('livewire.organizer.partials.roster')
    @endif

    {{-- Pay for an open play: bottom sheet on mobile, centered on desktop. --}}
    @if ($payPlay)
        @php($sent = in_array($payPlay->id, $claimedIds))
        <div wire:key="pay-{{ $payPlay->id }}" x-data x-on:keydown.escape.window="$wire.closePay()" class="fixed inset-0 z-66 flex items-end justify-center lg:items-center lg:p-6">
            <button type="button" wire:click="closePay" aria-label="Close payment" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
            <div role="dialog" aria-modal="true" aria-labelledby="pay-title" x-trap.noscroll="true" class="relative flex max-h-full w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] lg:w-170 lg:rounded-[28px]">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="truncate font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $payPlay->title }}</span>
                        <h2 id="pay-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">Pay with {{ $payPlay->payMethodLabel() }}</h2>
                    </div>
                    <button type="button" wire:click="closePay" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97"><x-icon name="x" :size="18" /></button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-4.5 overflow-y-auto px-6 pt-5 pb-5.5">
                    <div class="flex flex-col items-center gap-5.5 lg:flex-row">
                        <div class="flex shrink-0 flex-col items-center gap-2 rounded-3xl border border-[#DEDCD0] bg-white px-3.5 pt-3.5 pb-3 shadow-[0_16px_36px_-18px_rgba(0,0,0,.35)]">
                            <img src="{{ $payPlay->payQrUrl() }}" alt="Payment QR for {{ $payPlay->pay_name }} on {{ $payPlay->payMethodLabel() }}" class="block size-55 object-contain">
                            <span class="font-mono text-[11px] font-semibold tracking-[.14em] text-on-lime uppercase">{{ $payPlay->payMethodLabel() }}</span>
                        </div>
                        <div class="flex w-full min-w-0 flex-1 flex-col gap-3.5">
                            <div class="flex flex-col gap-0.5">
                                <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Amount</span>
                                <span class="font-display text-[44px] leading-none font-extrabold tracking-[-0.04em]">{{ $payPlay->feeLabel() }}</span>
                                <span class="text-[13px] text-ink3">per player · {{ $payPlay->whenLabel() }} · {{ $payPlay->timeRange() }}</span>
                            </div>
                            <dl class="m-0 flex flex-col overflow-hidden rounded-2xl border border-line bg-surface">
                                <div class="flex items-center justify-between gap-3 border-b border-line px-3.5 py-2.75"><dt class="text-[13px] text-ink3">Send to</dt><dd class="m-0 text-right text-sm font-bold">{{ $payPlay->pay_name }}</dd></div>
                                @if ($payPlay->pay_number)
                                    <div class="flex items-center justify-between gap-3 border-b border-line py-1.75 pr-2 pl-3.5">
                                        <dt class="text-[13px] text-ink3">Number</dt>
                                        <dd class="m-0 flex items-center gap-2">
                                            <span class="font-mono text-sm font-semibold">{{ $payPlay->pay_number }}</span>
                                            <button type="button" x-on:click="navigator.clipboard?.writeText(@js($payPlay->pay_number)).catch(() => {}); $dispatch('toast', { message: 'Number copied' })" class="h-8 cursor-pointer rounded-full border-[1.5px] border-line2 px-3 text-xs font-bold text-ink transition hover:brightness-96 active:scale-97">Copy</button>
                                        </dd>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between gap-3 px-3.5 py-2.75"><dt class="text-[13px] text-ink3">Club</dt><dd class="m-0 text-right text-sm font-bold">{{ $payPlay->club }}</dd></div>
                            </dl>
                        </div>
                    </div>

                    <ol class="m-0 flex list-none flex-col gap-2.5 p-0">
                        @foreach ([
                            "Open {$payPlay->payApp()} and scan the QR. On your phone, save the image and upload it from your gallery.",
                            'Send <strong>'.e($payPlay->feeLabel()).'</strong> and check the name reads <strong>'.e($payPlay->pay_name).'</strong> before you confirm.',
                            'Keep the receipt. The organizer marks you as paid, and you can show it at check-in if needed.',
                        ] as $step)
                            <li class="flex items-start gap-3 text-sm leading-[1.45]">
                                <span aria-hidden="true" class="flex size-6 shrink-0 items-center justify-center rounded-full bg-lime text-xs font-extrabold text-on-lime">{{ $loop->iteration }}</span>
                                <span>{!! $step !!}</span>
                            </li>
                        @endforeach
                    </ol>

                    @if ($sent)
                        <div role="status" class="flex items-start gap-2.5 rounded-[14px] border border-lime bg-lime-soft px-3.5 py-3 text-sm leading-[1.45] font-semibold"><x-icon name="check" :size="18" :stroke="2.4" class="mt-px" />You told the organizer you've paid. You'll show as paid once they confirm it.</div>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
                    <a href="{{ $payPlay->payQrUrl() }}?download=1" class="inline-flex h-13 items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 px-4.5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97"><x-icon name="download" :size="18" />Save QR</a>
                    @if ($sent)
                        <button type="button" wire:click="closePay" class="h-13 min-w-45 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">Done</button>
                    @else
                        <button type="button" wire:click="claimPayment({{ $payPlay->id }})" class="h-13 min-w-45 flex-1 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">I've sent the payment</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-app-shell>
