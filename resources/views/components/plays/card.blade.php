{{-- Member-facing open play card (live or upcoming) with join / leave. --}}
@props(['play', 'joined' => false, 'paid' => false, 'claimed' => false])

@php
    $count = $play->playerCount();
    $left = max(0, $play->cap - $count);
    $full = $left === 0 && ! $joined;
    $pct = min(100, round($count / max(1, $play->cap) * 100));
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3.5 rounded-3xl border border-line bg-surface p-4.5 transition duration-300 hover:-translate-y-1 hover:shadow-card lg:p-6']) }}>
    <div class="flex items-center justify-between gap-2.5">
        <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $play->club }}</span>
        @if ($play->isLive())
            <span class="inline-flex h-6.5 items-center gap-1.5 rounded-full bg-ink px-2.5 font-mono text-[11px] tracking-[.08em] text-bg">
                <span class="size-1.5 animate-live rounded-full bg-lime"></span>LIVE
            </span>
        @else
            <span class="inline-flex h-6.5 items-center rounded-full border border-line bg-surface2 px-2.5 text-xs font-bold">{{ $play->whenLabel() }}</span>
        @endif
    </div>

    <h3 class="m-0 font-display text-[23px] leading-[1.1] font-bold tracking-[-0.03em]">{{ $play->title }}</h3>

    @if ($host = $play->organizer)
        @php($mine = $host->is(auth()->user()))
        @php($n = (int) ($host->hosted_count ?? $host->openPlays()->status('done')->count()))
        @php($hosted = $n ? $n.' '.Str::plural('open play', $n).' hosted' : 'New organizer')
        <div role="group" aria-label="Hosted by {{ $mine ? 'you' : $host->shownName() }}, approved organizer, {{ $hosted }}" class="flex min-w-0 items-center gap-2.75">
            <x-avatar :src="$host->avatarUrl()" :frame="$host->frameClass()" :name="$host->shownName()" :color="$host->color()" class="ml-0.5 size-9 text-xs" aria-hidden="true" />
            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <span class="truncate text-sm text-ink2">Hosted by <strong class="font-bold text-ink">{{ $mine ? 'you' : $host->shownName() }}</strong></span>
                <span class="flex min-w-0 items-center gap-1.25 text-xs text-ink3"><svg class="shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.8l2.3 1.7 2.9.1.9 2.7 2.3 1.8-.8 2.8.8 2.8-2.3 1.8-.9 2.7-2.9.1L12 21.2l-2.3-1.7-2.9-.1-.9-2.7-2.3-1.8.8-2.8-.8-2.8 2.3-1.8.9-2.7 2.9-.1Z" fill="var(--color-lime)" stroke="var(--color-cta-border)" stroke-width="1.6" /><path d="M8.4 12.2l2.4 2.4 4.8-4.9" stroke="#12170F" stroke-width="2.6" /></svg><span class="truncate">{{ $mine ? "You're the organizer, {$hosted}" : "Approved organizer, {$hosted}" }}</span></span>
            </div>
        </div>
    @endif

    @php($cos = $play->cohosts)
    @php($meCo = $cos->contains(auth()->user()))
    @if ($cos->isNotEmpty() || $meCo)
        <div class="flex flex-wrap items-center gap-2">
            @if ($meCo)
                <span class="inline-flex h-7 items-center gap-1.5 rounded-full bg-ink px-2.75 text-xs font-bold text-bg"><x-icon name="users" :size="14" />You're a co-host, hosted by {{ $host?->shownName() }}</span>
            @endif
            @if ($cos->isNotEmpty())
                <span class="text-[13px] text-ink3">With {{ Str::plural('co-host', $cos->count()) }} {{ $cos->map(fn ($c) => $c->is(auth()->user()) ? 'you' : $c->shownName())->join(', ') }}</span>
            @endif
        </div>
    @endif

    <div class="flex flex-col gap-2 text-sm text-ink2">
        <span class="flex items-center gap-2"><x-icon name="pin" :size="17" :stroke="1.9" />{{ $play->venue }}</span>
        <span class="flex items-center gap-2"><x-icon name="clock" :size="17" :stroke="1.9" />{{ $play->isLive() ? $play->whenLabel().', ' : '' }}{{ $play->timeRange() }}</span>
        <span class="flex items-center gap-2"><x-icon name="court" :size="17" :stroke="1.9" />{{ $play->courts }} {{ Str::plural('court', $play->courts) }}, Race to {{ $play->race_to }}, {{ $play->feeLabel() }}</span>
    </div>

    <div class="flex flex-col gap-2">
        <div class="flex justify-between text-[13px]">
            <span class="font-semibold">{{ $count }} of {{ $play->cap }} players</span>
            <span class="font-mono text-ink3">{{ $full ? 'Full' : $left.($left === 1 ? ' spot left' : ' spots left') }}</span>
        </div>
        <div class="h-2 overflow-hidden rounded-sm bg-surface2">
            <div class="h-full rounded-sm bg-ink transition-[width] duration-700" style="width: {{ $pct }}%"></div>
        </div>
    </div>

    {{-- Who's in (upcoming) or here (live): the first five faces, then the dialog. A member co-host gets the host's player list. --}}
    @php($coList = $meCo && $play->status === 'upcoming')
    <button type="button" wire:click="{{ $coList ? 'openRoster' : 'openWho' }}({{ $play->id }})" @disabled(! $count)
            aria-label="{{ $coList ? 'See the player list for' : ($play->isLive() ? 'See who is here at' : 'See who joined') }} {{ $play->title }}"
            class="inline-flex min-h-11.5 max-w-full min-w-0 cursor-pointer items-center gap-2.5 self-stretch rounded-full border-[1.5px] border-line2 bg-bg py-1.25 pr-4 pl-1.5 text-ink transition hover:brightness-96 active:scale-97 disabled:cursor-default disabled:hover:brightness-100 disabled:active:scale-100 lg:self-start">
        @if ($count)
            <span aria-hidden="true" class="flex pr-0.5">
                @foreach ($play->whoIsIn()->take(5) as ['p' => $p])
                    <x-avatar :src="$p->avatarUrl()" :frame="$p->frameClass()" :name="$p->name" :color="$p->user ? $p->color() : '#E6E3D6'" class="-mr-2.25 size-8.5 border-2 border-bg text-[11px]" />
                @endforeach
                @if ($count > 5)
                    <span class="flex h-8.5 min-w-8.5 items-center justify-center rounded-full border-2 border-bg bg-ink px-1.5 font-mono text-[11px] font-semibold text-bg">+{{ $count - 5 }}</span>
                @endif
            </span>
        @endif
        <span class="ml-1.5 min-w-0 flex-1 truncate text-left text-sm font-bold">{{ ! $count ? 'No one yet' : ($play->isLive() ? "See who's here" : ($coList ? 'See the player list' : "See who's in")) }}</span>
        <x-icon name="chevron-right" :size="16" :stroke="2.2" />
    </button>

    <div class="flex flex-wrap gap-2">
        {{-- A member co-host starts it, or opens the court board once it's live. --}}
        @if ($meCo && $play->status === 'upcoming')
            <button type="button" wire:click="startSession({{ $play->id }})" class="inline-flex h-12 basis-full cursor-pointer items-center justify-center gap-2 rounded-full bg-ink px-4.5 text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97"><x-icon name="court" :size="16" />Start session</button>
        @elseif ($meCo && $play->isLive())
            <a href="{{ route('organizer.board') }}" wire:navigate class="inline-flex h-12 basis-full items-center justify-center gap-2 rounded-full bg-ink px-4.5 text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97"><x-icon name="court" :size="16" />Open court board</a>
        @endif
        @if ($joined)
            <button type="button" wire:click="leavePlay({{ $play->id }})" aria-pressed="true" class="h-12 min-w-40 flex-1 cursor-pointer rounded-full border-[1.5px] border-line2 bg-transparent text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">You're in, Leave</button>
        @else
            <button type="button" wire:click="joinPlay({{ $play->id }})" aria-pressed="false" @disabled($full) class="h-12 min-w-40 flex-1 cursor-pointer rounded-full border-[1.5px] border-lime bg-lime text-[15px] font-bold text-on-lime transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-60">
                {{ $full ? 'Full' : ($play->isLive() ? 'Join and get in line' : 'Join open play') }}
            </button>
        @endif
        @if ($play->hasPaymentQr() && $paid)
            <span class="inline-flex h-12 items-center gap-1.5 rounded-full bg-lime-soft px-4 text-sm font-extrabold text-lime-text"><x-icon name="check" :size="17" :stroke="2.4" />Paid</span>
        @elseif ($play->hasPaymentQr())
            <button type="button" wire:click="openPay({{ $play->id }})" @class([
                'inline-flex h-12 cursor-pointer items-center justify-center gap-2 rounded-full border-[1.5px] px-4.5 text-sm font-bold transition hover:brightness-96 active:scale-97',
                'border-line2 bg-transparent text-ink' => $claimed,
                'border-ink bg-ink text-bg' => ! $claimed,
            ])><x-icon name="qr" :size="18" />{{ $claimed ? 'Payment sent, view QR' : 'Pay '.$play->feeLabel() }}</button>
        @endif
        @if ($play->status === 'upcoming' && ($play->visibility === 'public' || $meCo))
            <button type="button" wire:click="openShare({{ $play->id }})" aria-label="Share a link to {{ $play->title }}" aria-haspopup="dialog" title="Share link"
                    class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-transparent text-ink transition hover:brightness-96 active:scale-97"><x-icon name="link" :size="17" /></button>
        @endif
        @if ($play->isLive() && $play->share_code)
            <a href="{{ route('live', $play->share_code) }}" wire:navigate class="inline-flex h-12 items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 px-4.5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                <x-icon name="eye" :size="18" />Watch live
            </a>
        @endif
    </div>
</div>
