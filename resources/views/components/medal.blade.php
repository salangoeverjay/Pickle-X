{{--
    Gold / silver / bronze medal for ranks 1-3: a ribbon over a metal disc with the rank engraved.
    Scales from $size (disc diameter, px). $ring = the background it sits on, drawn as a cut-out
    ring when it overlaps an avatar. $number = false for tiny chips; $display uses the display font.
--}}
@props(['rank', 'size' => 22, 'ring' => null, 'number' => true, 'display' => false, 'label' => true])
@php
    [$bg, $rim, $ink, $aria] = [
        1 => ['radial-gradient(circle at 34% 28%, #FFF3C4 0%, #F2C94C 46%, #C8931B 100%)', '#A87812', '#5E4003', 'Gold, 1st'],
        2 => ['radial-gradient(circle at 34% 28%, #FFFFFF 0%, #DCE0D9 48%, #A2A99F 100%)', '#8C948A', '#394039', 'Silver, 2nd'],
        3 => ['radial-gradient(circle at 34% 28%, #FFDCC2 0%, #E3A57A 48%, #B06737 100%)', '#96552A', '#4F250D', 'Bronze, 3rd'],
    ][$rank];
    $stroke = $ring ? "; stroke: {$ring}; stroke-width: 5; stroke-linejoin: round; paint-order: stroke fill" : '';
    $shadow = 'inset 0 0 0 '.($size < 18 ? 1.5 : 2)."px {$rim}".($ring ? ", 0 0 0 2px {$ring}" : '').', 0 2px 6px rgba(0,0,0,.2)';
@endphp
<span @if ($label) role="img" aria-label="{{ $aria }}" @else aria-hidden="true" @endif {{ $attributes->class('inline-flex shrink-0 flex-col items-center') }}>
    <svg width="{{ round($size * 0.91) }}" height="{{ round($size * 0.79) }}" viewBox="-3 -3 66 52" aria-hidden="true" class="block overflow-visible" style="margin-bottom: -{{ round($size * 0.3) }}px">
        <path d="M5 0h19l15 46H24z" style="fill: var(--color-court){{ $stroke }}" />
        <path d="M55 0H36L21 46h15z" style="fill: var(--color-lime){{ $stroke }}" />
        <path d="M13 0h5l15 46h-5z" fill="rgba(255,255,255,.35)" />
        <path d="M47 0h-5L27 46h5z" fill="rgba(18,23,15,.18)" />
    </svg>
    <span @class(['relative flex items-center justify-center rounded-full font-extrabold', 'font-display' => $display, 'font-mono' => ! $display])
          style="width: {{ $size }}px; height: {{ $size }}px; background: {{ $bg }}; box-shadow: {{ $shadow }}; font-size: {{ round($size * 0.44) }}px; color: {{ $ink }}; text-shadow: 0 1px 0 rgba(255,255,255,.55)">
        <span aria-hidden="true" class="absolute rounded-full border border-white/65" style="inset: {{ max(2, round($size / 8.5)) }}px"></span>
        @if ($number)
            <span class="relative leading-none">{{ $rank }}</span>
        @endif
    </span>
</span>
