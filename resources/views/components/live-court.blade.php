{{--
    One court from a live board. Shows "COURT n · mm:ss" when $badge, lock markers for locked teams,
    and an "open" overlay when the court is free. $me gets a lime ring.
--}}
@props(['play', 'court', 'me' => null, 'badge' => true, 'locks' => false, 'index' => 0, 'playerClass' => 'size-9 text-[11px]'])

@php
    $busy = (bool) $court['a'];
    $players = $busy ? collect([...$court['a']['ids'], ...$court['b']['ids']])->map(function ($id) use ($play, $me) {
        $p = $play->participant($id);

        return [$p?->initials() ?? '?', $p?->color() ?? '#FFFFFF', $me && $p?->user_id === $me->id ? 'var(--color-lime)' : '#FFFFFF', $p?->avatarUrl(), $p?->frameClass() ?? ''];
    })->all() : [];
    $elapsed = $busy ? now()->timestamp - $court['started_at'] : 0;
    $label = $busy
        ? "Court {$court['n']}: {$play->names($court['a']['ids'])} versus {$play->names($court['b']['ids'])}"
        : "Court {$court['n']} is open";
@endphp

<x-court {{ $attributes->merge(['class' => 'relative rounded-[20px]']) }} :players="$players" :player-class="$playerClass" :delay="-($index * 0.37 + 0.15)" :label="$label">
    @if ($busy && $locks)
        @foreach (['a' => '21%', 'b' => '79%'] as $side => $x)
            @if ($court[$side]['locked'])
                <div class="absolute top-[31%] h-[38%] border-l-2 border-dashed border-lime" style="left: {{ $x }}"></div>
                <div class="absolute top-1/2 z-3 -mt-2.75 -ml-2.75 flex size-5.5 items-center justify-center rounded-full border-[1.5px] border-on-lime bg-lime text-on-lime" style="left: {{ $x }}">
                    <x-icon name="lock" :size="12" :stroke="2.4" />
                </div>
            @endif
        @endforeach
    @endif

    @unless ($busy)
        <div class="absolute inset-0 flex items-center justify-center bg-[rgba(8,20,14,.32)]">
            <span class="inline-flex h-9 items-center gap-2 rounded-full bg-white/96 px-3.5 text-sm font-extrabold text-on-lime"><x-icon name="plus" :size="16" />Court open</span>
        </div>
    @endunless

    @if ($busy && $badge)
        <span class="absolute top-2.5 left-2.5 z-1 inline-flex h-6 items-center gap-1.5 rounded-full bg-black/38 px-2.25 font-mono text-[11px] tracking-[.08em] text-white">
            COURT {{ $court['n'] }} · <x-live-timer :start="$elapsed" before="WARMUP " />
        </span>
    @endif
</x-court>
