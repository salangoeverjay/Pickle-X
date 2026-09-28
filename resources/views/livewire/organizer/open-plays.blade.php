<x-app-shell>
    <x-page>
        <x-page-header :eyebrow="$club" title="Your open plays">
            <button type="button" wire:click="openCreate" class="inline-flex h-12.5 cursor-pointer items-center gap-2 rounded-full border-2 border-cta-border bg-lime px-5.5 text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">
                <x-icon name="plus" :size="18" :stroke="2.4" />Create open play
            </button>
        </x-page-header>

        @foreach ($live as $play)
            @php($longest = $play->longestCourtIndex())
            <div wire:key="live-{{ $play->id }}" class="relative grid animate-fade items-center gap-5.5 overflow-hidden rounded-[28px] bg-court p-5.5 text-white lg:grid-cols-2 lg:pt-7 lg:pr-7 lg:pb-7 lg:pl-8">
                <div class="relative flex flex-col gap-3.5">
                    <span class="inline-flex h-7 items-center gap-2 self-start rounded-full bg-black/25 px-2.75 font-mono text-[11px] tracking-widest">
                        <span class="size-1.75 animate-live rounded-full bg-lime"></span>LIVE NOW · {{ ago((int) $play->started_at?->diffInSeconds(now())) }}
                    </span>
                    <h2 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[44px]">{{ $play->title }}</h2>
                    <span class="text-sm text-white/90">{{ $play->venue }} · {{ $play->timeRange() }}</span>
                    <div class="mt-1 mb-0.5 grid max-w-120 grid-cols-2 gap-2 lg:grid-cols-4">
                        @foreach ([[$play->busyCourtCount() * 4, 'on court'], [$play->queuedPlayerCount(), 'in queue'], [$play->games_count, 'games'], [$play->checkedInCount().'/'.count($play->sessionPlayerIds()), 'checked in']] as [$n, $label])
                            <div class="flex flex-col gap-0.5 rounded-[14px] bg-white/12 px-3 py-2.5">
                                <span class="font-display text-[22px] leading-[1.1] font-extrabold">{{ $n }}</span>
                                <span class="text-xs font-semibold whitespace-nowrap text-white/85">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <a href="{{ route('organizer.board') }}" wire:navigate class="inline-flex h-12.5 items-center gap-2.5 rounded-full bg-lime px-5.5 text-[15px] font-extrabold text-on-lime transition hover:brightness-96 active:scale-97">
                            Open court board<x-icon name="arrow-right" :size="18" :stroke="2.2" />
                        </a>
                    </div>
                </div>
                @if ($longest !== null)
                    <x-live-court :play="$play" :court="$play->boardCourts()[$longest]" :index="$longest" class="h-42.5 shadow-[0_30px_50px_-25px_rgba(0,0,0,.6)] lg:h-52.5" />
                @endif
            </div>
        @endforeach

        <section class="flex flex-col gap-3">
            <h2 class="m-0 font-display text-[22px] font-bold tracking-[-0.02em]">Upcoming</h2>
            @forelse ($upcoming as $play)
                @php($today = $play->starts_at->isToday())
                <div wire:key="up-{{ $play->id }}" class="flex flex-col gap-4.5 rounded-3xl border border-line bg-surface p-4.5 transition duration-300 hover:-translate-y-1 hover:shadow-card lg:px-6 lg:py-5.5">
                    <div class="flex items-center gap-3.5 lg:gap-5">
                        <div class="flex h-16.5 w-14.5 shrink-0 flex-col items-center justify-center gap-0.75 rounded-[18px] border border-line bg-surface2 lg:h-20 lg:w-18">
                            <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $today ? 'Today' : $play->starts_at->format('M') }}</span>
                            @unless ($today)
                                <span class="font-display text-[28px] leading-none font-extrabold tracking-[-0.04em]">{{ $play->starts_at->format('j') }}</span>
                                <span class="text-[11px] text-ink3">{{ $play->starts_at->format('D') }}</span>
                            @endunless
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                            <span class="font-display text-[21px] leading-[1.2] font-bold tracking-[-0.02em]">{{ $play->title }}</span>
                            <span class="text-sm leading-[1.45] text-ink2">{{ $play->timeRange() }} · {{ $play->venue }} · {{ $play->courts }} courts</span>
                        </div>
                        <button type="button" wire:click="startSession({{ $play->id }})" class="hidden h-11.5 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 bg-transparent px-5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97 lg:inline-flex">
                            <x-icon name="court" :size="16" />Start session
                        </button>
                    </div>
                    <div class="flex flex-col gap-3.5 lg:pl-23">
                        <div class="flex max-w-105 items-center gap-3">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-[3px] bg-surface2">
                                <div class="h-full rounded-[3px] bg-ink" style="width: {{ min(100, round($play->playerCount() / max(1, $play->cap) * 100)) }}%"></div>
                            </div>
                            <span class="shrink-0 font-mono text-xs text-ink3">{{ $play->playerCount() }}/{{ $play->cap }} joined</span>
                        </div>
                        @if ($play->paid_count || $play->hasPaymentQr())
                            <div class="flex flex-wrap gap-2">
                                @if ($play->paid_count)
                                    @php($guests = $play->paid_count - $play->paid_linked_count)
                                    <span class="inline-flex h-6.5 items-center gap-1.5 rounded-full bg-lime-soft px-2.5 text-xs font-bold text-lime-text">
                                        <x-icon name="check" :size="14" :stroke="2.4" />{{ $play->paid_count }} paid · {{ $play->paid_linked_count }} linked · {{ $guests }} {{ Str::plural('guest', $guests) }}
                                    </span>
                                @endif
                                @if ($play->hasPaymentQr())
                                    <span class="inline-flex h-6.5 items-center gap-1.5 rounded-full border border-line bg-surface2 px-2.5 text-xs font-bold text-ink2">
                                        <x-icon name="qr" :size="14" />{{ $play->payMethodLabel() }} QR · {{ $play->feeLabel() }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <button type="button" wire:click="startSession({{ $play->id }})" class="flex h-12 cursor-pointer items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 bg-transparent px-4.5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97 lg:hidden">
                        <x-icon name="court" :size="16" />Start session
                    </button>
                </div>
            @empty
                <p class="m-0 rounded-[20px] border-[1.5px] border-dashed border-line2 p-5 text-ink2">No upcoming open plays. Create one and members can start joining.</p>
            @endforelse
        </section>

        @if ($past->isNotEmpty())
            <section class="flex flex-col gap-3">
                <h2 class="m-0 font-display text-[22px] font-bold tracking-[-0.02em]">Past sessions</h2>
                <div class="overflow-hidden rounded-[22px] border border-line bg-surface">
                    @foreach ($past as $play)
                        <div wire:key="past-{{ $play->id }}" class="flex items-center gap-3.5 border-b border-line px-4.5 py-3.5 last:border-b-0">
                            <span class="flex size-9.5 shrink-0 items-center justify-center rounded-xl bg-surface2 text-ink2"><x-icon name="check" :size="18" /></span>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <span class="font-bold">{{ $play->title }}</span>
                                <span class="text-[13px] text-ink3">{{ $play->whenLabel() }} · {{ $play->playerCount() }} players</span>
                            </div>
                            <span class="font-mono text-[13px] text-ink2">{{ $play->games_count }} games</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </x-page>

    {{-- Create open play: bottom sheet on mobile, right drawer on desktop. --}}
    <div x-show="$wire.create" x-cloak x-on:keydown.escape.window="$wire.$set('create', false)" class="fixed inset-0 z-60 flex items-end justify-end">
        <button type="button" x-on:click="$wire.$set('create', false)" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

        <form x-data="payQr(@js($lastQrUrl))" x-on:submit.prevent="publish()" x-on:create-published.window="reset($event.detail.qrUrl)" novalidate
              role="dialog" aria-modal="true" aria-labelledby="create-title"
              x-show="$wire.create" x-trap.noscroll="$wire.create"
              x-transition:enter="transition-transform duration-[420ms] ease-[cubic-bezier(.2,.8,.2,1)]"
              x-transition:enter-start="translate-y-full lg:translate-x-full lg:translate-y-0"
              x-transition:enter-end="translate-x-0 translate-y-0"
              class="relative flex h-[92%] w-full flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_-10px_60px_rgba(0,0,0,.3)] lg:h-full lg:w-130 lg:rounded-l-[28px] lg:rounded-tr-none">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                <div class="flex flex-col gap-0.5">
                    <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $club }}</span>
                    <h2 id="create-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">Create open play</h2>
                </div>
                <button type="button" x-on:click="$wire.$set('create', false)" aria-label="Close" class="flex size-11 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                    <x-icon name="x" :size="18" :stroke="2.2" />
                </button>
            </div>

            <div class="flex min-h-0 flex-1 flex-col gap-4.5 overflow-x-hidden overflow-y-auto px-6 py-5">
                <x-field label="Title" id="c-title" wire:model="title" placeholder="e.g. Saturday Sunrise Rally" />
                <x-field label="Venue" id="c-venue" wire:model="venue" placeholder="Court name and city" />

                {{-- Date + time pickers (resources/js/when-picker.js). Alpine-rendered, so Livewire leaves it alone. --}}
                <div x-data="whenPicker(@js(today()->toDateString()))" wire:ignore class="flex flex-col gap-2.5">
                    <div class="grid grid-cols-6 gap-2.5">
                        <div class="col-span-6 flex flex-col gap-2">
                            <span id="c-date-l" class="text-[13px] font-bold text-ink2">Date</span>
                            <button type="button" x-on:click="toggle('date')" aria-labelledby="c-date-l c-date-v" x-bind:aria-expanded="pick === 'date'" aria-controls="c-cal"
                                    :class="pick === 'date' ? 'border-ink' : 'border-line'" class="flex h-12.5 w-full cursor-pointer items-center gap-2.5 rounded-[14px] border-[1.5px] bg-surface pr-3 pl-3.5 text-left text-ink transition hover:brightness-96 active:scale-97">
                                <x-icon name="calendar" :size="19" />
                                <span id="c-date-v" class="min-w-0 flex-1 truncate text-[15px] font-semibold" x-text="dateLabel(date)"></span>
                                <x-icon name="chevron-down" :size="18" class="transition-transform" x-bind:class="pick === 'date' && 'rotate-180'" />
                            </button>
                        </div>
                        @foreach (['start' => ['Starts', 'starts'], 'end' => ['Ends', 'ends']] as $which => [$label, $prop])
                            <div class="col-span-3 flex flex-col gap-2">
                                <span id="c-{{ $which }}-l" class="text-[13px] font-bold text-ink2">{{ $label }}</span>
                                <button type="button" x-on:click="toggle('{{ $which }}')" aria-labelledby="c-{{ $which }}-l c-{{ $which }}-v" x-bind:aria-expanded="pick === '{{ $which }}'" aria-controls="c-time"
                                        :class="pick === '{{ $which }}' ? 'border-ink' : 'border-line'" class="relative flex h-12.5 w-full cursor-pointer items-center gap-2 rounded-[14px] border-[1.5px] bg-surface pr-2.5 pl-3 text-left text-ink transition hover:brightness-96 active:scale-97">
                                    <x-icon name="clock" :size="18" />
                                    <span id="c-{{ $which }}-v" class="min-w-0 flex-1 font-mono text-sm font-semibold whitespace-nowrap" x-text="fmtTime({{ $prop }})"></span>
                                    @if ($which === 'end')
                                        <span x-show="overnight" x-cloak class="absolute -top-2.25 right-2 inline-flex h-4.5 items-center rounded-full bg-ink px-1.5 text-[10px] font-extrabold text-bg">+1 day</span>
                                    @endif
                                </button>
                            </div>
                        @endforeach
                    </div>

                    {{-- Calendar --}}
                    <div x-show="pick === 'date'" x-cloak id="c-cal" role="dialog" aria-label="Choose a date" class="flex animate-fade flex-col gap-3 rounded-[20px] border-[1.5px] border-line bg-surface p-3.5 shadow-card">
                        <div class="grid grid-cols-4 gap-1.5">
                            <template x-for="q in quick" :key="q.iso">
                                <button type="button" x-on:click="setDate(q.iso)" x-bind:aria-pressed="date === q.iso" :class="date === q.iso ? 'border-ink bg-lime-soft' : 'border-line'"
                                        class="flex h-12.5 min-w-0 cursor-pointer flex-col items-center justify-center gap-px rounded-[14px] border-[1.5px] px-1 text-center text-ink transition hover:brightness-96 active:scale-97">
                                    <span class="max-w-full truncate text-[13px] leading-[1.2] font-bold" x-text="q.label"></span>
                                    <span class="text-xs leading-[1.2] font-medium text-ink3" x-text="q.sub"></span>
                                </button>
                            </template>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <button type="button" x-on:click="month(-1)" x-bind:disabled="!canPrev" aria-label="Previous month" class="flex size-10 cursor-pointer items-center justify-center rounded-xl border border-line bg-surface2 text-ink disabled:opacity-40 transition hover:brightness-96 active:scale-97"><x-icon name="chevron-left" :size="18" /></button>
                            <span role="status" aria-live="polite" class="font-display text-lg font-extrabold tracking-[-0.02em]" x-text="monthLabel"></span>
                            <button type="button" x-on:click="month(1)" x-bind:disabled="!canNext" aria-label="Next month" class="flex size-10 cursor-pointer items-center justify-center rounded-xl border border-line bg-surface2 text-ink disabled:opacity-40 transition hover:brightness-96 active:scale-97"><x-icon name="chevron-right" :size="18" /></button>
                        </div>
                        <div aria-hidden="true" class="grid grid-cols-7 gap-0.5">
                            @foreach (['S' => 'Sunday', 'M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 'Th' => 'Thursday', 'F' => 'Friday', 'Sa' => 'Saturday'] as $short => $full)
                                <abbr title="{{ $full }}" class="text-center font-mono text-[11px] font-semibold tracking-[.08em] text-ink3 no-underline">{{ $short[0] }}</abbr>
                            @endforeach
                        </div>
                        <div x-ref="cal" role="group" x-bind:aria-label="monthLabel + '. Use the arrow keys to move between days.'" x-on:keydown="calKey($event)" class="flex flex-col gap-0.5">
                            <template x-for="(week, w) in weeks" :key="calM + w">
                                <div class="grid grid-cols-7 gap-0.5">
                                    <template x-for="d in week" :key="d.iso">
                                        <button type="button" :data-iso="d.iso" :tabindex="d.focus ? 0 : -1" x-on:click="setDate(d.iso)" :disabled="d.off" :aria-pressed="d.sel" :aria-current="d.isToday ? 'date' : null" :aria-label="d.label" x-text="d.n"
                                                :class="[d.sel ? 'border-transparent bg-ink text-bg font-extrabold' : (d.isToday ? 'border-ink font-extrabold text-ink' : 'border-transparent font-semibold text-ink'), d.off ? 'opacity-30' : (d.out ? 'opacity-45' : '')]"
                                                class="h-10 cursor-pointer rounded-xl border-[1.5px] text-sm tabular-nums transition-[box-shadow,transform] enabled:hover:shadow-[inset_0_0_0_1.5px_var(--color-line2)] enabled:active:scale-94 disabled:cursor-not-allowed"></button>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-end"><button type="button" x-on:click="done()" class="h-10 cursor-pointer rounded-full bg-ink px-4.5 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">Done</button></div>
                    </div>

                    {{-- Start / end time --}}
                    <div x-show="pick === 'start' || pick === 'end'" x-cloak id="c-time" role="dialog" x-bind:aria-label="pick === 'start' ? 'Start time' : 'End time'" class="flex animate-fade flex-col gap-3.5 rounded-[20px] border-[1.5px] border-line bg-surface p-3.5 shadow-card">
                        <div class="flex items-baseline justify-between gap-2.5">
                            <label for="c-time-in" class="font-display text-lg font-extrabold tracking-[-0.02em]" x-text="pick === 'start' ? 'Start time' : 'End time'"></label>
                            <span class="text-xs text-ink3">Type any time or tap below</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center gap-1.5">
                                <button type="button" x-on:click="nudge(-5)" aria-label="5 minutes earlier" class="flex h-13 w-11 shrink-0 cursor-pointer items-center justify-center rounded-[14px] border-[1.5px] border-line bg-surface2 text-ink transition hover:brightness-96 active:scale-97"><x-icon name="minus" :size="18" /></button>
                                <input id="c-time-in" type="text" autocomplete="off" spellcheck="false" aria-describedby="c-time-hint" :value="typed ?? fmtTime(cur)" x-on:input="typed = $event.target.value; typedErr = ''" x-on:blur="commit()" x-on:keydown.enter.prevent="commit()"
                                       x-bind:aria-invalid="!!typedErr" :class="typedErr ? 'border-hot' : 'border-ink'" class="h-13 min-w-0 flex-1 rounded-[14px] border-[1.5px] bg-bg px-2 text-center font-mono text-lg font-semibold text-ink outline-none">
                                <button type="button" x-on:click="nudge(5)" aria-label="5 minutes later" class="flex h-13 w-11 shrink-0 cursor-pointer items-center justify-center rounded-[14px] border-[1.5px] border-line bg-surface2 text-ink transition hover:brightness-96 active:scale-97"><x-icon name="plus" :size="18" /></button>
                                <div role="radiogroup" aria-label="AM or PM" class="flex shrink-0 gap-1 rounded-[14px] border-[1.5px] border-line bg-surface2 p-1">
                                    @foreach (['AM' => 'false', 'PM' => 'true'] as $label => $isPm)
                                        <button type="button" role="radio" x-bind:aria-checked="pm === {{ $isPm }}" x-on:click="setTime(pick, at(h12, min, {{ $isPm }}))" :class="pm === {{ $isPm }} ? 'bg-ink text-bg' : 'text-ink'" class="h-10 w-11 cursor-pointer rounded-[10px] text-[13px] font-extrabold transition hover:brightness-96 active:scale-97">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <span x-show="typedErr" x-cloak role="alert" class="text-[13px] font-semibold text-hot" x-text="typedErr"></span>
                            <span id="c-time-hint" class="text-xs text-ink3">For example 6:10 PM, 610p or 18:10. The - and + buttons move it 5 minutes.</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <span id="c-time-h" class="text-xs font-bold text-ink3">Hour</span>
                            <div role="radiogroup" aria-labelledby="c-time-h" class="grid grid-cols-6 gap-1.5">
                                @foreach ([12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] as $h)
                                    <button type="button" role="radio" x-bind:aria-checked="h12 === {{ $h }}" x-bind:aria-label="'{{ $h }} ' + (pm ? 'PM' : 'AM')" x-on:click="setTime(pick, at({{ $h }}, min, pm))"
                                            :class="h12 === {{ $h }} ? 'bg-ink text-bg border-ink' : 'bg-surface2 text-ink border-line'" class="h-10 cursor-pointer rounded-xl border-[1.5px] font-mono text-[15px] font-semibold transition hover:brightness-96 active:scale-97">{{ $h }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <span id="c-time-m" class="text-xs font-bold text-ink3">Minutes</span>
                            <div role="radiogroup" aria-labelledby="c-time-m" class="grid grid-cols-6 gap-1.5">
                                @foreach (range(0, 55, 5) as $m)
                                    <button type="button" role="radio" x-bind:aria-checked="min === {{ $m }}" aria-label="{{ $m }} minutes" x-on:click="setTime(pick, at(h12, {{ $m }}, pm))"
                                            :class="min === {{ $m }} ? 'bg-ink text-bg border-ink' : 'bg-surface2 text-ink border-line'" class="h-10 cursor-pointer rounded-xl border-[1.5px] font-mono text-sm font-semibold transition hover:brightness-96 active:scale-97">:{{ sprintf('%02d', $m) }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold text-ink3" x-text="pick === 'start' ? 'Quick picks' : 'Session length'"></span>
                            <div x-show="pick === 'start'" class="flex flex-wrap gap-1.5">
                                <template x-for="[label, t] in startPresets" :key="t">
                                    <button type="button" x-on:click="setTime('start', t)" x-bind:aria-pressed="starts === t" :class="starts === t ? 'bg-ink text-bg border-ink' : 'bg-surface2 text-ink border-line'" class="flex h-9.5 min-w-18 flex-1 cursor-pointer items-center justify-center rounded-full border-[1.5px] px-2.5 text-[13px] font-bold whitespace-nowrap transition hover:brightness-96 active:scale-97" x-text="label"></button>
                                </template>
                            </div>
                            <div x-show="pick === 'end'" class="flex flex-wrap gap-1.5">
                                <template x-for="[n, label] in lengthPresets" :key="n">
                                    <button type="button" x-on:click="setTime('end', lengthEnd(n))" x-bind:aria-pressed="duration === n" :class="duration === n ? 'bg-ink text-bg border-ink' : 'bg-surface2 text-ink border-line'" class="flex h-9.5 min-w-18 flex-1 cursor-pointer items-center justify-center rounded-full border-[1.5px] px-2.5 text-[13px] font-bold whitespace-nowrap transition hover:brightness-96 active:scale-97" x-text="label"></button>
                                </template>
                            </div>
                        </div>
                        <p x-show="pick === 'start'" class="m-0 text-xs text-ink3">Changing the start keeps the same session length.</p>
                        <div class="flex justify-end"><button type="button" x-on:click="done()" class="h-10 cursor-pointer rounded-full bg-ink px-4.5 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">Done</button></div>
                    </div>

                    <div role="status" aria-live="polite" :class="problem ? 'text-hot' : 'text-ink2'" class="flex items-center gap-2 text-[13px] font-semibold">
                        <x-icon name="check" :size="16" :stroke="2.4" x-show="!problem" />
                        <x-icon name="alert" :size="16" x-show="problem" x-cloak />
                        <span x-text="problem || summary"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <x-stepper label="Courts" model="courts" :value="$courts" :min="1" :max="12" />
                    <x-stepper label="Player cap" model="cap" :value="$cap" :min="4" :max="64" :step="2" />
                </div>

                <x-segmented label="Race to" model="raceTo" :options="[11 => '11', 15 => '15', 21 => '21']" />
                <x-segmented label="Rotation" model="rotation" :options="['rotate' => 'Everyone rotates', 'winners' => 'Winners stay (2 max)']" />
                <x-segmented label="Who can join" model="visibility" :options="['public' => 'Any member', 'invite' => 'Invite only']" />

                <div class="flex flex-col gap-2">
                    <label for="c-paid" class="text-[13px] font-bold text-ink2">Paid players <span class="font-medium text-ink3">(one per line)</span></label>
                    <textarea id="c-paid" rows="6" aria-describedby="c-paid-hint" wire:model.live.debounce.400ms="paid" placeholder="Rico Dizon&#10;mika@picklex.app&#10;Juan Dela Cruz" class="input h-auto resize-y py-3.5 font-mono text-sm leading-[1.6]"></textarea>
                    <span id="c-paid-hint" class="text-xs text-ink3">Paste names or emails. Anyone with a PickleX account is linked automatically and joins the open play; everyone else is added as a guest.</span>
                    @if ($paidPreview)
                        @php($linkedN = collect($paidPreview)->whereNotNull('user')->count())
                        @php($guestN = count($paidPreview) - $linkedN)
                        <div class="flex animate-fade flex-col overflow-hidden rounded-2xl border border-line bg-surface">
                            <div class="border-b border-line px-3.5 py-2.5 text-[13px] font-bold">{{ count($paidPreview) }} paid · {{ $linkedN }} linked to accounts · {{ $guestN }} {{ Str::plural('guest', $guestN) }}</div>
                            @foreach ($paidPreview as $row)
                                <div wire:key="paid-{{ $loop->index }}" class="flex items-center gap-2.5 border-b border-line px-3.5 py-2 last:border-b-0">
                                    @if ($row['user'])
                                        <x-avatar :name="$row['name']" :color="$row['user']->color()" :src="$row['user']->avatarUrl()" class="size-7.5 text-[11px]" />
                                    @else
                                        <span class="flex size-7.5 shrink-0 items-center justify-center rounded-full bg-surface2 text-[11px] font-extrabold text-ink2">{{ initials($row['name']) }}</span>
                                    @endif
                                    <span class="flex min-w-0 flex-1 flex-col">
                                        <span class="truncate text-sm font-bold">{{ $row['name'] }}</span>
                                        @if ($row['user'] && mb_strtolower($row['line']) !== mb_strtolower($row['name']))
                                            <span class="truncate font-mono text-[11px] text-ink3">from “{{ $row['line'] }}”</span>
                                        @endif
                                    </span>
                                    <span class="inline-flex h-6 shrink-0 items-center rounded-full px-2.25 text-[11px] font-bold {{ \App\Livewire\Organizer\OpenPlays::PAID_TAGS[$row['how']][1] }}">{{ \App\Livewire\Organizer\OpenPlays::PAID_TAGS[$row['how']][0] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    <label for="c-fee" class="text-[13px] font-bold text-ink2">Fee per player <span class="font-medium text-ink3">(optional)</span></label>
                    <input id="c-fee" type="text" inputmode="decimal" wire:model="fee" placeholder="e.g. 150, or leave blank for free" class="input">
                </div>

                {{-- Payment QR (resources/js/pay-qr.js; the image is sent on publish) --}}
                <section aria-labelledby="pay-h" class="flex flex-col gap-3 rounded-[20px] border border-line bg-surface p-4">
                    <div class="flex items-start gap-3">
                        <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lime text-on-lime"><x-icon name="qr" /></span>
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span id="pay-h" class="text-[15px] font-extrabold">Payment QR <span class="font-medium text-ink3">(optional)</span></span>
                            <span class="text-[13px] leading-[1.45] text-ink3">Players scan it from the open play to pay the fee before they arrive.</span>
                        </div>
                    </div>
                    <div role="radiogroup" aria-label="Payment method" class="flex flex-wrap gap-1 rounded-2xl border-[1.5px] border-line bg-surface2 p-1">
                        @foreach (\App\Models\OpenPlay::PAY_METHODS as $key => [$label])
                            <label class="flex min-h-9.5 flex-auto cursor-pointer items-center justify-center rounded-xl px-3 text-[13px] font-bold whitespace-nowrap text-ink2 transition-colors has-checked:bg-ink has-checked:text-bg has-focus-visible:outline-2 has-focus-visible:outline-focus">
                                <input type="radio" value="{{ $key }}" wire:model="payMethod" class="sr-only">{{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <div wire:ignore>
                        <label x-show="!qr" x-on:dragenter.prevent="over = true" x-on:dragover.prevent="over = true" x-on:dragleave="$el.contains($event.relatedTarget) || (over = false)" x-on:drop.prevent="dropped($event)"
                               :class="over ? 'border-ink bg-lime-soft' : 'border-line2 bg-surface'"
                               class="relative flex min-h-37.5 cursor-pointer flex-col items-center justify-center gap-1.5 overflow-hidden rounded-[18px] border-2 border-dashed p-5 text-center transition-colors has-focus-visible:outline-2 has-focus-visible:outline-focus">
                            <span aria-hidden="true" class="mb-1 flex size-11.5 items-center justify-center rounded-[14px] border border-line bg-surface2 text-ink"><x-icon name="upload" /></span>
                            <span role="status" aria-live="polite" class="text-[15px] font-extrabold">
                                <span x-show="busy">Opening your QR…</span>
                                <span x-show="!busy && over">Drop it here</span>
                                <span x-show="!busy && !over"><span class="lg:hidden">Upload your payment QR</span><span class="hidden lg:inline">Drag your payment QR here</span></span>
                            </span>
                            <span class="max-w-85 text-xs leading-[1.45] text-ink3"><span class="hidden lg:inline">or click to browse. </span>A screenshot from your payment app works. PNG, JPG or WebP up to 5 MB.</span>
                            <input type="file" accept="image/png,image/jpeg,image/webp" x-on:change="pick($event)" aria-label="Upload your payment QR" class="absolute inset-0 cursor-pointer opacity-0">
                        </label>

                        <div x-show="qr" x-cloak class="flex animate-fade flex-wrap items-center gap-3.5">
                            <div class="shrink-0 rounded-2xl border border-[#DEDCD0] bg-white p-2"><img :src="src" alt="Your payment QR" class="block size-30 object-contain"></div>
                            <div class="flex min-w-45 flex-1 flex-col gap-2">
                                <span x-show="qr === 'saved'" class="inline-flex h-6.5 items-center gap-1.5 self-start rounded-full border border-line bg-surface2 px-2.5 text-xs font-bold text-ink2"><x-icon name="history" :size="14" />Saved from your last open play</span>
                                <span x-show="check === 'ok'" class="inline-flex h-6.5 animate-snap items-center gap-1.5 self-start rounded-full bg-lime-soft px-2.5 text-xs font-extrabold text-lime-text"><x-icon name="check" :size="14" :stroke="2.4" />QR code found</span>
                                <span x-show="check === 'none'" role="alert" class="text-[13px] leading-[1.45] font-semibold text-hot">We couldn't spot a QR code in this image. Check that it's the QR from your payment app.</span>
                                <span class="truncate font-mono text-xs text-ink3" x-text="name"></span>
                                <div class="flex flex-wrap gap-2">
                                    <label class="relative inline-flex h-9.5 cursor-pointer items-center overflow-hidden rounded-full border-[1.5px] border-line2 px-3.5 text-[13px] font-bold text-ink has-focus-visible:outline-2 has-focus-visible:outline-focus transition hover:brightness-96 active:scale-97">
                                        Replace<input type="file" accept="image/png,image/jpeg,image/webp" x-on:change="pick($event)" aria-label="Replace the payment QR" class="absolute inset-0 cursor-pointer opacity-0">
                                    </label>
                                    <button type="button" x-on:click="remove()" class="h-9.5 cursor-pointer rounded-full px-3 text-[13px] font-bold text-hot transition hover:brightness-96 active:scale-97">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="qr" x-cloak class="flex flex-col gap-2.5">
                        <div class="grid gap-2.5 lg:grid-cols-2">
                            <div class="flex flex-col gap-1.5"><label for="pay-name" class="text-[13px] font-bold text-ink2">Account name</label><input id="pay-name" type="text" autocomplete="off" placeholder="As shown on the QR" wire:model="payName" class="input h-11.5 bg-bg"></div>
                            <div class="flex flex-col gap-1.5"><label for="pay-num" class="text-[13px] font-bold text-ink2">Number <span class="font-medium text-ink3">(optional)</span></label><input id="pay-num" type="text" autocomplete="off" inputmode="tel" placeholder="Mobile or account number" wire:model="payNumber" class="input h-11.5 bg-bg"></div>
                        </div>
                        <span x-show="!($wire.fee || '').trim()" class="text-[13px] font-semibold text-hot">Add a fee above so players know how much to send.</span>
                        <span x-show="($wire.fee || '').trim()" class="text-[13px] text-ink3" x-text="'Players will see “Pay ' + (/^\d+(\.\d{1,2})?$/.test($wire.fee.trim()) ? '₱' + $wire.fee.trim() : $wire.fee.trim()) + '” on this open play.'"></span>
                    </div>

                    <div x-show="error" x-cloak role="alert" class="flex items-start gap-2.5 rounded-[14px] border border-hot-line bg-hot-soft px-3.5 py-3 text-sm leading-[1.45] font-semibold text-hot"><x-icon name="alert" :size="18" class="mt-px" /><span x-text="error"></span></div>
                </section>

                @if ($error)
                    <x-alert>{{ $error }}</x-alert>
                @endif
            </div>

            <div class="flex shrink-0 gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
                <button type="button" x-on:click="$wire.$set('create', false)" class="h-13 cursor-pointer rounded-full border-[1.5px] border-line2 bg-transparent px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Cancel</button>
                <button type="submit" class="h-13 flex-1 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Publish open play</button>
            </div>
        </form>
    </div>
</x-app-shell>
