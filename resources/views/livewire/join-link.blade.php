{{-- The page a share link opens: the open play's details, who's going, and how to join. --}}
@php($user = auth()->user())
@php($home = $user ? route($user->homeRoute()) : route('home'))
@php($homeLabel = $user ? ($user->isOrganizer() ? 'Back to open plays' : 'Go to PickleX') : 'See what PickleX is')
<div class="min-h-screen animate-screen bg-bg text-ink">
    <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-line bg-glass px-4 backdrop-blur-[14px] lg:h-19 lg:px-14">
        <a href="{{ route('home') }}" wire:navigate aria-label="PickleX home" class="text-ink"><x-logo class="text-2xl" /></a>
        <div class="flex items-center gap-2">
            <x-theme-toggle />
            @if ($user)
                <a href="{{ $home }}" wire:navigate class="inline-flex h-11 items-center rounded-full border-[1.5px] border-line2 px-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">Go to PickleX</a>
            @else
                <button type="button" wire:click="login" class="inline-flex h-11 cursor-pointer items-center rounded-full border-[1.5px] border-line2 px-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">Log in</button>
            @endif
        </div>
    </header>

    <div id="main" tabindex="-1">
        @if (! $play)
            <main class="mx-auto max-w-160 px-4 pt-4 pb-12 lg:px-14 lg:pt-12 lg:pb-18">
                <div class="flex flex-col items-start gap-3.5 rounded-[28px] border-[1.5px] border-dashed border-line2 bg-surface p-8">
                    <span aria-hidden="true" class="flex size-13 items-center justify-center rounded-2xl bg-hot-soft text-hot"><x-icon name="link-off" :size="24" /></span>
                    <h1 class="m-0 font-display text-[30px] leading-[1.1] font-extrabold tracking-[-0.03em]">This link doesn't work anymore</h1>
                    <p class="m-0 text-[15px] leading-normal text-ink2">The organizer may have reset it or deleted the open play. Ask them for a new link.</p>
                    <a href="{{ $home }}" wire:navigate class="inline-flex h-13 items-center justify-center rounded-full bg-ink px-5.5 text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">{{ $homeLabel }}</a>
                </div>
            </main>
        @else
            @php($invite = $play->visibility === 'invite')
            @php($host = $play->organizer)
            @php($hosted = $host ? $host->openPlays()->status('done')->count() : 0)
            @php($done = in_array($state, ['in', 'guestIn']))
            <main class="mx-auto grid max-w-295 grid-cols-1 items-start gap-3.5 px-4 pt-4 pb-12 [grid-template-areas:'a'_'p'_'b'] lg:grid-cols-[minmax(0,1.12fr)_minmax(0,1fr)] lg:gap-x-7 lg:gap-y-5 lg:px-14 lg:pt-12 lg:pb-18 lg:[grid-template-areas:'a_p'_'b_p']">
                <section aria-labelledby="jn-title" class="flex flex-col gap-5 rounded-[28px] border border-line bg-surface p-5 shadow-card [grid-area:a] lg:p-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <span @class(['inline-flex h-7 items-center gap-1.5 rounded-full px-2.75 text-xs font-extrabold', 'bg-ink text-bg' => $invite, 'bg-lime-soft text-lime-text' => ! $invite])>
                            @if ($invite)
                                <x-icon name="lock" :size="13" :stroke="2.2" />
                            @endif
                            {{ $state === 'mine' ? ($invite ? 'Invite only · link' : 'Open to everyone') : ($invite ? "Invite only · you're invited" : 'Open play · anyone can join') }}
                        </span>
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $play->club }}</span>
                    </div>
                    <h1 id="jn-title" class="m-0 font-display text-[34px] leading-none font-extrabold tracking-[-0.04em] lg:text-[58px]">{{ $play->title }}</h1>
                    @if ($host)
                        <div class="flex min-w-0 items-center gap-2.75">
                            <x-avatar :src="$host->avatarUrl()" :frame="$host->frameClass()" :name="$host->shownName()" :color="$host->color()" class="ml-0.5 size-9.5 text-xs" aria-hidden="true" />
                            <span class="flex min-w-0 flex-col gap-px">
                                <span class="text-sm text-ink2">Hosted by <strong class="text-ink">{{ $host->shownName() }}</strong></span>
                                <span class="truncate text-xs text-ink3">Approved organizer · {{ $hosted ? $hosted.' '.Str::plural('open play', $hosted).' hosted' : 'New organizer' }}</span>
                            </span>
                        </div>
                    @endif
                    <div class="flex flex-col gap-3.5 rounded-[20px] border border-line bg-surface2 p-4">
                        @foreach ([
                            ['calendar', $play->starts_at->isToday() ? 'Today' : $play->starts_at->format('l, M j'), $play->timeRange()],
                            ['pin', $play->venue, $play->club],
                            ['court', $play->fee ? $play->feeLabel().' per player' : 'Free', $play->courts.' '.Str::plural('court', $play->courts).' · race to '.$play->race_to.' · '.($play->rotation === 'winners' ? 'winners stay' : 'everyone rotates').($play->hasPaymentQr() ? ' · pay by '.$play->payMethodLabel() : '')],
                        ] as [$icon, $a, $b])
                            <span class="flex items-start gap-2.5">
                                <x-icon :name="$icon" :size="18" class="mt-px shrink-0 text-ink3" />
                                <span class="flex min-w-0 flex-col gap-px"><span class="text-[15px] font-bold">{{ $a }}</span><span class="text-[13px] text-ink2">{{ $b }}</span></span>
                            </span>
                        @endforeach
                    </div>
                </section>

                <section aria-label="Who's going" class="rounded-3xl border border-line bg-surface px-4.5 py-4 [grid-area:b] lg:px-8 lg:py-5">
                    <div class="flex flex-col gap-2.5">
                        <div class="flex items-center justify-between gap-2.5 text-sm"><span class="font-bold">{{ $count }} of {{ $play->cap }} joined</span><span class="font-mono text-ink3">{{ $left ? $left.' '.($left === 1 ? 'spot left' : 'spots left') : 'Full' }}</span></div>
                        <div class="h-2 overflow-hidden rounded-sm bg-surface2"><div class="h-full rounded-sm bg-ink transition-[width] duration-700" style="width: {{ min(100, round($count / max(1, $play->cap) * 100)) }}%"></div></div>
                        @if ($faces->isNotEmpty())
                            <div class="flex items-center gap-2.5">
                                <span aria-hidden="true" class="flex pl-0.5">
                                    @foreach ($faces as ['p' => $p])
                                        <x-avatar :src="$seesNames ? $p->avatarUrl() : null" :frame="$p->frameClass()" :name="$p->name" :color="$p->user ? $p->color() : '#E6E3D6'" class="-mr-2.25 size-8.5 border-2 border-surface text-[11px]" />
                                    @endforeach
                                </span>
                                <span class="ml-2.5 text-[13px] text-ink2">{{ $seesNames ? ($count > 6 ? 'and '.($count - 6).' more' : '') : 'Names show once you join' }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                <section aria-live="polite" aria-label="Join" @class(['flex flex-col gap-4 rounded-[28px] border-[1.5px] p-5 [grid-area:p] lg:p-8', 'border-lime bg-lime-soft' => $done, 'border-line bg-surface' => ! $done])>
                    @switch ($state)
                        @case('choose')
                            <div class="flex flex-col gap-1.5">
                                <h2 class="m-0 font-display text-[28px] leading-[1.05] font-extrabold tracking-[-0.03em]">Join this open play</h2>
                                <p class="m-0 text-[15px] leading-normal text-ink2">{{ $play->isLive() ? "It's on now. " : '' }}Pick how you want to join. It takes less than a minute.</p>
                            </div>
                            <div class="flex flex-col gap-2.5">
                                @php($ways = [])
                                @if ($play->link_guests && ! $play->isLive())
                                    @php($ways[] = ['pickGuest', 'user', 'bg-lime text-on-lime', 'Join as a guest', 'Just your name. No account needed.'])
                                @endif
                                @php($ways[] = ['signup', 'user-plus', 'bg-court text-white', 'Create an account and join', 'Free. Your games count toward rankings and Played with.'])
                                @php($ways[] = ['login', 'login', 'bg-surface2 text-ink', 'I already have an account', "Log in and you're on the list straight away."])
                                @foreach ($ways as [$action, $icon, $tone, $label, $desc])
                                    <button type="button" wire:click="{{ $action }}" class="flex w-full cursor-pointer items-center gap-3.5 rounded-[20px] border-[1.5px] border-line bg-surface p-3.5 text-left text-ink transition hover:brightness-96 active:scale-99">
                                        <span aria-hidden="true" class="flex size-11.5 shrink-0 items-center justify-center rounded-[14px] {{ $tone }}"><x-icon :name="$icon" :size="22" /></span>
                                        <span class="flex min-w-0 flex-1 flex-col gap-0.75"><span class="text-base font-extrabold">{{ $label }}</span><span class="text-[13px] leading-[1.4] text-ink2">{{ $desc }}</span></span>
                                        <x-icon name="chevron-right" :size="18" class="shrink-0 text-ink3" />
                                    </button>
                                @endforeach
                            </div>
                            @if (! $play->link_guests || $play->isLive())
                                <p class="m-0 flex items-start gap-2 text-[13px] leading-[1.45] text-ink2"><x-icon name="alert" :size="16" class="mt-px shrink-0" />
                                    <span>{{ ! $play->link_guests ? 'The organizer asks everyone to join with a PickleX account.' : "It's on now. Without an account, tell the organizer at the courts and they'll add you as a walk-in." }}</span>
                                </p>
                            @endif
                            @break

                        @case('guest')
                            <button type="button" wire:click="back" class="inline-flex h-10 cursor-pointer items-center gap-1.5 self-start rounded-full pr-3 pl-1.5 text-sm font-bold text-ink2 transition hover:text-ink"><x-icon name="chevron-left" :size="18" />Other ways to join</button>
                            <form wire:submit="joinGuest" novalidate class="flex flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <h2 class="m-0 font-display text-[28px] leading-[1.05] font-extrabold tracking-[-0.03em]">Join as a guest</h2>
                                    <p class="m-0 text-[15px] leading-normal text-ink2">The organizer sees your name on their player list. That's all you need at check-in.</p>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="jn-name" class="text-[13px] font-bold text-ink2">Your name</label>
                                    <input id="jn-name" type="text" wire:model="name" autocomplete="name" autocapitalize="words" maxlength="60" placeholder="First and last name" aria-describedby="jn-err" @if ($error && str_contains(strtolower($error), 'name')) aria-invalid="true" @endif class="input">
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="jn-email" class="text-[13px] font-bold text-ink2">Email <span class="font-medium text-ink3">(optional)</span></label>
                                    <input id="jn-email" type="email" wire:model="email" autocomplete="email" placeholder="you@example.com" aria-describedby="jn-email-hint" class="input">
                                    <span id="jn-email-hint" class="text-[13px] leading-[1.4] text-ink3">We'll email you the details and any changes. Nothing else.</span>
                                </div>
                                @if ($error)
                                    <p id="jn-err" role="alert" class="m-0 text-sm font-semibold text-hot">{{ $error }}</p>
                                @endif
                                <button type="submit" class="inline-flex h-13 cursor-pointer items-center justify-center rounded-full border-2 border-cta-border bg-lime px-5.5 text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Join as a guest</button>
                                <p class="m-0 text-[13px] leading-[1.45] text-ink3">Games you play as a guest don't count toward rankings. You can create an account any time and keep your spot.</p>
                            </form>
                            @break

                        @case('in')
                        @case('guestIn')
                            @php($first = $state === 'guestIn' ? strtok($guest->name, ' ') : $user->firstName())
                            <span aria-hidden="true" class="flex size-15 animate-snap items-center justify-center rounded-full border-2 border-cta-border bg-lime text-on-lime"><x-icon name="check" :size="28" :stroke="2.6" /></span>
                            <div class="flex flex-col gap-1.5">
                                <h2 class="m-0 font-display text-[30px] leading-[1.05] font-extrabold tracking-[-0.03em]">{{ $state === 'guestIn' || $just ? "You're in, {$first}." : "You're already in" }}</h2>
                                <p class="m-0 text-[15px] leading-normal text-ink2">
                                    {{ $state === 'guestIn'
                                        ? "You're on ".($host ? $host->firstName()."'s" : 'the').' list as a guest. Give your name at check-in on '.$play->whenLabel().'.'
                                        : 'See you '.($play->starts_at->isToday() ? 'today' : $play->whenLabel()).' at '.$play->starts_at->format('g:i A').'. Check in when you get to the courts.' }}
                                </p>
                            </div>
                            @php($mail = $state === 'guestIn' ? $guest->email : ($just ? $user->email : null))
                            @if ($mail)
                                <p class="m-0 flex items-start gap-2 text-sm leading-[1.45]"><x-icon name="mail" :size="17" class="mt-px shrink-0" /><span>We emailed the details to {{ $mail }}.</span></p>
                            @endif
                            <div class="flex flex-wrap gap-2.5">
                                @if ($owes)
                                    <button type="button" x-data x-on:click="$dispatch('open-join-pay')" class="inline-flex h-13 cursor-pointer items-center justify-center gap-2 rounded-full bg-ink px-5.5 text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97"><x-icon name="qr" :size="18" />Pay {{ $play->feeLabel() }}</button>
                                @endif
                                @if ($state === 'in')
                                    <a href="{{ route('member.plays') }}" wire:navigate class="inline-flex h-13 items-center justify-center rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">See my open plays</a>
                                @endif
                            </div>
                            @if ($state === 'guestIn')
                                <div class="flex flex-col gap-2.5 rounded-[20px] border border-line bg-surface p-4">
                                    <strong class="text-base">Keep your games</strong>
                                    <span class="text-sm leading-[1.45] text-ink2">Create a free account and this spot moves to it. Your games, partners and rankings start counting.</span>
                                    <button type="button" wire:click="signup" class="inline-flex h-11.5 cursor-pointer items-center gap-2 self-start rounded-full border-[1.5px] border-line2 px-4.5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97"><x-icon name="user-plus" :size="17" />Create my account</button>
                                </div>
                            @endif
                            <button type="button" wire:click="leave" class="h-11 cursor-pointer self-start px-0.5 text-sm font-semibold text-ink2 underline underline-offset-3">Leave this open play</button>
                            @break

                        @case('out')
                            <div class="flex flex-col gap-1.5">
                                <h2 class="m-0 font-display text-[28px] leading-[1.05] font-extrabold tracking-[-0.03em]">Join this open play</h2>
                                <p class="m-0 text-[15px] leading-normal text-ink2">You're logged in as {{ $user->name }}. One tap and you're on the list.</p>
                            </div>
                            <button type="button" wire:click="join" class="inline-flex h-13 cursor-pointer items-center justify-center rounded-full border-2 border-cta-border bg-lime px-5.5 text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Join as {{ $user->firstName() }}</button>
                            @break

                        @case('mine')
                            <span aria-hidden="true" class="flex size-13 items-center justify-center rounded-2xl bg-surface2"><x-icon name="link" :size="24" /></span>
                            <div class="flex flex-col gap-1.5">
                                <h2 class="m-0 font-display text-[28px] leading-[1.05] font-extrabold tracking-[-0.03em]">This is your open play</h2>
                                <p class="m-0 text-[15px] leading-normal text-ink2">People who open this link see these details. They can join as a guest{{ $play->link_guests ? '' : ' (off right now)' }}, create an account, or log in, and anyone already logged in is added straight away.</p>
                            </div>
                            <a href="{{ route('organizer.plays', ['share' => $play->id]) }}" wire:navigate class="inline-flex h-13 items-center justify-center gap-2 self-start rounded-full bg-ink px-5.5 text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97"><x-icon name="link" :size="17" />Back to open plays</a>
                            @break

                        @default
                            <span aria-hidden="true" class="flex size-13 items-center justify-center rounded-2xl bg-hot-soft text-hot"><x-icon name="ban" :size="24" /></span>
                            <div class="flex flex-col gap-1.5">
                                <h2 class="m-0 font-display text-[28px] leading-[1.05] font-extrabold tracking-[-0.03em]">{{ $state === 'ended' ? 'This open play has ended' : 'This open play is full' }}</h2>
                                <p class="m-0 text-[15px] leading-normal text-ink2">{{ $state === 'ended' ? "Look out for the next one from {$play->club}." : "All {$play->cap} spots are taken. Ask ".($host?->firstName() ?? 'the organizer').' if one opens up.' }}</p>
                            </div>
                            <a href="{{ $home }}" wire:navigate class="inline-flex h-13 items-center justify-center self-start rounded-full bg-ink px-5.5 text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">{{ $homeLabel }}</a>
                    @endswitch
                </section>
            </main>

            {{-- Pay the fee: the organizer's QR, for players and guests. --}}
            @if ($owes)
                <div x-data="{ open: false }" x-on:open-join-pay.window="open = true" x-on:keydown.escape.window="open = false">
                    <template x-if="open">
                        <div class="fixed inset-0 z-66 flex items-end justify-center lg:items-center lg:p-6">
                            <button type="button" x-on:click="open = false" aria-label="Close payment" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
                            <div role="dialog" aria-modal="true" aria-labelledby="jn-pay-title" x-trap.noscroll="open" class="relative flex max-h-full w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] lg:w-170 lg:rounded-[28px]">
                                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                                    <div class="flex min-w-0 flex-col gap-0.5">
                                        <span class="truncate font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $play->title }}</span>
                                        <h2 id="jn-pay-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">Pay with {{ $play->payMethodLabel() }}</h2>
                                    </div>
                                    <button type="button" x-on:click="open = false" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97"><x-icon name="x" :size="18" /></button>
                                </div>
                                <div class="flex min-h-0 flex-1 flex-col items-center gap-4.5 overflow-y-auto px-6 pt-5 pb-5.5 lg:flex-row">
                                    <div class="flex shrink-0 flex-col items-center gap-2 rounded-3xl border border-[#DEDCD0] bg-white px-3.5 pt-3.5 pb-3 shadow-[0_16px_36px_-18px_rgba(0,0,0,.35)]">
                                        <img src="{{ $play->payQrUrl() }}" alt="Payment QR for {{ $play->pay_name }} on {{ $play->payMethodLabel() }}" class="block size-55 object-contain">
                                        <span class="font-mono text-[11px] font-semibold tracking-[.14em] text-on-lime uppercase">{{ $play->payMethodLabel() }}</span>
                                    </div>
                                    <dl class="m-0 flex w-full flex-col overflow-hidden rounded-2xl border border-line bg-surface">
                                        @foreach (array_filter(['Amount' => $play->feeLabel().' per player', 'Send to' => $play->pay_name, 'Number' => $play->pay_number, 'Club' => $play->club]) as $dt => $dd)
                                            <div class="flex items-center justify-between gap-3 border-b border-line px-3.5 py-2.75 last:border-b-0"><dt class="text-[13px] text-ink3">{{ $dt }}</dt><dd class="m-0 text-right text-sm font-bold">{{ $dd }}</dd></div>
                                        @endforeach
                                    </dl>
                                </div>
                                <div class="flex shrink-0 flex-wrap gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
                                    <a href="{{ $play->payQrUrl() }}?download=1" class="inline-flex h-13 items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 px-4.5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97"><x-icon name="download" :size="18" />Save QR</a>
                                    <button type="button" x-on:click="open = false" class="h-13 min-w-45 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">Done</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endif
        @endif
    </div>
    <x-toast />
</div>
