{{-- mm:ss that ticks up client-side, starting from $start seconds.
     A negative $start counts down first (a court still warming up: the game clock starts at 0:00),
     shown as $before + mm:ss + $after; $refresh re-renders the Livewire component when it reaches 0:00.
     Anchored to the absolute start time (not a counter), so a page restored from the
     wire:navigate history cache (browser Back) still shows the right time.
     window.serverSkew (partials/head) corrects for a device clock that is off. --}}
@props(['start' => 372, 'before' => '', 'after' => '', 'refresh' => false])

@php
    $start = (int) $start;
    $since = now()->timestamp - $start;
    $mmss = fn ($s) => sprintf('%02d:%02d', intdiv(abs($s), 60), abs($s) % 60);
@endphp

<span
    x-data="{ since: {{ $since }}, s: {{ $start }}, t: null,
        tick() {
            const was = this.s
            this.s = Math.floor(Date.now() / 1000 - (window.serverSkew || 0) - this.since)
            @if ($refresh) if (was < 0 && this.s >= 0) $wire.$refresh() @endif
        },
        init() { this.tick(); this.t = setInterval(() => this.tick(), 1000) },
        destroy() { clearInterval(this.t) } }"
    x-text="(s < 0 ? @js($before) : '') + String(Math.floor(Math.abs(s) / 60)).padStart(2, '0') + ':' + String(Math.abs(s) % 60).padStart(2, '0') + (s < 0 ? @js($after) : '')"
    {{ $attributes }}
>{{ ($start < 0 ? $before : '').$mmss($start).($start < 0 ? $after : '') }}</span>
