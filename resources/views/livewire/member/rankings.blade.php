<x-app-shell>
    <x-page>
        <x-page-header :eyebrow="$kicker" title="Rankings">
            <x-tabs label="Period">
                @if ($all)
                    <x-tab active>All time</x-tab>
                @else
                    <x-tab :active="$period === 'month'" wire:click="$set('period', 'month')">This month</x-tab>
                    <x-tab :active="$period === 'all'" wire:click="$set('period', 'all')">All time</x-tab>
                @endif
            </x-tabs>
        </x-page-header>

        {{-- One table per club whose open plays you joined, plus every club added up. --}}
        <div class="flex flex-col gap-2.5">
            <div role="tablist" aria-label="Club" class="-mx-1 flex gap-2 overflow-x-auto px-1 py-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($scopes as $sc)
                    <button type="button" role="tab" aria-selected="{{ $sc['on'] ? 'true' : 'false' }}" aria-label="{{ $sc['name'] }}, {{ $sc['sub'] }}" wire:click="$set('club', @js($sc['id']))" wire:key="scope-{{ $sc['id'] }}"
                            @class(['flex min-h-13 shrink-0 cursor-pointer flex-col items-start justify-center gap-px rounded-2xl border-[1.5px] px-4 py-1.75 text-left transition-colors duration-250 active:scale-97',
                                'border-ink bg-ink text-bg' => $sc['on'], 'border-line bg-surface text-ink' => ! $sc['on']])>
                        <span class="text-sm font-extrabold whitespace-nowrap">{{ $sc['name'] }}</span>
                        <span @class(['font-mono text-[11px] tracking-[.08em] whitespace-nowrap uppercase', 'text-lime-on-ink' => $sc['on'], 'text-ink3' => ! $sc['on']])>{{ $sc['sub'] }}</span>
                    </button>
                @endforeach
            </div>
            <span class="text-[13px] text-ink2">{{ $scopeNote }}</span>
        </div>

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

        @if ($me)
            <div class="flex items-center gap-3.5 rounded-[22px] bg-ink px-4.5 py-4 text-bg">
                <span class="shrink-0 font-display text-[34px] font-extrabold tracking-[-0.04em] text-lime-on-ink">#{{ $me->rank }}</span>
                <div class="flex flex-1 flex-col">
                    <span class="text-base font-bold">That's you, {{ $user->firstName() }} · {{ $me->pts }} pts</span>
                    <span class="text-sm opacity-80">{{ $note }}</span>
                </div>
            </div>
        @else
            {{-- Not ranked yet: a guide to the first game instead of the "That's you" bar. --}}
            <section aria-labelledby="unr-h" class="grid animate-rise items-center gap-x-7 gap-y-5.5 rounded-[26px] bg-ink p-4.5 text-bg lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] lg:p-6">
                <div class="flex min-w-0 items-start gap-4.5">
                    <span class="relative size-18 shrink-0">
                        <x-avatar :src="$user->avatarUrl()" :frame="$user->frameClass()" :name="$user->shownName()" :color="$user->color()" class="size-18 text-[22px]" />
                        <span class="absolute -bottom-2.25 left-1/2 inline-flex h-5.5 -translate-x-1/2 items-center rounded-full border-2 border-ink bg-lime px-2.25 text-[11px] font-extrabold tracking-[.04em] whitespace-nowrap text-on-lime">NEW</span>
                    </span>
                    <div class="flex min-w-0 flex-col gap-1.5">
                        <span class="font-mono text-xs tracking-[.12em] text-lime-on-ink uppercase">Not ranked yet · {{ $unranked['games'] }}</span>
                        <h2 id="unr-h" class="m-0 font-display text-[28px] leading-[1.05] font-extrabold tracking-[-0.035em]">You're not ranked yet</h2>
                        <p class="m-0 max-w-135 text-[15px] leading-normal opacity-82">{{ $unranked['body'] }}</p>
                    </div>
                </div>
                <div class="flex min-w-0 flex-col gap-3">
                    <div aria-hidden="true" class="flex items-center gap-2.5">
                        <span class="flex size-8.5 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-line2 font-mono text-[15px] opacity-80">1</span>
                        <span class="h-0.5 flex-1 bg-[linear-gradient(90deg,var(--color-line2)_0_50%,transparent_50%_100%)] bg-size-[10px_2px] bg-repeat-x"></span>
                        <span class="inline-flex h-8.5 shrink-0 items-center justify-center rounded-full bg-lime px-3 text-[13px] font-extrabold text-on-lime">Ranked</span>
                    </div>
                    <span class="text-[13px] font-bold">One game gets you ranked</span>
                    <a href="{{ route('member.plays') }}" wire:navigate class="mt-0.5 inline-flex h-12.5 items-center justify-center gap-2 rounded-full bg-lime px-5.5 text-[15px] font-extrabold text-on-lime transition hover:brightness-96 active:scale-97">
                        Find an open play<x-icon name="arrow-right" :size="17" :stroke="2.2" />
                    </a>
                </div>
            </section>
            <div class="grid gap-3 lg:grid-cols-3">
                @foreach ($unranked['tips'] as [$k, $v, $t])
                    <div class="flex flex-col gap-1.5 rounded-[20px] border border-line bg-surface px-4.5 py-4">
                        <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $k }}</span>
                        <span class="font-display text-2xl leading-[1.05] font-extrabold tracking-[-0.03em]">{{ $v }}</span>
                        <span class="text-[13px] leading-[1.45] text-ink2">{{ $t }}</span>
                    </div>
                @endforeach
            </div>
        @endif

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
            @if (! $me && $rows->isNotEmpty())
                <div aria-label="You, not ranked yet" class="flex items-center gap-2.5 bg-lime-soft px-3.5 py-3 lg:gap-3.5 lg:px-6">
                    <span class="w-6.5 shrink-0 text-center font-mono text-[15px] text-ink3">-</span>
                    <span aria-hidden="true" class="w-8.5"></span>
                    <x-avatar :src="$user->avatarUrl()" :frame="$user->frameClass()" :name="$user->shownName()" :color="$user->color()" class="size-9.5 text-xs outline-2 outline-offset-2 outline-line2 outline-dashed" />
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <span class="truncate font-bold">{{ $user->shownName() }}</span>
                        <span class="inline-flex h-5.5 shrink-0 items-center rounded-full bg-lime px-2 text-[11px] font-extrabold text-on-lime">YOU</span>
                        <span class="hidden shrink-0 text-[13px] text-ink2 lg:inline">Not ranked yet</span>
                    </div>
                    <span class="hidden w-22.5 font-mono text-sm text-ink2 lg:inline">-</span>
                    <span class="hidden w-22.5 font-mono text-sm text-ink2 lg:inline">0 games</span>
                    <span class="w-12 text-right font-mono text-[15px] font-semibold text-ink3 lg:w-17.5">-</span>
                </div>
            @endif
            @if ($rows->isEmpty())
                <p class="m-0 px-6 py-7 text-ink2">{{ $empty }}</p>
            @endif
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
