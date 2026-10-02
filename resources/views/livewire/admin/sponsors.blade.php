@php
    // Tier chip colors: [background, text, border].
    $tc = ['gold' => ['#FFF4C9', '#8A5A00', '#E8B425'], 'silver' => ['#EEF1F5', '#4E5866', '#A7B0BC'], 'bronze' => ['#FBE3CF', '#7A3B14', '#C97A42']];
    $blurb = [
        'gold' => 'Winged heart crest, a full laurel wreath with gems, and gold coins falling through their queue spot.',
        'silver' => 'A star crest and laurel wreath, with stars twinkling in their queue spot.',
        'bronze' => 'A heart rosette and laurel branch, with warm embers rising in their queue spot.',
    ];
    $chip = fn ($t) => "background: {$tc[$t][0]}; color: {$tc[$t][1]}; border-color: {$tc[$t][2]}";
    $seg = 'h-8 cursor-pointer rounded-full px-2.75 text-xs font-bold whitespace-nowrap transition-colors duration-200';
@endphp

<x-app-shell>
    <x-page>
        <x-page-header eyebrow="Admin" title="Sponsors" description="Acknowledge the people who cover some of PickleX’s costs. Each tier unlocks its own animated avatar decoration and queue background. Only admins can change this." />

        <div class="grid gap-3.5 lg:grid-cols-3">
            @foreach (App\Support\Looks::TIERS as $t => $label)
                @php($n = $counts[$t] ?? 0)
                <section aria-label="{{ $label }} sponsor" class="flex flex-col gap-4 rounded-3xl border-[1.5px] bg-surface px-5 pt-6.5 pb-5" style="border-color: {{ $tc[$t][2] }}">
                    <div class="flex min-w-0 items-center gap-5.5">
                        <span aria-hidden="true" class="px-fr px-fr-sp-{{ $t }} mx-2 mt-3 mb-1 flex size-18 shrink-0 items-center justify-center rounded-full text-on-lime" style="background: {{ $tc[$t][0] }}"><x-icon name="heart" :size="28" /></span>
                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                            <span class="inline-flex h-5.5 items-center self-start rounded-full border px-2.25 font-mono text-xs font-semibold tracking-[.06em] uppercase" style="{{ $chip($t) }}">{{ $n }} {{ Str::plural('sponsor', $n) }}</span>
                            <h2 class="m-0 font-display text-[21px] font-extrabold tracking-[-0.02em]">{{ $label }} sponsor</h2>
                        </div>
                    </div>
                    <p class="m-0 text-[13.5px] leading-[1.45] text-ink2">{{ $blurb[$t] }}</p>
                    <div role="img" aria-label="Queue background preview" class="px-qb px-qb-sp-{{ $t }} mt-auto flex min-h-15 items-center gap-2.5 rounded-2xl border-[1.5px] border-line bg-surface py-1.5 pr-3.5 pl-3">
                        <span class="w-4 shrink-0 font-mono text-xs text-ink3">3</span>
                        <span class="px-fr px-fr-sp-{{ $t }} mx-1.5 flex size-8.5 shrink-0 items-center justify-center rounded-full text-on-lime" style="background: {{ $tc[$t][0] }}"><x-icon name="heart" :size="15" /></span>
                        <span class="flex min-w-0 flex-1 flex-col gap-px"><span class="truncate text-sm font-bold">Sponsor</span><span class="text-xs text-ink3">In the queue</span></span>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <section aria-labelledby="sp-list-h" class="flex flex-col gap-3.5 rounded-3xl border border-line bg-surface p-4.5 lg:p-6">
                <div class="flex items-baseline justify-between gap-2.5">
                    <h2 id="sp-list-h" class="m-0 font-display text-[21px] font-bold tracking-[-0.02em]">Sponsors</h2>
                    <span class="shrink-0 font-mono text-xs tracking-[.08em] text-ink3 uppercase">{{ $sponsors->count() }} {{ Str::plural('sponsor', $sponsors->count()) }}</span>
                </div>
                @if ($sponsors->isEmpty())
                    <p class="m-0 rounded-[18px] border-[1.5px] border-dashed border-line2 p-4.5 text-sm text-ink2">No sponsors yet. Search for an account to acknowledge them.</p>
                @else
                    <div role="list" class="flex flex-col gap-2.5">
                        @foreach ($sponsorsPage as $s)
                            @php($t = $s->sponsor_tier)
                            @php($tl = App\Support\Looks::TIERS[$t])
                            @php($wf = str_starts_with((string) $s->frame, 'sp-'))
                            @php($wq = str_starts_with((string) $s->queue_bg, 'sp-'))
                            <div role="listitem" wire:key="sp-{{ $s->id }}" class="flex animate-fade flex-wrap items-center gap-x-3 gap-y-2.5 rounded-[18px] border border-line bg-surface2 pt-4 pr-3 pb-3 pl-3.5">
                                <x-avatar :src="$s->avatarUrl()" :frame="$s->frameClass()" :name="$s->name" :color="$s->color()" class="mx-1.5 mt-1 size-11 text-sm" />
                                <div class="flex min-w-37.5 flex-1 flex-col gap-0.5">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="min-w-0 truncate text-base font-bold">{{ $s->name }}</span>
                                        <span class="inline-flex h-5 shrink-0 items-center gap-1 rounded-full border px-1.75 text-[10.5px] font-extrabold tracking-[.05em]" style="{{ $chip($t) }}"><x-icon name="heart" :size="11" :stroke="2.4" /><span>{{ $tl }}</span></span>
                                    </span>
                                    <span class="truncate text-[13px] text-ink3">{{ $s->email }}</span>
                                    <span class="text-[12.5px] text-ink2">{{ $wf && $wq ? "Wearing the {$tl} border and queue background" : ($wf || $wq ? "Wearing part of the {$tl} look" : 'Unlocked · not worn yet') }}</span>
                                </div>
                                <div class="ml-14 flex w-[calc(100%-56px)] flex-wrap items-center gap-2 lg:ml-0 lg:w-auto">
                                    <div role="radiogroup" aria-label="Tier" class="inline-flex rounded-full border border-line bg-surface2 p-0.75">
                                        @foreach (App\Support\Looks::TIERS as $x => $xl)
                                            <button type="button" role="radio" aria-checked="{{ $x === $t ? 'true' : 'false' }}" aria-label="Make {{ $s->name }} a {{ $xl }} sponsor" wire:click="setTier({{ $s->id }}, '{{ $x }}')"
                                                    @class([$seg, 'bg-ink text-bg' => $x === $t, 'text-ink2' => $x !== $t])>{{ $xl }}</button>
                                        @endforeach
                                    </div>
                                    <button type="button" wire:click="removeSponsor({{ $s->id }})" aria-label="Remove {{ $s->name }} as a sponsor"
                                            class="h-9.5 cursor-pointer rounded-full border-[1.5px] border-line2 bg-surface px-3.25 text-[13px] font-bold text-hot transition hover:brightness-96 active:scale-97">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <x-pager :page="$sponsorsPage" noun="sponsors" class="pt-0.5" />
                @endif
                <p class="m-0 text-[13px] leading-[1.45] text-ink3">Changing a tier swaps their look to the new tier. Removing a sponsor locks the look again.</p>
            </section>

            <section aria-labelledby="sp-add-h" class="flex flex-col gap-3.5 rounded-3xl border border-line bg-surface p-4.5 lg:p-6">
                <div>
                    <h2 id="sp-add-h" class="m-0 font-display text-[21px] font-bold tracking-[-0.02em]">Acknowledge a sponsor</h2>
                    <p class="mt-1 mb-0 text-sm leading-[1.45] text-ink2">Pick their tier, then find their account. Check the email so you pick the right person.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="text-[13px] font-bold text-ink2">Tier</span>
                    <div role="radiogroup" aria-label="Tier" class="inline-flex rounded-full border border-line bg-surface2 p-0.75">
                        @foreach (App\Support\Looks::TIERS as $x => $xl)
                            <button type="button" role="radio" aria-checked="{{ $x === $addTier ? 'true' : 'false' }}" wire:click="$set('tier', '{{ $x }}')"
                                    @class([$seg, 'bg-ink text-bg' => $x === $addTier, 'text-ink2' => $x !== $addTier])>{{ $xl }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="relative">
                    <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-ink3" />
                    <input type="search" wire:model.live.debounce.250ms="q" placeholder="Name or email" aria-label="Search accounts" autocomplete="off"
                           class="h-12 w-full rounded-[14px] border-[1.5px] border-line2 bg-surface px-10.5 text-[15px] text-ink outline-none focus:border-ink [&::-webkit-search-cancel-button]:hidden">
                    @if (trim($q) !== '')
                        <button type="button" wire:click="$set('q', '')" aria-label="Clear search" class="absolute top-1/2 right-1.5 flex size-8.5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-ink2"><x-icon name="x" :size="16" /></button>
                    @endif
                </div>
                @if (trim($q) === '')
                    <p class="m-0 text-sm text-ink3">Type a name or email to find an account.</p>
                @elseif ($results->isEmpty())
                    <p role="status" class="m-0 rounded-2xl bg-surface2 px-4 py-3.5 text-sm text-ink2">No account matches “{{ trim($q) }}”, or they are already a sponsor.</p>
                @else
                    @php($al = App\Support\Looks::TIERS[$addTier])
                    <div role="list" aria-label="Matching accounts" class="flex flex-col gap-2">
                        @foreach ($results as $r)
                            @php($g = $games($r))
                            <div role="listitem" wire:key="spr-{{ $r->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2.5 rounded-[18px] border-[1.5px] border-line px-3 py-2.5 lg:flex-nowrap">
                                <x-avatar :src="$r->avatarUrl()" :frame="$r->frameClass()" :name="$r->name" :color="$r->color()" class="size-11 text-sm" />
                                <div class="flex min-w-0 flex-1 flex-col gap-px">
                                    <span class="truncate text-[15px] font-bold">{{ $r->name }}</span>
                                    <span class="truncate text-[13px] text-ink2">{{ $r->email }}</span>
                                    <span class="truncate text-xs text-ink3">{{ $r->club ? $r->club.' · ' : '' }}{{ $g }} {{ Str::plural('game', $g) }}</span>
                                </div>
                                <button type="button" wire:click="addSponsor({{ $r->id }})" aria-label="Add {{ $r->name }} as a {{ $al }} sponsor"
                                        class="ml-14 inline-flex h-10.5 w-[calc(100%-56px)] shrink-0 cursor-pointer items-center justify-center gap-1.75 rounded-full bg-lime px-4 text-sm font-extrabold whitespace-nowrap text-on-lime transition hover:brightness-96 active:scale-97 lg:ml-0 lg:w-auto">
                                    <x-icon name="heart" :size="16" :stroke="2.2" /><span>Add as {{ $al }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </x-page>
</x-app-shell>
