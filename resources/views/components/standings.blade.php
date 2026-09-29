{{--
    Tonight's standings: podium for the top 3 plus every player's stats. Rows come from OpenPlay::standings().
    $me highlights the viewer's row (participant id); $fresh are participant ids that just moved onto the podium.
--}}
@props(['play', 'rows', 'me' => null, 'fresh' => [], 'heading' => 'standings-title', 'title' => "Tonight's standings", 'plain' => false])

@php
    $games = $play->games()->count();
    $diff = fn ($n) => ($n > 0 ? '+' : ($n < 0 ? '−' : '')).abs($n);
    $diffClass = fn ($n) => $n > 0 ? 'text-lime-text' : ($n < 0 ? 'text-hot' : 'text-ink3');
    // Podium columns, left to right: 2nd, 1st, 3rd.
    $stands = [
        [1, 'h-18 lg:h-23 bg-ink text-bg', 'size-12 text-[15px] lg:size-15 lg:text-lg', '.12s'],
        [0, 'h-24 lg:h-31 bg-lime text-on-lime', 'size-14.5 text-lg lg:size-18.5 lg:text-[22px]', '0s'],
        [2, 'h-14.5 lg:h-18.5 bg-court text-white', 'size-12 text-[15px] lg:size-15 lg:text-lg', '.24s'],
    ];
    $cols = 'grid-cols-[28px_minmax(0,1fr)_42px_42px_44px] lg:grid-cols-[30px_minmax(0,1fr)_34px_34px_38px_40px_40px_48px_52px]';
@endphp

<section aria-labelledby="{{ $heading }}" {{ $attributes->merge(['class' => 'flex flex-col gap-5'.($plain ? '' : ' border border-line bg-surface p-4.5 lg:p-5.5')]) }}>
    <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
        <div class="flex min-w-0 flex-col gap-1">
            <h2 id="{{ $heading }}" @class(['m-0 font-display font-extrabold tracking-[-0.03em]', 'text-[22px]' => ! $plain, 'text-xl' => $plain])>{{ $title }}</h2>
            <span class="text-[13px] leading-[1.4] text-ink3">Top 3 by wins, then fewest losses, then point difference.</span>
        </div>
        <span class="font-mono text-xs text-ink3">{{ count($rows) }} {{ Str::plural('player', count($rows)) }} · {{ $games }} {{ Str::plural('game', $games) }}</span>
    </div>

    @if (! $rows)
        <p class="m-0 rounded-[18px] border-[1.5px] border-dashed border-line2 p-5.5 text-center text-ink2">The podium fills in as soon as the first score is logged.</p>
    @else
        <div class="mx-auto grid w-full max-w-140 grid-cols-3 items-end gap-2.5 lg:gap-4">
            @foreach ($stands as [$i, $block, $size, $delay])
                @php($r = $rows[$i] ?? null)
                @php($p = $r ? $play->participant($r['participant_id']) : null)
                <div class="flex min-w-0 animate-rise flex-col items-center gap-2.5" style="animation-delay: {{ $delay }}">
                    @if ($r)
                        <div @class(['relative', 'animate-snap' => in_array($r['participant_id'], $fresh)])>
                            <x-avatar :src="$p?->avatarUrl()" :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'"
                                      :class="$size.' border-3 '.($r['participant_id'] === $me ? 'border-lime' : 'border-surface')" />
<span class="absolute -right-3 -bottom-2 lg:hidden"><x-medal :rank="$i + 1" :size="26" ring="{{ $plain ? 'var(--color-bg)' : 'var(--color-surface)' }}" /></span>
                            <span class="absolute -right-3.75 -bottom-2 hidden lg:block"><x-medal :rank="$i + 1" :size="34" ring="{{ $plain ? 'var(--color-bg)' : 'var(--color-surface)' }}" /></span>
                        </div>
                        <div class="flex max-w-full flex-col items-center gap-0.5 text-center">
                            <span class="max-w-full truncate text-sm font-extrabold lg:text-base">{{ $p?->firstName() }}</span>
                            <span class="font-mono text-[13px] whitespace-nowrap text-ink2">{{ $r['w'] }}-{{ $r['l'] }} · <span class="font-semibold {{ $diffClass($r['diff']) }}">{{ $diff($r['diff']) }}</span></span>
                        </div>
                    @else
                        <span class="{{ $size }} rounded-full border-2 border-dashed border-line2"></span>
                        <span class="text-[13px] font-bold text-ink3">Open spot</span>
                    @endif
                    <div class="flex w-full justify-center rounded-t-[18px] rounded-b-md pt-2.5 font-display text-[26px] font-extrabold tracking-[-0.04em] lg:text-[32px] {{ $block }}">{{ $i + 1 }}</div>
                </div>
            @endforeach
        </div>

        <div role="table" aria-label="Every player's stats" class="flex flex-col overflow-hidden rounded-[18px] border border-line bg-surface">
            <div role="row" class="grid {{ $cols }} items-center gap-2 bg-surface2 px-3.5 py-2.5 font-mono text-[11px] tracking-[.08em] text-ink3 uppercase">
                <span role="columnheader">#</span>
                <span role="columnheader">Player</span>
                @foreach (['W' => 'Wins', 'L' => 'Losses', 'GP' => 'Games played', 'PF' => 'Points for', 'PA' => 'Points against'] as $abbr => $title)
                    <span role="columnheader" title="{{ $title }}" class="hidden text-right lg:block">{{ $abbr }}</span>
                @endforeach
                <span role="columnheader" title="Wins and losses" class="text-right lg:hidden">W-L</span>
                <span role="columnheader" title="Point difference" class="text-right">+/−</span>
                <span role="columnheader" class="text-right">Win %</span>
            </div>
            @foreach ($rows as $r)
                @php($p = $play->participant($r['participant_id']))
                @php($top = $r['rank'] <= 3)
                @php($mine = $r['participant_id'] === $me)
                <div role="row" wire:key="st-{{ $r['participant_id'] }}" @class([
                    "grid $cols min-h-13 items-center gap-2 border-t border-line px-3.5 py-2 text-sm",
                    'bg-lime-soft' => $top, 'bg-surface2' => ! $top && $mine,
                    'animate-snap' => in_array($r['participant_id'], $fresh),
                ])>
                    <span role="cell">
                        @if ($top)
                            <x-medal :rank="$r['rank']" :size="22" />
                        @else
                            <span class="inline-block w-6.5 text-center font-mono text-[13px] text-ink3">{{ $r['rank'] }}</span>
                        @endif
                    </span>
                    <span role="cell" class="flex min-w-0 items-center gap-2.5">
                        <x-avatar :src="$p?->avatarUrl()" :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" class="size-7.5 text-[11px]" />
                        <span @class(['min-w-0 truncate', 'font-extrabold' => $top || $mine, 'font-semibold' => ! $top && ! $mine])>
                            <span class="lg:hidden">{{ $p?->firstName() }}</span><span class="hidden lg:inline">{{ $p?->name }}</span>
                        </span>
                        @if ($mine)
                            <span class="inline-flex h-5 shrink-0 items-center rounded-full bg-ink px-1.75 text-[10px] font-extrabold text-bg">YOU</span>
                        @endif
                    </span>
                    <span role="cell" class="hidden text-right font-mono font-bold lg:block">{{ $r['w'] }}</span>
                    @foreach (['l', 'g', 'pf', 'pa'] as $k)
                        <span role="cell" class="hidden text-right font-mono text-ink2 lg:block">{{ $r[$k] }}</span>
                    @endforeach
                    <span role="cell" class="text-right font-mono font-bold lg:hidden">{{ $r['w'] }}-{{ $r['l'] }}</span>
                    <span role="cell" class="text-right font-mono font-semibold {{ $diffClass($r['diff']) }}">{{ $diff($r['diff']) }}</span>
                    <span role="cell" class="text-right font-mono text-ink2">{{ round($r['w'] * 100 / $r['g']) }}%</span>
                </div>
            @endforeach
        </div>
    @endif
</section>
