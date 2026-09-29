<x-app-shell>
    <x-page>
        <x-page-header eyebrow="Your people" title="Played with" description="Everyone you've partnered with or faced, counted from logged open-play games." />

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="flex items-center gap-3.5 rounded-[22px] bg-lime p-4 text-on-lime lg:p-5">
                @if ($best)
                    <x-avatar :src="$best->user->avatarUrl()" :frame="$best->user->frameClass()" :name="$best->user->shownName()" color="#FFFFFF" class="size-13" />
                @else
                    <span class="flex size-13 shrink-0 items-center justify-center rounded-full bg-white font-extrabold">-</span>
                @endif
                <div class="flex flex-col">
                    <span class="font-mono text-[11px] tracking-widest uppercase">Best partner</span>
                    <span class="font-display text-[22px] font-extrabold tracking-[-0.03em]">{{ $best?->user->shownName() ?? 'Not yet' }}</span>
                    <span class="text-[13px] font-semibold">{{ $best ? "{$best->wins}-{$best->losses} together · {$best->pct}% wins" : 'Play 5 games with someone to unlock' }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3.5 rounded-[22px] border border-line bg-surface p-4 lg:p-5">
                @if ($tough)
                    <x-avatar :src="$tough->user->avatarUrl()" :frame="$tough->user->frameClass()" :name="$tough->user->shownName()" :color="$tough->user->color()" class="size-13" />
                @else
                    <span class="flex size-13 shrink-0 items-center justify-center rounded-full bg-surface2 font-extrabold">-</span>
                @endif
                <div class="flex flex-col">
                    <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">Toughest opponent</span>
                    <span class="font-display text-[22px] font-extrabold tracking-[-0.03em]">{{ $tough?->user->shownName() ?? 'Not yet' }}</span>
                    <span class="text-[13px] text-ink2">{{ $tough ? "You're {$tough->wins}-{$tough->losses} against them" : 'Face someone 5 times to unlock' }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3.5 rounded-[22px] border border-line bg-surface p-4 lg:p-5">
                <span class="flex size-13 shrink-0 items-center justify-center rounded-2xl bg-surface2 text-ink"><x-icon name="users" :size="22" :stroke="1.9" /></span>
                <div class="flex flex-col">
                    <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">Different players</span>
                    <span class="font-display text-[22px] font-extrabold tracking-[-0.03em]">{{ $stats->partners->count() }} {{ Str::plural('partner', $stats->partners->count()) }} · {{ $stats->opponents->count() }} {{ Str::plural('opponent', $stats->opponents->count()) }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <x-tabs label="Relationship">
                <x-tab :active="$tab === 'partners'" wire:click="$set('tab', 'partners')">Partners</x-tab>
                <x-tab :active="$tab === 'opponents'" wire:click="$set('tab', 'opponents')">Opponents</x-tab>
            </x-tabs>
            <div class="inline-flex gap-1.5">
                @foreach (['games' => 'Most games', 'rate' => 'Best win rate'] as $key => $label)
                    <button type="button" aria-pressed="{{ $sort === $key ? 'true' : 'false' }}" wire:click="$set('sort', '{{ $key }}')" @class([
                        'h-10 cursor-pointer rounded-full border-[1.5px] px-3.5 text-[13px] font-semibold transition hover:brightness-96 active:scale-97',
                        'border-ink text-ink' => $sort === $key,
                        'border-line text-ink2' => $sort !== $key,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
            <div class="relative min-w-full flex-1 lg:min-w-60">
                <label for="px-pq" class="sr-only">Search players</label>
                <x-icon name="search" :size="18" :stroke="1.9" class="absolute top-1/2 left-3.5 -translate-y-1/2 text-ink3" />
                <input id="px-pq" type="text" placeholder="Search by name" wire:model.live.debounce.300ms="search" class="h-11 w-full rounded-full border-[1.5px] border-line bg-surface pr-4 pl-10.5 text-sm text-ink outline-none placeholder:text-ink3 focus:border-ink">
            </div>
        </div>

        @php
            $barColor = fn ($pct) => $pct >= 60 ? 'bg-lime-text' : ($pct >= 45 ? 'bg-ink2' : 'bg-hot');
        @endphp
        <div class="overflow-hidden rounded-3xl border border-line bg-surface">
            {{-- Desktop table --}}
            <div class="hidden lg:block">
                <div class="grid grid-cols-[minmax(0,2.2fr)_minmax(0,.8fr)_minmax(0,.8fr)_minmax(0,1.6fr)_minmax(0,1fr)] gap-4 border-b border-line px-6 py-3.5 font-mono text-[11px] tracking-widest text-ink3 uppercase">
                    <span>Player</span><span>Games</span><span>Record</span><span>Win rate {{ $tab === 'partners' ? 'together' : 'against' }}</span><span>Last played</span>
                </div>
                @foreach ($rows as $r)
                    <div wire:key="d-{{ $r->user->id }}" class="grid grid-cols-[minmax(0,2.2fr)_minmax(0,.8fr)_minmax(0,.8fr)_minmax(0,1.6fr)_minmax(0,1fr)] items-center gap-4 border-b border-line px-6 py-3.5">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-avatar :src="$r->user->avatarUrl()" :frame="$r->user->frameClass()" :name="$r->user->shownName()" :color="$r->user->color()" class="size-10 text-[13px]" />
                            <span class="truncate font-bold">{{ $r->user->shownName() }}</span>
                        </div>
                        <span class="font-mono font-semibold">{{ $r->games }}</span>
                        <span class="font-mono font-semibold">{{ $r->wins }}-{{ $r->losses }}</span>
                        <div class="flex items-center gap-3">
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-surface2"><div class="h-full rounded-sm {{ $barColor($r->pct) }}" style="width: {{ $r->pct }}%"></div></div>
                            <span class="w-10.5 text-right font-mono text-[13px] text-ink2">{{ $r->pct }}%</span>
                        </div>
                        <span class="text-sm text-ink2">{{ \App\Support\MemberStats::when($r->last) }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Mobile rows --}}
            <div class="lg:hidden">
                @foreach ($rows as $r)
                    <div wire:key="m-{{ $r->user->id }}" class="flex items-center gap-3 border-b border-line px-4 py-3.5">
                        <x-avatar :src="$r->user->avatarUrl()" :frame="$r->user->frameClass()" :name="$r->user->shownName()" :color="$r->user->color()" class="size-10.5 text-[13px]" />
                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                            <div class="flex justify-between gap-2"><span class="font-bold">{{ $r->user->shownName() }}</span><span class="font-mono font-semibold">{{ $r->wins }}-{{ $r->losses }}</span></div>
                            <div class="flex items-center gap-2.5">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-[3px] bg-surface2"><div class="h-full rounded-[3px] {{ $barColor($r->pct) }}" style="width: {{ $r->pct }}%"></div></div>
                                <span class="text-xs text-ink3">{{ $r->games }} games · {{ \App\Support\MemberStats::when($r->last) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($rows->isEmpty())
                <p class="m-0 px-6 py-7 text-ink2">{{ trim($search) !== '' ? 'No one matches that search yet.' : ($tab === 'opponents' ? 'No opponents yet. Everyone you face in a logged game shows up here.' : 'No partners yet. Everyone you team up with in a logged game shows up here.') }}</p>
            @endif
        </div>
    </x-page>
</x-app-shell>
