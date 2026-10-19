{{-- Co-hosts for an upcoming open play (the host only): who helps run it, and who to add. --}}
@php($play = $cohosts['play'])
@php($now = $cohosts['now'])
@php($row = function ($u) use ($cohosts) {
    return ($u->isOrganizer() ? 'Organizer' : 'Player').(in_array($u->id, $cohosts['joined']) ? ', joined this' : '').($u->club ? ', '.$u->club : '').', '.$u->email;
})
<div wire:key="cohosts-{{ $play->id }}" x-data x-on:keydown.escape.window="$wire.closeCohosts()" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
    <button type="button" wire:click="closeCohosts" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
    <div role="dialog" aria-modal="true" aria-labelledby="coh-title" x-trap.noscroll="true" class="relative flex max-h-[92%] w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:max-h-full md:w-190 md:max-w-full md:rounded-[28px]">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
            <div class="flex min-w-0 flex-col gap-0.5">
                <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Co-hosts</span>
                <h2 id="coh-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $play->title }}</h2>
                <span class="truncate text-[13px] text-ink3">{{ $play->whenLabel() }}, {{ $play->timeRange() }}, {{ $play->venue }}</span>
            </div>
            <button type="button" wire:click="closeCohosts" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97"><x-icon name="x" :size="18" /></button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto px-6 py-5">
            <p class="m-0 text-[15px] leading-normal text-ink2">Add up to {{ App\Models\OpenPlay::MAX_COHOSTS }} people who help you run this open play: regulars or other organizers. On the day, any co-host can start it and run the court board, even before you get there.</p>

            <section aria-labelledby="coh-now" class="flex flex-col gap-2.5">
                <div class="flex items-baseline justify-between gap-2.5"><h3 id="coh-now" class="m-0 text-[15px] font-extrabold">Co-hosts</h3><span class="font-mono text-xs text-ink3">{{ $now->count() }} of {{ App\Models\OpenPlay::MAX_COHOSTS }}</span></div>
                @if ($now->isNotEmpty())
                    <ul class="m-0 flex list-none flex-col overflow-hidden rounded-[18px] border border-line bg-surface p-0">
                        @foreach ($now as $u)
                            <li wire:key="co-now-{{ $u->id }}" class="flex items-center gap-3 border-t border-line px-3.5 py-3 first:border-t-0">
                                <x-avatar :src="$u->avatarUrl()" :frame="$u->frameClass()" :name="$u->name" :color="$u->color()" class="size-10 text-[13px]" />
                                <span class="flex min-w-0 flex-1 flex-col gap-0.5"><strong class="truncate text-[15px]">{{ $u->name }}</strong><span class="truncate text-xs text-ink3">{{ $row($u) }}</span></span>
                                <button type="button" wire:click="removeCohost({{ $u->id }})" aria-label="Remove {{ $u->name }} as a co-host" class="h-9 shrink-0 cursor-pointer rounded-full border-[1.5px] border-hot-line px-3 text-xs font-bold text-hot transition hover:brightness-96 active:scale-97">Remove</button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="m-0 rounded-[18px] border-[1.5px] border-dashed border-line2 px-4 py-3.5 text-sm text-ink2">No co-hosts yet. You're the only one who can start this open play.</p>
                @endif
            </section>

            <section aria-labelledby="coh-add" class="flex flex-col gap-2.5">
                <h3 id="coh-add" class="m-0 text-[15px] font-extrabold">Add a co-host</h3>
                @if ($now->count() < App\Models\OpenPlay::MAX_COHOSTS)
                    <div class="relative">
                        <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-ink3" />
                        <input type="text" wire:model.live.debounce.250ms="cohostQ" aria-label="Search PickleX accounts by name, email or club" placeholder="Search by name, email or club" autocomplete="off" autocorrect="off" spellcheck="false"
                               class="h-11.5 w-full rounded-[14px] border-[1.5px] border-line2 bg-surface px-10.5 text-[15px] text-ink outline-none focus:border-ink">
                    </div>
                    @if ($cohosts['cands']->isNotEmpty())
                        <ul aria-label="People you can add" class="m-0 flex list-none flex-col overflow-hidden rounded-[18px] border border-line bg-surface p-0">
                            @foreach ($cohosts['cands'] as $u)
                                <li wire:key="co-cand-{{ $u->id }}" class="flex items-center gap-3 border-t border-line px-3.5 py-3 first:border-t-0">
                                    <x-avatar :src="$u->avatarUrl()" :frame="$u->frameClass()" :name="$u->name" :color="$u->color()" class="size-10 text-[13px]" />
                                    <span class="flex min-w-0 flex-1 flex-col gap-0.5"><strong class="truncate text-[15px]">{{ $u->name }}</strong><span class="truncate text-xs text-ink3">{{ $row($u) }}</span></span>
                                    <button type="button" wire:click="addCohost({{ $u->id }})" aria-label="Add {{ $u->name }} as a co-host" class="inline-flex h-9 shrink-0 cursor-pointer items-center gap-1 rounded-full bg-ink px-3.5 text-xs font-bold text-bg transition hover:brightness-96 active:scale-97"><x-icon name="plus" :size="14" :stroke="2.6" />Add</button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="m-0 text-sm text-ink2">{{ trim($cohostQ) !== '' ? 'No PickleX account matches “'.trim($cohostQ).'”. Send them the share link so they can make one, then add them here.' : 'Search for anyone with a PickleX account.' }}</p>
                    @endif
                    <p class="m-0 text-xs leading-[1.45] text-ink3">Any PickleX account can co-host, member or organizer.</p>
                @else
                    <p class="m-0 text-sm text-ink2">You have {{ App\Models\OpenPlay::MAX_COHOSTS }} co-hosts, the most an open play can have. Remove one to add someone else.</p>
                @endif
            </section>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                @foreach ([
                    ['Co-hosts can', 'check', 'text-lime-text', ['Start the session on the day', 'Check players in, add walk-ins and sub players', 'Run courts, the queue and the game mode', 'Enter scores and end the session', 'See the player list and who has paid', 'Share the link, and play too if they joined']],
                    ['Only you', 'minus', 'text-ink3', ['Edit or delete the open play', 'Change who co-hosts', 'Mark who has paid: they can see it, but players still pay your '.($play->hasPaymentQr() ? $play->payMethodLabel().' QR' : 'payment details')]],
                ] as [$label, $icon, $tone, $items])
                    <div class="flex flex-col gap-2 rounded-[18px] border border-line bg-surface p-4">
                        <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $label }}</span>
                        <ul class="m-0 flex list-none flex-col gap-1.5 p-0">
                            @foreach ($items as $item)
                                <li class="flex items-start gap-2 text-sm leading-[1.4]"><x-icon :name="$icon" :size="16" :stroke="2.6" class="mt-px shrink-0 {{ $tone }}" /><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex shrink-0 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
            <button type="button" wire:click="closeCohosts" class="h-13 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">Done</button>
        </div>
    </div>
</div>
