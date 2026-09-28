{{-- mm:ss that ticks up client-side, starting from $start seconds. --}}
@props(['start' => 372])

<span
    x-data="{ s: {{ (int) $start }} }"
    x-init="setInterval(() => s++, 1000)"
    x-text="String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')"
    {{ $attributes }}
>{{ sprintf('%02d:%02d', intdiv($start, 60), $start % 60) }}</span>
