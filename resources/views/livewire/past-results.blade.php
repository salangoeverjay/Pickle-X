{{-- Results sheet for a concluded open play: bottom sheet on mobile, centered modal on desktop. --}}
<div>
    @if ($play)
        @php($closest = $results->closest())
        <div wire:key="results-{{ $play->id }}" x-data x-on:keydown.escape.window="$wire.close()" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <button type="button" wire:click="close" aria-label="Close results" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

            <div role="dialog" aria-modal="true" aria-labelledby="results-title" x-trap.noscroll="true"
                 class="relative flex max-h-[92%] w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:max-h-full md:w-215 md:max-w-full md:rounded-[28px]">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="truncate font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $play->club }} · Concluded</span>
                        <h2 id="results-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $play->title }}</h2>
                        <span class="truncate text-[13px] text-ink3">{{ $play->starts_at->format('D, M j') }} · {{ $play->timeRange() }} · {{ $play->venue }}</span>
                    </div>
                    <button type="button" wire:click="close" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-5.5 overflow-y-auto px-6 pt-5.5 pb-7">
                    <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-4">
                        @foreach ([['Players', $results->players()], ['Games', $results->log->count()], ['Courts', $play->courts], ['Closest game', $closest ? $results::score($closest) : 'None']] as [$label, $value])
                            <div class="flex flex-col gap-1 rounded-[18px] border border-line bg-surface px-4 py-3.5">
                                <span class="font-mono text-[10px] tracking-widest text-ink3 uppercase">{{ $label }}</span>
                                <span class="font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($mine)
                        @php($top = $mine['rank'] <= 3)
                        <div class="flex flex-col gap-3.5 rounded-[22px] bg-ink p-4.5 text-bg">
                            <div class="flex flex-wrap items-center gap-3.5">
                                @if ($top)
                                    <x-medal :rank="$mine['rank']" :size="50" :display="true" />
                                @else
                                    <span class="flex size-13 shrink-0 items-center justify-center rounded-full bg-white font-display text-[22px] font-extrabold text-on-lime">{{ $mine['rank'] }}</span>
                                @endif
                                <div class="flex min-w-45 flex-1 flex-col gap-0.5">
                                    <span class="font-mono text-[11px] tracking-widest uppercase opacity-75">Your night</span>
                                    <span class="font-display text-[22px] font-extrabold tracking-[-0.02em]">{{ $top ? $results::MEDALS[$mine['rank'] - 1][1].' · ' : '' }}#{{ $mine['rank'] }} of {{ $mine['of'] }}</span>
                                    <span class="text-sm opacity-80">{{ $mine['w'] }}-{{ $mine['l'] }} · {{ $results::signed($mine['diff']) }} points · {{ round($mine['w'] * 100 / $mine['g']) }}% win rate</span>
                                </div>
                            </div>
                            <div class="flex flex-col overflow-hidden rounded-2xl bg-white/6">
                                @foreach ($mine['games'] as $g)
                                    <div class="flex items-center gap-3 border-t border-white/8 px-3 py-2.5 first:border-t-0">
                                        <span @class(['flex size-7 shrink-0 items-center justify-center rounded-lg border-[1.5px] text-xs font-extrabold', 'border-lime bg-lime text-on-lime' => $g['won'], 'border-current' => ! $g['won']])>{{ $g['won'] ? 'W' : 'L' }}</span>
                                        <span class="min-w-0 flex-1 truncate text-sm"><span class="opacity-70">with</span> <strong>{{ $g['with'] }}</strong> <span class="opacity-70">vs</span> {{ $g['vs'] }}</span>
                                        <span class="shrink-0 font-mono text-sm font-semibold">{{ $g['score'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($highlights = $results->highlights())
                        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-3">
                            @foreach ($highlights as [$label, $value, $icon])
                                <div class="flex min-w-0 items-center gap-3 rounded-[18px] border border-line bg-surface px-3.5 py-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-lime-soft text-lime-text"><x-icon :name="$icon" :size="18" /></span>
                                    <div class="flex min-w-0 flex-col">
                                        <span class="text-xs font-bold text-ink3">{{ $label }}</span>
                                        <span class="truncate text-sm font-extrabold">{{ $value }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <x-standings :play="$play" :rows="$results->rows" :page="$resultsPage" :me="$pid" heading="results-standings" title="Final standings" :plain="true" />
                </div>
            </div>
        </div>
    @endif
</div>
