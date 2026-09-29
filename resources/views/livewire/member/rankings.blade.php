<x-app-shell>
    <x-page>
        <x-page-header :eyebrow="$user->club ?? 'All members'" title="Rankings">
            <x-tabs label="Period">
                <x-tab :active="$period === 'month'" wire:click="$set('period', 'month')">This month</x-tab>
                <x-tab :active="$period === 'all'" wire:click="$set('period', 'all')">All time</x-tab>
            </x-tabs>
        </x-page-header>

        @php
            // Podium order 2nd, 1st, 3rd; sizes mobile / lg.
            $podium = [
                1 => ['h' => 'h-[78px] lg:h-[110px]', 'av' => 'size-[50px] text-[15px] lg:size-[66px] lg:text-[19px]', 'block' => 'bg-ink text-bg', 'delay' => '[animation-delay:.12s]'],
                0 => ['h' => 'h-[108px] lg:h-[150px]', 'av' => 'size-[62px] text-[18px] lg:size-[86px] lg:text-[24px]', 'block' => 'bg-lime text-on-lime', 'delay' => ''],
                2 => ['h' => 'h-[58px] lg:h-[82px]', 'av' => 'size-[50px] text-[15px] lg:size-[66px] lg:text-[19px]', 'block' => 'bg-court text-white', 'delay' => '[animation-delay:.24s]'],
            ];
        @endphp
        <div class="grid grid-cols-3 items-end gap-2 pt-2 lg:gap-4">
            @foreach ($podium as $i => $pd)
                @if ($r = $rows[$i] ?? null)
                    <div wire:key="pod-{{ $period }}-{{ $i }}" class="flex animate-rise flex-col items-center gap-2.5 {{ $pd['delay'] }}">
                        <div class="relative">
                            <x-avatar :src="$r->user->avatarUrl()" :frame="$r->user->frameClass()" :name="$r->user->shownName()" :color="$r->user->color()" :class="$pd['av'].' border-3 '.($r->me ? 'border-lime' : 'border-surface')" />
<span class="absolute -right-3 -bottom-2 lg:hidden"><x-medal :rank="$r->rank" :size="26" ring="var(--color-bg)" /></span>
                            <span class="absolute -right-3.75 -bottom-2 hidden lg:block"><x-medal :rank="$r->rank" :size="36" ring="var(--color-bg)" /></span>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <span class="text-sm font-extrabold lg:text-[17px]"><span class="lg:hidden">{{ $r->user->shownFirst() }}</span><span class="hidden lg:inline">{{ $r->user->shownName() }}</span></span>
                            <span class="font-mono text-[13px] text-ink2">{{ $r->pts }} pts</span>
                        </div>
                        <div class="flex w-full items-start justify-center rounded-[18px_18px_6px_6px] pt-3 font-display text-[30px] font-extrabold tracking-[-0.04em] lg:text-[44px] {{ $pd['h'] }} {{ $pd['block'] }}">{{ $r->rank }}</div>
                    </div>
                @else
                    <div></div>
                @endif
            @endforeach
        </div>

        <div class="flex items-center gap-3.5 rounded-[22px] bg-ink px-4.5 py-4 text-bg">
            <span class="shrink-0 font-display text-[34px] font-extrabold tracking-[-0.04em] text-lime-on-ink">#{{ $me->rank }}</span>
            <div class="flex flex-1 flex-col">
                <span class="text-base font-bold">That's you, {{ $user->firstName() }} · {{ $me->pts }} pts</span>
                <span class="text-sm opacity-80">{{ $note }}</span>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-line bg-surface">
            @foreach ($rows as $r)
                <div wire:key="row-{{ $r->user->id }}" @class(['flex items-center gap-2.5 border-b border-line px-3.5 py-3 lg:gap-3.5 lg:px-6', 'bg-lime-soft' => $r->me])>
                    <span class="flex w-6.5 shrink-0 justify-center">
                        @if ($r->rank <= 3)
                            <x-medal :rank="$r->rank" :size="22" />
                        @else
                            <span class="font-mono text-[15px] font-semibold">{{ $r->rank }}</span>
                        @endif
                    </span>
                    <span @class([
                        'inline-flex w-8.5 items-center gap-px font-mono text-xs',
                        'text-lime-text' => $r->mv > 0,
                        'text-hot' => $r->mv < 0,
                        'text-ink3' => $r->mv === 0,
                    ])>
                        @if ($r->mv > 0)
                            <x-icon name="arrow-up" :size="14" :stroke="2.4" /><span class="sr-only">Up</span>
                        @elseif ($r->mv < 0)
                            <x-icon name="arrow-down" :size="14" :stroke="2.4" /><span class="sr-only">Down</span>
                        @endif
                        {{ $r->mv === 0 ? '-' : abs($r->mv) }}
                    </span>
                    <x-avatar :src="$r->user->avatarUrl()" :frame="$r->user->frameClass()" :name="$r->user->shownName()" :color="$r->user->color()" class="size-9.5 text-xs" />
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <span class="truncate font-bold">{{ $r->user->shownName() }}</span>
                        @if ($r->me)
                            <span class="inline-flex h-5.5 shrink-0 items-center rounded-full bg-lime px-2 text-[11px] font-extrabold text-on-lime">YOU</span>
                        @endif
                    </div>
                    <span class="hidden w-22.5 font-mono text-sm text-ink2 lg:inline">{{ $r->wins }}-{{ $r->losses }}</span>
                    <span class="hidden w-22.5 font-mono text-sm text-ink2 lg:inline">{{ $r->games }} games</span>
                    <span class="w-12 text-right font-mono text-[15px] font-semibold lg:w-17.5">{{ $r->pts }}</span>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2.5 rounded-[20px] border-[1.5px] border-dashed border-line2 px-4.5 py-4">
            <span class="mr-1 font-bold">How points work</span>
            @foreach (['+10' => 'win', '+3' => 'game played', '+2' => 'new partner'] as $pts => $label)
                <span class="inline-flex h-8.5 items-center gap-1.5 rounded-full border border-line bg-surface px-3 text-[13px] font-semibold"><span class="font-mono text-lime-text">{{ $pts }}</span>{{ $label }}</span>
            @endforeach
            <span class="text-[13px] text-ink3">No DUPR, no self-ratings.</span>
        </div>
    </x-page>
</x-app-shell>
