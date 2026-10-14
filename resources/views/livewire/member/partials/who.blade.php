{{-- Who's in an upcoming open play, or who's here at a live one: read-only, names as each player shares them. --}}
@php($play = $who['play'])
@php($live = $who['live'])
@php($c = $who['counts'])
@php($total = $c['all'])
@php($left = max(0, $play->cap - $total))
@php($spots = $left ? $left.' '.Str::plural('spot', $left).' left' : 'Full')
@php($pad = 'px-3.5 lg:px-4.5')
<div wire:key="who-{{ $play->id }}" x-data x-on:keydown.escape.window="$wire.closeWho()" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
    <button type="button" wire:click="closeWho" aria-label="Close player list" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

    <div role="dialog" aria-modal="true" aria-labelledby="who-title" x-trap.noscroll="true"
         class="relative flex max-h-[92%] w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:max-h-full md:w-215 md:max-w-full md:rounded-[28px]">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $live ? "Live now · who's here" : "Upcoming open play · who's in" }}</span>
                <h2 id="who-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $play->title }}</h2>
                <span class="truncate text-[13px] text-ink3">{{ $play->whenLabel() }} · {{ $play->timeRange() }} · {{ $play->venue }}</span>
            </div>
            <button type="button" wire:click="closeWho" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                <x-icon name="x" :size="18" />
            </button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-6 pt-5 pb-6 [&>*]:shrink-0">
            <div class="grid grid-cols-3 gap-2.5">
                @if ($live)
                    @php($busy = $play->busyCourtCount())
                    @php($tiles = [
                        ['Players', $total.'/'.$play->cap, $spots],
                        ['On court', $who['onCourt'], $busy.' of '.count($play->boardCourts()).' courts<span class="hidden lg:inline"> in play</span>'],
                        ['In line', $who['inLine'], $who['away'] ? $who['away'].'<span class="lg:hidden"> not in yet</span><span class="hidden lg:inline"> not checked in yet</span>' : 'all checked in'],
                    ])
                @else
                    @php($tiles = [
                        ['Joined', $total.'/'.$play->cap, $spots],
                        ['Played with<span class="hidden lg:inline"> you</span>', $who['played'], '<span class="lg:hidden">you before</span><span class="hidden lg:inline">as your partner or opponent</span>'],
                        ['Your spot', $who['joined'] ? 'In' : ($left ? 'Open' : 'Full'), $who['joined'] ? 'see you '.e($play->whenLabel()) : ($left ? 'join to save it' : 'no spots left')],
                    ])
                @endif
                @foreach ($tiles as [$label, $value, $sub])
                    <div class="flex min-w-0 flex-col gap-1 rounded-[18px] border border-line bg-surface px-4 py-3.5">
                        <span class="truncate font-mono text-[11px] tracking-widest text-ink3 uppercase">{!! $label !!}</span>
                        <span class="font-display text-[22px] leading-[1.05] font-extrabold tracking-[-0.03em] lg:text-[28px]">{{ $value }}</span>
                        <span class="text-xs leading-[1.35] text-ink3">{!! $sub !!}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-2.5 lg:flex-row">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-ink3" />
                    <input type="search" wire:model.live.debounce.250ms="whoQ" placeholder="Search by name" aria-label="Search players" autocomplete="off"
                           class="h-11.5 w-full rounded-[14px] border-[1.5px] border-line2 bg-surface px-10.5 text-[15px] text-ink outline-none focus:border-ink [&::-webkit-search-cancel-button]:hidden">
                    @if ($who['q'] !== '')
                        <button type="button" wire:click="$set('whoQ', '')" aria-label="Clear search" class="absolute top-1/2 right-1.5 flex size-8.5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-ink2">
                            <x-icon name="x" :size="15" :stroke="2.2" />
                        </button>
                    @endif
                </div>
                <div role="tablist" aria-label="Show players" class="flex shrink-0 gap-1 overflow-x-auto rounded-2xl border border-line bg-surface p-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($who['tabs'] as $key => $label)
                        @php($on = $who['f'] === $key)
                        <button type="button" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}" wire:click="$set('whoF', '{{ $key }}')"
                                @class(['inline-flex h-9.5 flex-[1_0_auto] cursor-pointer items-center justify-center gap-1.5 rounded-xl px-1.75 text-[13px] font-bold whitespace-nowrap transition-colors duration-200 lg:px-3', 'bg-ink text-bg' => $on, 'text-ink2' => ! $on])>
                            <span>{!! $label !!}</span>
                            <span @class(['inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 font-mono text-[11px] font-semibold', 'bg-white/18' => $on, 'bg-surface2' => ! $on])>{{ $c[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col overflow-hidden rounded-[20px] border border-line bg-surface">
                <div class="flex items-center justify-between gap-2.5 {{ $pad }} py-2.5 font-mono text-[11px] tracking-widest text-ink3 uppercase">
                    <span role="status" aria-live="polite">{{ $who['rows']->count() === $total ? $total.' '.Str::plural('player', $total) : $who['rows']->count().' of '.$total.' players' }}</span>
                    <span>{{ $live ? 'On court, then the queue' : 'In the order they joined' }}</span>
                </div>
                @if ($who['rows']->isNotEmpty())
                    <div role="list" aria-label="Players">
                        @foreach ($who['page'] as $r)
                            @php($p = $r['p'])
                            @php($pill = match ($r['st']) { 'court' => 'Court '.$r['court'], 'line' => 'In line · #'.$r['pos'], 'out' => 'Stepped out', 'away' => 'Not checked in', default => null })
                            <div role="listitem" wire:key="wr-{{ $p->id }}" class="flex items-center gap-2.5 border-t border-line {{ $pad }} py-3 lg:gap-3.5">
                                <span class="w-5 shrink-0 font-mono text-xs text-ink3">{{ $who['page']->firstItem() + $loop->index }}</span>
                                <x-avatar :src="$p->avatarUrl()" :frame="$p->frameClass()" :name="$p->name" :color="$p->user ? $p->color() : '#E6E3D6'" class="size-10 text-[13px]" />
                                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span class="flex min-w-0 items-center gap-1.5">
                                        <span class="min-w-0 truncate text-[15px] font-bold">{{ $p->name }}</span>
                                        @if ($r['me'])
                                            <span class="inline-flex h-5 shrink-0 items-center rounded-full bg-lime px-1.75 text-[10px] font-extrabold text-on-lime">YOU</span>
                                        @endif
                                        @if ($r['host'])
                                            <span class="inline-flex h-5 shrink-0 items-center rounded-full bg-ink px-1.75 text-[10px] font-extrabold text-bg">HOST</span>
                                        @endif
                                        @if ($r['guest'])
                                            <span class="inline-flex h-5 shrink-0 items-center rounded-full border border-line2 bg-surface2 px-1.75 text-[11px] font-bold text-ink2">Guest</span>
                                        @endif
                                    </span>
                                    @if ($r['sub'] !== '')
                                        <span class="text-xs leading-[1.35] [overflow-wrap:anywhere] text-ink3">{{ $r['sub'] }}</span>
                                    @endif
                                </div>
                                @if ($pill)
                                    <span @class(['inline-flex h-7 shrink-0 items-center rounded-full border px-2.5 text-xs font-extrabold whitespace-nowrap',
                                        'border-lime bg-lime text-on-lime' => $r['st'] === 'court',
                                        'border-line bg-surface2 text-ink' => $r['st'] === 'line',
                                        'border-line2 text-ink2' => $r['st'] === 'out',
                                        'border-line2 text-ink3' => $r['st'] === 'away'])>{{ $pill }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <x-pager :page="$who['page']" noun="players" class="border-t border-line {{ $pad }} py-3" />
                @elseif ($total)
                    <p class="m-0 border-t border-line {{ $pad }} py-5.5 text-sm text-ink2">
                        {{ $who['q'] !== '' ? 'No one here matches “'.$who['q'].'”.' : match ($who['f']) { 'played' => "You haven't played with anyone here yet.", 'away' => 'Everyone has checked in.', 'court' => 'No one is on court right now.', default => 'No players in this group.' } }}
                    </p>
                @else
                    <p class="m-0 border-t border-line {{ $pad }} py-5.5 text-sm text-ink2">No one has joined yet. Be the first.</p>
                @endif
            </div>

            <p class="m-0 flex items-start gap-2 text-[12.5px] leading-[1.45] text-ink3">
                <x-icon name="shield" :size="14" class="mt-px shrink-0" />
                <span>Players show up by the name they chose to share. Emails and payments stay with the organizer.</span>
            </p>
        </div>

        <div class="flex shrink-0 gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-4.5">
            @if ($live && $play->share_code)
                <a href="{{ route('live', $play->share_code) }}" wire:navigate class="inline-flex h-12.5 items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">
                    <x-icon name="eye" :size="16" />Watch live
                </a>
            @endif
            @if (! $live && ! $who['joined'] && $left)
                <button type="button" wire:click="joinPlay({{ $play->id }})" class="inline-flex h-12.5 cursor-pointer items-center justify-center rounded-full border-2 border-cta-border bg-lime px-5.5 text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Join open play</button>
            @endif
            <button type="button" wire:click="closeWho" class="h-12.5 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">Done</button>
        </div>
    </div>
</div>
