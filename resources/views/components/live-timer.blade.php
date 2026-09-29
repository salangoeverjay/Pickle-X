{{-- mm:ss that ticks up client-side, starting from $start seconds.
     Anchored to the absolute start time (not a counter), so a page restored from the
     wire:navigate history cache (browser Back) still shows the right time.
     window.serverSkew (partials/head) corrects for a device clock that is off. --}}
@props(['start' => 372])

@php($since = now()->timestamp - (int) $start)

<span
    x-data="{ since: {{ $since }}, s: {{ (int) $start }}, t: null,
        tick() { this.s = Math.max(0, Math.floor(Date.now() / 1000 - (window.serverSkew || 0) - this.since)) },
        init() { this.tick(); this.t = setInterval(() => this.tick(), 1000) },
        destroy() { clearInterval(this.t) } }"
    x-text="String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')"
    {{ $attributes }}
>{{ sprintf('%02d:%02d', intdiv($start, 60), $start % 60) }}</span>
