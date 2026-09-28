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
                    <div class="flex flex-wrap gap-2">
                        @foreach ([[$play->busyCourtCount() * 4, 'on court'], [$play->queuedPlayerCount(), 'in queue'], [$play->games_count, 'games logged']] as [$n, $label])
                            <span class="inline-flex h-8.5 items-center gap-1.5 rounded-full bg-white/14 px-3 text-[13px] font-bold"><span class="font-mono">{{ $n }}</span>{{ $label }}</span>
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
                <div wire:key="up-{{ $play->id }}" class="flex flex-wrap items-center gap-4 rounded-[22px] border border-line bg-surface p-3.5 transition duration-300 hover:-translate-y-1 hover:shadow-card lg:py-3.5 lg:pr-4.5 lg:pl-3.5">
                    <div class="flex h-18.5 w-16.5 shrink-0 flex-col items-center justify-center gap-0.5 rounded-[18px] border border-line bg-surface2">
                        <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $today ? 'Today' : $play->starts_at->format('M') }}</span>
                        @unless ($today)
                            <span class="font-display text-[28px] leading-none font-extrabold tracking-[-0.04em]">{{ $play->starts_at->format('j') }}</span>
                            <span class="text-[11px] text-ink3">{{ $play->starts_at->format('D') }}</span>
                        @endunless
                    </div>
                    <div class="flex min-w-50 flex-1 flex-col gap-1.5">
                        <span class="font-display text-xl font-bold tracking-[-0.02em]">{{ $play->title }}</span>
                        <span class="text-sm text-ink2">{{ $play->timeRange() }} · {{ $play->venue }} · {{ $play->courts }} courts</span>
                        <div class="flex max-w-90 items-center gap-2.5">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-[3px] bg-surface2">
                                <div class="h-full rounded-[3px] bg-ink" style="width: {{ min(100, round($play->playerCount() / max(1, $play->cap) * 100)) }}%"></div>
                            </div>
                            <span class="font-mono text-xs text-ink3">{{ $play->playerCount() }}/{{ $play->cap }} joined</span>
                        </div>
                    </div>
                    <button type="button" wire:click="startSession({{ $play->id }})" class="inline-flex h-11.5 grow cursor-pointer items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 bg-transparent px-4.5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97 lg:grow-0">
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

        <form wire:submit="publish" role="dialog" aria-modal="true" aria-labelledby="create-title"
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

                <div class="grid grid-cols-6 gap-2.5">
                    <div class="col-span-6 lg:col-span-2"><x-field label="Date" id="c-date" type="date" wire:model="date" class="px-3 dark:scheme-dark" /></div>
                    <div class="col-span-3 lg:col-span-2"><x-field label="Starts" id="c-start" type="time" wire:model="starts" class="px-3 dark:scheme-dark" /></div>
                    <div class="col-span-3 lg:col-span-2"><x-field label="Ends" id="c-end" type="time" wire:model="ends" class="px-3 dark:scheme-dark" /></div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <x-stepper label="Courts" model="courts" :value="$courts" :min="1" :max="12" />
                    <x-stepper label="Player cap" model="cap" :value="$cap" :min="4" :max="64" :step="2" />
                </div>

                <x-segmented label="Race to" model="raceTo" :options="[11 => '11', 15 => '15', 21 => '21']" />
                <x-segmented label="Rotation" model="rotation" :options="['rotate' => 'Everyone rotates', 'winners' => 'Winners stay (2 max)']" />
                <x-segmented label="Who can join" model="visibility" :options="['public' => 'Any member', 'invite' => 'Invite only']" />

                <div class="flex flex-col gap-2">
                    <label for="c-fee" class="text-[13px] font-bold text-ink2">Fee per player <span class="font-medium text-ink3">(optional)</span></label>
                    <input id="c-fee" type="text" wire:model="fee" placeholder="e.g. ₱150, or leave blank for free" class="input">
                </div>

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
