{{--
    Top-down pickleball court with four bobbing players and a bouncing ball. Size it from the caller.
    players: [[initials, color, ring?], ...] in spot order: team A top, A bottom, B top, B bottom.
    Default slot renders on top of the court (badges, lock markers, "open" overlay).
--}}
@props(['players' => [], 'playerClass' => 'size-8 text-[11px]', 'delay' => 0, 'label' => 'Animated court with four players rallying'])

@php
    // Fixed spots (left, top) and bob offsets for the four players.
    $spots = [['21%', '31%', 0, 0], ['21%', '69%', -.6, 0], ['79%', '31%', -.3, -1.4], ['79%', '69%', -.9, -1.4]];
@endphp

<div role="img" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'overflow-hidden bg-court-out']) }}>
    <div class="absolute inset-x-[7%] inset-y-[11%] bg-court shadow-[0_0_0_3px_rgba(255,255,255,.92)]">
        <div class="absolute inset-y-0 left-[34%] w-[32%] border-x-[3px] border-white/92 bg-court-in"></div>
        <div class="absolute top-1/2 left-0 -mt-[1.5px] h-0.75 w-[34%] bg-white/92"></div>
        <div class="absolute top-1/2 right-0 -mt-[1.5px] h-0.75 w-[34%] bg-white/92"></div>
        <div class="absolute inset-y-[-10%] left-1/2 -ml-[2.5px] w-1.25 rounded-[3px] bg-[#F4F4EC] shadow-[0_0_0_1px_rgba(0,0,0,.25),4px_0_8px_rgba(0,0,0,.3)]"></div>
    </div>

    {{ $slot }}

    @foreach ($players as $i => $player)
        @php([$initials, $color, $ring] = $player + [2 => '#FFFFFF'])
        <div class="court-player absolute flex items-center justify-center rounded-full border-[3px] font-extrabold text-on-lime {{ $playerClass }}"
             style="left: {{ $spots[$i][0] }}; top: {{ $spots[$i][1] }}; background: {{ $color }}; border-color: {{ $ring }}; animation-delay: {{ $spots[$i][2] + $delay }}s, {{ $spots[$i][3] }}s">{{ $initials }}</div>
    @endforeach

    @if ($players)
        <div class="ball" style="animation-delay: {{ $delay }}s"><div class="ball-shadow"></div><div class="ball-body"></div></div>
    @endif
</div>
