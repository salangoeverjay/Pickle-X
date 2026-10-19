{{-- Who joined an upcoming open play: tiles, search, filter tabs and each player's payment. --}}
@php($play = $roster['play'])
@php($c = $roster['counts'])
@php($left = max(0, $play->cap - $c['all']))
@php($peso = fn ($n) => '₱'.number_format(round($n)))
@php($pad = 'px-3.5 lg:px-4.5')
{{-- Co-hosts see the list; the host marks payments and edits. --}}
@php($owner = $play->organizer_id === auth()->id())
@php($hostFirst = $play->organizer?->firstName() ?? 'The host')
@php($yr = $owner ? 'your' : $hostFirst."'s")
<div wire:key="roster-{{ $play->id }}" x-data x-on:keydown.escape.window="$wire.closeRoster()" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
    <button type="button" wire:click="closeRoster" aria-label="Close player list" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

    <div role="dialog" aria-modal="true" aria-labelledby="roster-title" x-trap.noscroll="true"
         class="relative flex max-h-[92%] w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:max-h-full md:w-215 md:max-w-full md:rounded-[28px]">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $owner ? 'Upcoming open play: players' : "Upcoming open play: you're co-hosting" }}</span>
                <h2 id="roster-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $play->title }}</h2>
                <span class="truncate text-[13px] text-ink3">{{ $play->whenLabel() }}, {{ $play->timeRange() }}, {{ $play->venue }}</span>
            </div>
            <button type="button" wire:click="closeRoster" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                <x-icon name="x" :size="18" />
            </button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-6 pt-5 pb-6 [&>*]:shrink-0">
            @unless ($owner)
                <p class="m-0 flex items-start gap-2.5 rounded-[16px] border border-line bg-surface px-3.5 py-3 text-sm leading-[1.45] text-ink2"><x-icon name="users" :size="18" class="mt-px shrink-0" />
                    <span>{{ $roster['free'] ? "You can see who joined. {$hostFirst} handles any changes to the list." : "You can see who joined and paid. Only {$hostFirst} marks payments." }}</span>
                </p>
            @endunless
            <div class="grid grid-cols-3 gap-2.5">
                @php($tiles = [['Joined', $c['all'].'/'.$play->cap, $left ? $left.'<span class="lg:hidden"> left</span><span class="hidden lg:inline"> '.Str::plural('spot', $left).' left</span>' : 'Full']])
                @if ($roster['free'])
                    @php($tiles[] = ['Guests', $c['guests'], $c['guests'] === 1 ? 'no PickleX account' : 'no PickleX accounts'])
                    @php($tiles[] = ['Fee', 'Free', 'nothing to collect'])
                @else
                    @php($tiles[] = ['Collected', $peso($c['paid'] * $roster['fee']), 'of '.$peso($c['all'] * $roster['fee']).'<span class="hidden lg:inline">, '.e($play->fee).' each</span>'])
                    @php($tiles[] = ['<span class="lg:hidden">Unpaid</span><span class="hidden lg:inline">To collect</span>', $c['unpaid'], $roster['sent'] ? $roster['sent'].' sent<span class="hidden lg:inline">, '.($owner ? 'check your '.e($roster['account']) : e($hostFirst).' checks them').'</span>' : ($c['unpaid'] ? 'not paid yet' : 'all paid')])
                @endif
                @foreach ($tiles as [$label, $value, $sub])
                    <div class="flex min-w-0 flex-col gap-1 rounded-[18px] border border-line bg-surface px-4 py-3.5">
                        <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{!! $label !!}</span>
                        <span class="font-display text-[22px] leading-[1.05] font-extrabold tracking-[-0.03em] lg:text-[28px]">{{ $value }}</span>
                        <span class="text-xs leading-[1.35] text-ink3">{!! $sub !!}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-2.5 lg:flex-row">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-ink3" />
                    <input type="search" wire:model.live.debounce.250ms="rosterQ" placeholder="Search by name or email" aria-label="Search players" autocomplete="off"
                           class="h-11.5 w-full rounded-[14px] border-[1.5px] border-line2 bg-surface px-10.5 text-[15px] text-ink outline-none focus:border-ink [&::-webkit-search-cancel-button]:hidden">
                    @if ($roster['q'] !== '')
                        <button type="button" wire:click="$set('rosterQ', '')" aria-label="Clear search" class="absolute top-1/2 right-1.5 flex size-8.5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-ink2">
                            <x-icon name="x" :size="16" />
                        </button>
                    @endif
                </div>
                <div role="tablist" aria-label="Show players" class="flex shrink-0 gap-1 overflow-x-auto rounded-2xl border border-line bg-surface p-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($roster['tabs'] as $key => $label)
                        @php($on = $roster['f'] === $key)
                        <button type="button" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}" wire:click="$set('rosterF', '{{ $key }}')"
                                @class(['inline-flex h-9.5 flex-[1_0_auto] cursor-pointer items-center justify-center gap-1.5 rounded-xl px-1.75 text-[13px] font-bold whitespace-nowrap transition-colors duration-200 lg:px-3', 'bg-ink text-bg' => $on, 'text-ink2' => ! $on])>
                            <span>{{ $label }}</span>
                            <span @class(['inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 font-mono text-[11px] font-semibold', 'bg-white/18' => $on, 'bg-surface2' => ! $on])>{{ $c[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col overflow-hidden rounded-[20px] border border-line bg-surface">
                <div class="flex items-center justify-between gap-2.5 {{ $pad }} py-2.5 font-mono text-[11px] tracking-widest text-ink3 uppercase">
                    <span role="status" aria-live="polite">{{ $roster['rows']->count() === $c['all'] ? $c['all'].' '.Str::plural('player', $c['all']) : $roster['rows']->count().' of '.$c['all'].' players' }}</span>
                    <span>In the order they joined</span>
                </div>
                @if ($roster['rows']->isNotEmpty())
                    <div role="list" aria-label="Players who joined">
                        @foreach ($roster['page'] as $r)
                            @php($p = $r['p'])
                            <div role="listitem" wire:key="rr-{{ $p->id }}" class="flex flex-wrap items-center gap-x-2.5 gap-y-2 border-t border-line {{ $pad }} py-3 lg:flex-nowrap lg:gap-x-3.5">
                                <span class="w-5 shrink-0 font-mono text-xs text-ink3">{{ $r['n'] }}</span>
                                <x-avatar :src="$p->avatarUrl()" :frame="$p->frameClass()" :name="$p->name" :color="$p->user ? $p->color() : '#E6E3D6'" class="size-10 text-[13px]" />
                                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span class="flex min-w-0 items-center gap-1.5">
                                        <span class="min-w-0 truncate text-[15px] font-bold">{{ $p->name }}</span>
                                        @if ($r['guest'])
                                            <span class="inline-flex h-5 shrink-0 items-center rounded-full border border-line2 bg-surface2 px-1.75 text-[11px] font-bold text-ink2">Guest</span>
                                        @endif
                                        @if ($p->user_id === auth()->id())
                                            <span class="inline-flex h-5 shrink-0 items-center rounded-full bg-lime px-1.75 text-[10px] font-extrabold text-on-lime">YOU</span>
                                        @endif
                                    </span>
                                    <span class="text-xs leading-[1.35] text-ink3">
                                        {{ $r['guest'] ? ($p->via_link ? "Guest, joined with {$yr} share link".($p->email ? ', '.$p->email : '') : "Guest, no PickleX account, from {$yr} paid list") : ($p->user->usesDisplayName() ? $p->user->name.', ' : '').($p->from_list ? "Added from {$yr} paid list, ".$p->created_at->format('M j') : ($p->via_link ? "Joined with {$yr} share link, " : 'Joined in the app, ').$p->created_at->format('M j, g:i A')) }}
                                    </span>
                                    @if ($r['sent'])
                                        <span class="flex items-center gap-1.25 text-xs font-bold text-ink"><x-icon name="clock" :size="14" />Says they sent {{ $play->fee }}, {{ $owner ? 'check your '.$roster['account'] : $hostFirst.' checks it' }}</span>
                                    @endif
                                    @unless ($r['guest'])
                                        <span class="truncate font-mono text-[11px] text-ink2 lg:hidden">{{ $p->user->email }}</span>
                                    @endunless
                                    @if ($p->user?->phone)
                                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $p->user->phone) }}" class="flex items-center gap-1.25 self-start font-mono text-[11px] text-ink2 underline-offset-2 hover:underline"><x-icon name="phone" :size="12" />{{ $p->user->phone }}</a>
                                    @endif
                                </div>
                                <span class="hidden w-57.5 shrink-0 truncate font-mono text-xs text-ink2 lg:block">{{ $r['guest'] ? 'No account' : $p->user->email }}</span>
                                @if (! $roster['free'] && $owner)
                                    <div class="ml-20 flex w-[calc(100%-80px)] shrink-0 items-center gap-1.5 lg:ml-0 lg:w-37.5 lg:justify-end">
                                        <div role="group" aria-label="Payment" class="inline-flex gap-0.5 rounded-full border border-line2 bg-bg p-0.75">
                                            <button type="button" wire:click="setPaid({{ $p->id }}, false)" aria-pressed="{{ $r['paid'] ? 'false' : 'true' }}" aria-label="Not paid: {{ $p->name }}"
                                                    @class(['h-8 cursor-pointer rounded-full px-2.75 text-xs font-bold whitespace-nowrap transition-colors duration-200 active:scale-97', 'bg-hot-soft text-hot' => ! $r['paid'], 'text-ink3' => $r['paid']])>Unpaid</button>
                                            <button type="button" wire:click="setPaid({{ $p->id }}, true)" aria-pressed="{{ $r['paid'] ? 'true' : 'false' }}" aria-label="Paid: {{ $p->name }}"
                                                    @class(['inline-flex h-8 cursor-pointer items-center gap-1 rounded-full px-2.75 text-xs font-bold whitespace-nowrap transition-colors duration-200 active:scale-97', 'bg-lime text-on-lime' => $r['paid'], 'text-ink3' => ! $r['paid']])>
                                                @if ($r['paid'])
                                                    <x-icon name="check" :size="14" :stroke="2.4" />
                                                @endif
                                                <span>Paid</span>
                                            </button>
                                        </div>
                                    </div>
                                @elseif (! $roster['free'])
                                    {{-- Co-hosts see who has paid; only the host changes it. --}}
                                    <div class="ml-20 flex w-[calc(100%-80px)] shrink-0 items-center lg:ml-0 lg:w-37.5 lg:justify-end">
                                        <span role="status" aria-label="{{ $p->name }}: {{ $r['paid'] ? 'paid' : ($r['sent'] ? 'says they sent it, not checked yet' : 'not paid') }}"
                                              @class(['inline-flex h-8 items-center gap-1 rounded-full px-3 text-xs font-bold whitespace-nowrap', 'bg-lime-soft text-lime-text' => $r['paid'], 'bg-surface2 text-ink' => ! $r['paid'] && $r['sent'], 'bg-surface2 text-ink2' => ! $r['paid'] && ! $r['sent']])>
                                            @if ($r['paid'])
                                                <x-icon name="check" :size="12" :stroke="3" />
                                            @endif
                                            {{ $r['paid'] ? 'Paid' : ($r['sent'] ? 'Sent, not checked' : 'Not paid') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <x-pager :page="$roster['page']" noun="players" class="border-t border-line {{ $pad }} py-3" />
                @elseif ($c['all'])
                    <p class="m-0 border-t border-line {{ $pad }} py-5.5 text-sm text-ink2">
                        {{ $roster['q'] !== '' ? 'No one here matches “'.$roster['q'].'”.' : match ($roster['f']) { 'unpaid' => 'Everyone has paid.', 'guests' => 'No guests on this open play.', default => 'No players in this group.' } }}
                    </p>
                @else
                    <p class="m-0 border-t border-line {{ $pad }} py-5.5 text-sm text-ink2">No one has joined yet. Players show up here when they join or you add them to the paid list.</p>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-4.5">
            @if ($owner)
            <button type="button" wire:click="rosterEdit" class="inline-flex h-12.5 cursor-pointer items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 bg-transparent px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">
                <x-icon name="edit" :size="17" />Edit open play
            </button>
            @endif
            <button type="button" wire:click="closeRoster" class="h-12.5 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">Done</button>
        </div>
    </div>
</div>
