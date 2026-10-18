<x-app-shell>
    <x-page>
        <x-page-header eyebrow="Your people" title="Played with" description="Everyone you've partnered with or faced in logged games." />

        @if ($guide)
            @php
                $joined = $guide['joined'];
                $steps = [
                    ['Join an open play', $joined ? "You're in · {$joined->title}, {$joined->whenLabel()}" : 'Pick one on Open plays and tap Join.'],
                    ['Get queued on a court', 'The rotation mixes you with new players.'],
                    ['Scores get logged', 'Your partner and opponents land here, with your record.'],
                ];
                $current = $joined ? 1 : 0; // steps before this one are done
                $steps = collect($steps)->map(fn ($st, $i) => [...$st, $i < $current, $i === $current])->all();
            @endphp
            <section aria-labelledby="pwn-h" class="grid animate-rise items-center gap-x-9 gap-y-5.5 rounded-[26px] bg-ink p-4.5 text-bg lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:p-6">
                <div class="flex min-w-0 flex-col items-start gap-2.5">
                    <span class="font-mono text-xs tracking-[.12em] text-lime-on-ink uppercase">{{ $joined ? "You're in · first game coming up" : 'No logged games yet' }}</span>
                    <h2 id="pwn-h" class="m-0 font-display text-[30px] leading-[1.05] font-extrabold tracking-[-0.035em]">Your people show up here</h2>
                    <p class="m-0 max-w-118 text-[15px] leading-normal opacity-82">
                        {{ $joined ? "Your first game at {$joined->title} adds a partner and two opponents. Records and win rates build from there." : 'Each logged game adds a partner and two opponents. Records and win rates build from there.' }}
                    </p>
                    <a href="{{ route('member.plays') }}" wire:navigate class="mt-2 inline-flex h-12.5 items-center justify-center gap-2 rounded-full bg-lime px-5.5 text-[15px] font-extrabold text-on-lime transition hover:brightness-96 active:scale-97">
                        {{ $joined ? 'See your open play' : 'Find an open play' }}<x-icon name="arrow-right" :size="17" :stroke="2.2" />
                    </a>
                </div>
                <figure class="m-0 flex min-w-0 flex-col gap-2.5">
                    <div role="img" aria-label="A doubles court: you and a partner on one side, two opponents on the other" class="relative h-42 overflow-hidden rounded-[18px] bg-court lg:h-50">
                        <svg viewBox="0 0 440 200" preserveAspectRatio="none" aria-hidden="true" class="absolute inset-0 size-full">
                            <rect x="12" y="12" width="416" height="176" fill="var(--color-court-in)" stroke="rgba(255,255,255,.9)" stroke-width="2.5" vector-effect="non-scaling-stroke" />
                            <rect x="152" y="12" width="136" height="176" fill="rgba(255,255,255,.07)" />
                            <path d="M152 12V188M288 12V188M12 100H152M288 100H428" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="2.5" vector-effect="non-scaling-stroke" />
                            <path d="M220 4V196" stroke="#FFFFFF" stroke-width="5" stroke-dasharray="2 3" vector-effect="non-scaling-stroke" />
                        </svg>
                        <span class="absolute top-[29%] left-[18.6%] flex -translate-1/2 flex-col items-center gap-1">
                            <x-avatar :src="auth()->user()->avatarUrl()" :frame="auth()->user()->frameClass()" :name="auth()->user()->shownName()" :color="auth()->user()->color()" class="size-9.5 text-[13px] shadow-[0_0_0_2px_#FFFFFF] lg:size-11.5 lg:text-[15px]" />
                            <span class="inline-flex h-4.5 items-center rounded-full bg-lime px-2 text-[10px] font-extrabold tracking-[.06em] text-on-lime">YOU</span>
                        </span>
                        @foreach ([['18.6%', '71%', 'PARTNER'], ['81.4%', '29%', 'OPPONENT'], ['81.4%', '71%', 'OPPONENT']] as [$x, $y, $who])
                            <span class="absolute flex -translate-1/2 flex-col items-center gap-1" style="left: {{ $x }}; top: {{ $y }}">
                                <span class="px-pwn-q flex size-9.5 items-center justify-center rounded-full border-2 border-dashed border-white/90 bg-white/10 text-[13px] font-extrabold text-white lg:size-11.5 lg:text-[15px]">?</span>
                                <span class="inline-flex h-4.5 items-center rounded-full bg-[rgba(10,14,11,.55)] px-2 text-[10px] font-extrabold tracking-[.06em] text-white">{{ $who }}</span>
                            </span>
                        @endforeach
                    </div>
                    <figcaption class="font-mono text-xs tracking-widest uppercase opacity-72">One logged game · 1 partner + 2 opponents</figcaption>
                </figure>
            </section>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                @foreach ([
                    ['Best partner', 'Locked', 'Play 5 games with the same partner', 'lock', true],
                    ['Toughest opponent', 'Locked', 'Face the same player 5 times', 'lock', true],
                    ['Different players', '0 so far', 'Your first game adds 3', 'users', false],
                ] as [$k, $v, $t, $icon, $bar])
                    <div class="flex items-center gap-3.5 rounded-[22px] border-[1.5px] border-dashed border-line2 bg-surface p-4 lg:p-5">
                        <span class="flex size-13 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-line2 text-ink3"><x-icon :name="$icon" :size="22" :stroke="1.9" /></span>
                        <div class="flex min-w-0 flex-1 flex-col gap-0.75">
                            <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $k }}</span>
                            <span class="font-display text-[22px] font-extrabold tracking-[-0.03em]">{{ $v }}</span>
                            <span class="text-[13px] text-ink2">{{ $t }}</span>
                            @if ($bar)
                                <div class="mt-1.5 flex items-center gap-2.5">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-[3px] bg-line"><div class="h-full w-0 rounded-[3px] bg-lime-text"></div></div>
                                    <span class="font-mono text-xs text-ink3">0 / 5</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <section aria-labelledby="pwn-s" class="flex flex-col gap-3">
                <h2 id="pwn-s" class="m-0 font-display text-xl font-extrabold tracking-[-0.02em]">How this page fills up</h2>
                <ol class="m-0 grid list-none grid-cols-1 gap-3 p-0 lg:grid-cols-3">
                    @foreach ($steps as $i => [$title, $text, $done, $cur])
                        <li @class(['flex items-start gap-3.5 rounded-[20px] border-[1.5px] bg-surface p-4.5', 'border-ink' => $cur, 'border-line' => ! $cur])>
                            <span @class([
                                'box-border flex size-8.5 shrink-0 items-center justify-center rounded-full border-2 font-mono text-[15px] font-bold',
                                'border-lime bg-lime text-on-lime' => $done,
                                'border-ink bg-ink text-bg' => $cur,
                                'border-dashed border-line2 text-ink3' => ! $done && ! $cur,
                            ])>
                                @if ($done)
                                    <x-icon name="check" :size="16" :stroke="2.6" />
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            <div class="flex min-w-0 flex-col gap-1">
                                <span @class(['font-mono text-[11px] tracking-widest uppercase', 'text-lime-text' => $done, 'text-ink' => $cur, 'text-ink3' => ! $done && ! $cur])>{{ $done ? 'Done' : ($cur ? 'Up next' : 'Then') }}</span>
                                <span class="text-base font-extrabold">{{ $title }}</span>
                                <span class="text-[13px] leading-[1.45] text-ink2">{{ $text }}</span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @else

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
                    @foreach ($rowsPage as $r)
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
                    @foreach ($rowsPage as $r)
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
                    <p class="m-0 px-6 py-7 text-ink2">{{ trim($search) !== '' ? 'No one matches that search yet.' : ($tab === 'opponents' ? 'No opponents yet. They show up after your first logged game.' : 'No partners yet. They show up after your first logged game.') }}</p>
                @endif
                <x-pager :page="$rowsPage" :noun="$tab === 'partners' ? 'partners' : 'opponents'" class="px-5 py-3" />
            </div>
        @endif
    </x-page>
</x-app-shell>
