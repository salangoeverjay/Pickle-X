{{-- Member-facing open play card (live or upcoming) with join / leave. --}}
@props(['play', 'joined' => false])

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

    <div class="flex flex-col gap-2 text-sm text-ink2">
        <span class="flex items-center gap-2"><x-icon name="pin" :size="17" :stroke="1.9" />{{ $play->venue }}</span>
        <span class="flex items-center gap-2"><x-icon name="clock" :size="17" :stroke="1.9" />{{ $play->isLive() ? $play->whenLabel().' · ' : '' }}{{ $play->timeRange() }}</span>
        <span class="flex items-center gap-2"><x-icon name="court" :size="17" :stroke="1.9" />{{ $play->courts }} courts · Race to {{ $play->race_to }} · {{ $play->feeLabel() }}</span>
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

    @if ($joined)
        <button type="button" wire:click="leave({{ $play->id }})" aria-pressed="true" class="h-12 cursor-pointer rounded-full border-[1.5px] border-line2 bg-transparent text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">You're in · Leave</button>
    @else
        <button type="button" wire:click="join({{ $play->id }})" aria-pressed="false" @disabled($full) class="h-12 cursor-pointer rounded-full border-[1.5px] border-lime bg-lime text-[15px] font-bold text-on-lime transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-60">
            {{ $full ? 'Full' : ($play->isLive() ? 'Join and get in line' : 'Join open play') }}
        </button>
    @endif
</div>
