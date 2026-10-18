@php
    $reasons = App\Livewire\Admin\OpenPlays::REASONS;
    $isTest = fn ($p) => App\Livewire\Admin\OpenPlays::looksTest($p->title);
    $testPill = 'inline-flex h-5.5 shrink-0 items-center rounded-full border border-hot-line bg-hot-soft px-2 text-[11.5px] font-extrabold whitespace-nowrap text-hot';
    $cols = 'lg:grid-cols-[minmax(0,2.6fr)_minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(0,.5fr)_minmax(0,.5fr)_112px]';
    $statOff = fn ($games, $players) => $games.' '.Str::plural('game', $games).' came off '.$players.' '.($players === 1 ? "player's" : "players'").' stats';
@endphp

<x-app-shell>
    <x-page>
        <x-page-header eyebrow="Admin" title="Concluded open plays" description="Delete test or duplicate open plays after they end. Their games come off rankings and stats." />

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($tiles as [$label, $n, $hot])
                <div @class(['flex flex-col gap-2 rounded-[22px] border p-4 lg:p-5', 'border-hot-line bg-hot-soft text-hot' => $hot, 'border-line bg-surface' => ! $hot])>
                    <span class="font-mono text-[11px] tracking-widest uppercase">{{ $label }}</span>
                    <span class="font-display text-[34px] leading-none font-extrabold tracking-[-0.04em] lg:text-[40px]">{{ $n }}</span>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <div role="tablist" aria-label="Show open plays" class="inline-flex max-w-full overflow-x-auto rounded-full border border-line bg-surface p-1 [scrollbar-width:none]">
                @foreach ($tabs as $key => [$label, $n])
                    @php($on = $tab === $key)
                    <button type="button" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')"
                            @class(['inline-flex h-10 shrink-0 cursor-pointer items-center gap-1.75 rounded-full px-3.5 text-sm font-bold whitespace-nowrap transition-colors', 'bg-ink text-bg' => $on, 'text-ink2' => ! $on])>
                        {{ $label }}<span @class(['inline-flex h-5 min-w-5.5 items-center justify-center rounded-full px-1.5 font-mono text-xs font-bold', 'bg-lime text-on-lime' => $on, 'bg-surface2 text-ink2' => ! $on])>{{ $n }}</span>
                    </button>
                @endforeach
            </div>
            <div class="relative min-w-full flex-1 lg:min-w-60">
                <label for="ap-q" class="sr-only">Search open plays</label>
                <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink3" />
                <input id="ap-q" type="search" wire:model.live.debounce.250ms="q" placeholder="Search by title, club or organizer" autocomplete="off"
                       class="h-11 w-full rounded-full border-[1.5px] border-line bg-surface pr-4 pl-10.5 text-sm text-ink outline-none focus:border-ink [&::-webkit-search-cancel-button]:hidden">
            </div>
        </div>

        <section aria-label="Concluded open plays" class="overflow-hidden rounded-3xl border border-line bg-surface">
            <div class="flex items-baseline justify-between gap-2.5 border-b border-line px-5 py-3.5">
                <span class="font-bold">Newest first</span>
                <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $rows->count() === $total ? $total.' '.Str::plural('open play', $total) : $rows->count().' of '.$total.' open plays' }}</span>
            </div>
            <div class="hidden {{ $cols }} gap-3.5 border-b border-line px-5 py-3 font-mono text-[11px] tracking-widest text-ink3 uppercase lg:grid">
                <span>Open play</span><span>Club</span><span>Organizer</span><span>Games</span><span>Players</span><span></span>
            </div>
            @forelse ($rowsPage as $p)
                @php($host = $p->organizer?->name)
                <div wire:key="ap-{{ $p->id }}" class="flex items-start gap-3 border-b border-line px-4 py-3.5 last:border-b-0 lg:grid {{ $cols }} lg:items-center lg:gap-3.5 lg:px-5 lg:py-3">
                    <div class="flex min-w-0 flex-1 items-start gap-3 lg:items-center">
                        <div aria-hidden="true" class="flex h-13.5 w-11.5 shrink-0 flex-col items-center justify-center rounded-[14px] border border-line bg-bg lg:h-14 lg:w-12">
                            <span class="font-mono text-[10px] tracking-[.12em] text-ink3 uppercase">{{ $p->starts_at->format('M') }}</span>
                            <span class="font-display text-[19px] leading-none font-extrabold tracking-[-0.03em]">{{ $p->starts_at->format('j') }}</span>
                            <span class="font-mono text-[10px] tracking-[.08em] text-ink3 uppercase">{{ $p->starts_at->format('D') }}</span>
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col gap-1 lg:gap-0.75">
                            <span class="flex min-w-0 flex-col items-start gap-1 lg:flex-row lg:items-center lg:gap-2">
                                <span class="leading-[1.25] font-bold lg:truncate">{{ $p->title }}</span>
                                @if ($isTest($p))<span class="{{ $testPill }}">Looks like a test</span>@endif
                            </span>
                            <span class="hidden truncate text-[13px] text-ink3 lg:block">{{ $p->timeRange() }} · {{ $p->venue }}</span>
                            <span class="text-[12.5px] leading-[1.35] text-ink3 lg:hidden">{{ $p->club }}{{ $host ? ' · '.$host : '' }}</span>
                            <span class="font-mono text-xs text-ink2 lg:hidden">{{ $p->games_count }} {{ Str::plural('game', $p->games_count) }} · {{ $p->players_count }} {{ Str::plural('player', $p->players_count) }}</span>
                            <span class="mt-1 flex lg:hidden">
                                <button type="button" wire:click="askDelete({{ $p->id }})" aria-label="Delete {{ $p->title }}" class="inline-flex h-10 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-full border-[1.5px] border-hot-line px-3.5 text-sm font-bold text-hot transition hover:bg-hot-soft active:scale-97"><x-icon name="trash" :size="16" />Delete</button>
                            </span>
                        </div>
                    </div>
                    <span class="hidden truncate text-sm text-ink2 lg:block">{{ $p->club }}</span>
                    <span class="hidden truncate text-sm text-ink2 lg:block">{{ $host ?? '-' }}</span>
                    <span class="hidden font-mono text-[15px] font-semibold lg:block">{{ $p->games_count }}</span>
                    <span class="hidden font-mono text-[15px] font-semibold lg:block">{{ $p->players_count }}</span>
                    <button type="button" wire:click="askDelete({{ $p->id }})" aria-label="Delete {{ $p->title }}" class="hidden h-10 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-full border-[1.5px] border-hot-line px-3.5 text-sm font-bold text-hot transition hover:bg-hot-soft active:scale-97 lg:inline-flex"><x-icon name="trash" :size="16" />Delete</button>
                </div>
            @empty
                <p class="m-0 px-5 py-6 text-ink2">{{ trim($q) !== '' ? 'No concluded open play matches "'.trim($q).'".' : ($tab === 'test' ? 'Nothing here looks like a test.' : 'No concluded open plays yet.') }}</p>
            @endforelse
            <x-pager :page="$rowsPage" noun="open plays" class="border-t border-line px-5 py-3" />
        </section>

        @if ($done->isNotEmpty())
            <section aria-labelledby="ap-done" class="flex flex-col gap-2.5">
                <div class="flex flex-col gap-0.5">
                    <h2 id="ap-done" class="m-0 font-display text-xl font-bold tracking-[-0.02em]">Deleted</h2>
                    <span class="text-sm text-ink3">Their games don't count anywhere anymore.</span>
                </div>
                @foreach ($done as $d)
                    <div wire:key="apd-{{ $d->id }}" class="flex items-start gap-3 rounded-[18px] border-[1.5px] border-dashed border-line2 px-4 py-3.5">
                        <span aria-hidden="true" class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-surface2 text-ink3"><x-icon name="trash" :size="16" /></span>
                        <div class="flex min-w-0 flex-1 flex-col gap-0.75">
                            <span class="font-bold text-ink2 line-through decoration-hot decoration-2">{{ $d->title }}</span>
                            <span class="text-[13px] leading-[1.4] text-ink3">{{ $d->club }}{{ $d->host ? ' · hosted by '.$d->host : '' }} · reason: {{ $reasons[$d->reason][2] ?? 'something else' }}</span>
                            <span class="text-[13px] font-semibold text-ink">{{ $statOff($d->games, $d->players) }}{{ $d->host ? ' · '.Str::before($d->host, ' ').' was emailed' : '' }}</span>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    </x-page>

    {{-- Delete a concluded open play: who it comes off, why, and who gets emailed. --}}
    @if ($play)
        @php($host = $play->organizer)
        @php($hf = $host?->firstName() ?? 'the organizer')
        @php($n = $people->count())
        <div wire:key="apdel-{{ $play->id }}" x-data x-on:keydown.escape.window="$wire.cancelDelete()" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <button type="button" wire:click="cancelDelete" aria-label="Keep open play" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

            <div role="alertdialog" aria-modal="true" aria-labelledby="apd-title" aria-describedby="apd-desc" x-trap.noscroll="true"
                 class="relative flex max-h-full w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-120 md:max-w-full md:rounded-[28px]">
                <div class="flex flex-col gap-4 overflow-y-auto px-6 pt-6.5 pb-4.5">
                    <span class="flex size-13 shrink-0 items-center justify-center rounded-2xl border border-hot-line bg-hot-soft text-hot"><x-icon name="trash" :size="22" /></span>
                    <div class="flex flex-col gap-1.5">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Delete a concluded open play</span>
                        <h2 id="apd-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">Delete {{ $play->title }}?</h2>
                        <p class="m-0 text-sm text-ink3">{{ $play->starts_at->format('D, M j') }} · {{ $play->timeRange() }} · {{ $play->club }}</p>
                        <span class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                            @if ($host)<span class="text-sm text-ink2">Hosted by {{ $host->name }} · {{ $host->email }}</span>@endif
                            @if ($isTest($play))<span class="{{ $testPill }}">Looks like a test</span>@endif
                        </span>
                    </div>
                    <div class="grid shrink-0 grid-cols-3 gap-2">
                        @foreach (['Games' => $play->games_count, 'Players' => $n, 'Ended' => $play->starts_at->format('M j')] as $label => $value)
                            <div class="rounded-2xl border border-line bg-surface p-3">
                                <div class="font-mono text-[10px] tracking-widest text-ink3 uppercase">{{ $label }}</div>
                                <div class="font-display text-2xl font-extrabold tracking-[-0.03em] whitespace-nowrap">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if ($n)
                        <div class="flex flex-col gap-2.5">
                            <span class="font-bold">Comes off {{ $n === 1 ? "this player's" : "these {$n} players'" }} stats</span>
                            <div role="list" class="grid grid-cols-2 gap-2">
                                @foreach ($people->take(8) as [$p, $w, $l])
                                    <div role="listitem" aria-label="{{ $p->name }}: {{ $w }} {{ Str::plural('win', $w) }}, {{ $l }} {{ $l === 1 ? 'loss' : 'losses' }}" class="flex min-w-0 items-center gap-2 rounded-full border border-line bg-surface py-1.5 pr-2.5 pl-1.5">
                                        <x-avatar :src="$p->avatarUrl()" :frame="$p->frameClass()" :name="$p->name" :color="$p->color()" class="size-7.5 text-[11px]" aria-hidden="true" />
                                        <span aria-hidden="true" class="min-w-0 flex-1 truncate text-[13.5px] font-semibold">{{ $p->name }}</span>
                                        <span aria-hidden="true" class="shrink-0 font-mono text-[13px] font-semibold text-ink2">{{ $w }}-{{ $l }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @if ($n > 8)<span class="text-[13px] text-ink3">+ {{ $n - 8 }} more</span>@endif
                        </div>
                    @endif
                    <p id="apd-desc" class="m-0 text-[15px] leading-normal text-ink2">Its {{ $play->games_count }} {{ Str::plural('game', $play->games_count) }} come off their rankings, played-with lists and game counts, and it leaves past results for them and for {{ $hf }}. Every other open play and game stays as it is.</p>
                    <div role="radiogroup" aria-labelledby="apd-why" class="flex flex-col gap-2">
                        <span id="apd-why" class="font-bold">Why are you deleting it?</span>
                        @foreach ($reasons as $key => [$label, $hint])
                            @php($on = $why === $key)
                            <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" wire:click="pickWhy('{{ $key }}')"
                                    @class(['flex cursor-pointer items-center gap-3 rounded-2xl border-[1.5px] px-3.5 py-2.75 text-left text-ink transition active:scale-97', 'border-ink bg-surface' => $on, 'border-line' => ! $on])>
                                <span aria-hidden="true" class="box-border flex size-5 shrink-0 items-center justify-center rounded-full border-2 border-ink"><span @class(['size-2.5 rounded-full', 'bg-ink' => $on])></span></span>
                                <span class="flex flex-col gap-px"><span class="text-[15px] font-bold">{{ $label }}</span><span class="text-[13px] text-ink3">{{ str_replace('{host}', Str::ucfirst($hf), $hint) }}</span></span>
                            </button>
                        @endforeach
                    </div>
                    <p class="m-0 flex items-start gap-2.5 rounded-[14px] border border-line bg-surface px-3.5 py-3 text-sm leading-[1.45] text-ink2">
                        <x-icon name="mail" :size="18" class="mt-px shrink-0" />
                        <span>{{ $host ? "We email {$host->name} that it was deleted, with this reason. " : '' }}Players aren't emailed: their stats just drop these games.</span>
                    </p>
                    <p class="m-0 text-[13px] text-ink3">This can't be undone.</p>
                </div>
                <div class="flex shrink-0 gap-2.5 border-t border-line px-6 pt-3.5 pb-5.5">
                    <button type="button" wire:click="cancelDelete" class="h-13 flex-1 cursor-pointer rounded-full border-[1.5px] border-line2 bg-transparent text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Keep open play</button>
                    <button type="button" wire:click="delete" wire:loading.attr="disabled" class="h-13 flex-1 cursor-pointer rounded-full bg-hot text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">Delete open play</button>
                </div>
            </div>
        </div>
    @endif
</x-app-shell>
