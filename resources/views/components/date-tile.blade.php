{{-- Calendar tile: SEP / 25 / Fri. --}}
@props(['date'])

<div {{ $attributes->merge(['class' => 'flex h-16 w-14.5 shrink-0 flex-col items-center justify-center gap-0.5 rounded-2xl border border-line bg-surface2']) }}>
    <span class="font-mono text-[10px] tracking-widest text-ink3 uppercase">{{ $date->format('M') }}</span>
    <span class="font-display text-2xl leading-none font-extrabold tracking-[-0.04em]">{{ $date->format('j') }}</span>
    <span class="text-[10px] text-ink3">{{ $date->format('D') }}</span>
</div>
