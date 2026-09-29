{{-- Big-number stat card: label, value, one line of context. accent = lime card. --}}
@props(['label', 'value', 'sub' => null, 'accent' => false])

<div {{ $attributes->class(['flex min-w-0 flex-col gap-2 rounded-[22px] p-4 lg:p-5', 'border border-line bg-surface' => ! $accent, 'bg-lime text-on-lime' => $accent]) }}>
    <span @class(['font-mono text-[11px] tracking-widest uppercase', 'text-ink3' => ! $accent])>{{ $label }}</span>
    <span class="font-display text-[34px] leading-none font-extrabold tracking-[-0.04em] lg:text-[40px]">{{ $value }}</span>
    @if ($sub)
        <span @class(['truncate text-[13px]', 'text-ink2' => ! $accent])>{{ $sub }}</span>
    @endif
</div>
