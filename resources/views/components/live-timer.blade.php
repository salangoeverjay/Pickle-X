{{-- mm:ss that ticks up client-side, starting from $start seconds. --}}
@props(['start' => 372])

<span
    x-data="{ s: {{ (int) $start }}, t: null, init() { this.t = setInterval(() => this.s++, 1000) }, destroy() { clearInterval(this.t) } }"
    x-text="String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')"
    {{ $attributes }}
>{{ sprintf('%02d:%02d', intdiv($start, 60), $start % 60) }}</span>
