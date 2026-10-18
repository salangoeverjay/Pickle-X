{{-- Your look: an avatar border and a queue background (App\Support\Looks). Each pick saves right away.
     One kind of look shows at a time and every tile is still; only the preview moves (with a Pause button). --}}
@use('App\Support\Looks')
@props(['user', 'tab' => 'border', 'col' => '', 'cats' => [], 'saved' => false])
@php
    $dev = (bool) $user->is_developer;
    $tier = $user->sponsor_tier;
    // What's worn: an exclusive look someone lost counts as none; a sponsor who moved tier shows the new tier.
    $cur = ['frame' => Looks::effective('frame', $user->frame, $dev, $tier) ?? '', 'queue' => Looks::effective('queue', $user->queue_bg, $dev, $tier) ?? ''];
    $tab = $tab === 'queue' ? 'queue' : 'border';
    $kind = $tab === 'border' ? 'frame' : 'queue';
    $list = Looks::list($kind);
    // Collections: one tab each; opens on the collection of your current pick.
    $colId = isset(Looks::COLLECTIONS[$col]) ? $col : (Looks::collectionOf($cur[$kind]) ?? 'nj');
    $colDef = Looks::COLLECTIONS[$colId];
    $colTotal = collect(Looks::COLLECTIONS)->sum(fn ($c) => count($c[4]));
    $std = [
        ['Animated', '10', 'anim', array_filter($list, fn ($l) => $l[1])],
        ['Collections', $colTotal.' animated looks', 'col', array_map(fn ($l) => [$l, true], $colDef[4])],
        ['Still', '10 + none', 'still', ['' => ['None', false]] + array_filter($list, fn ($l) => ! $l[1])],
    ];
    // Exclusive: unlocked ones first.
    $exItems = collect(Looks::EXCLUSIVE)->map(fn ($l) => [$l[0], true, $l[1], ! Looks::unlocked($l[1], $dev, $tier)])->sortBy(fn ($l) => $l[3])->all();
    $open = collect($exItems)->reject(fn ($l) => $l[3])->count();
    $tierName = Looks::TIERS[$tier] ?? null;
    $exNote = match (true) {
        $dev && (bool) $tierName => "Unlocked: developer and {$tierName} sponsor.",
        $dev => 'Unlocked: developer.',
        (bool) $tierName => "Thanks for sponsoring. Your {$tierName} look is unlocked.",
        default => 'For PickleX developers and sponsors. An admin unlocks them.',
    };
    $ex = ['Exclusive', $open ? $open.' of '.count($exItems).' unlocked' : 'Locked', 'ex', $exItems];
    $groups = collect([...$std, $ex])->keyBy(2);
    // The kind you're wearing gets a dot; the picker opens on it.
    $wearing = match (true) {
        $cur[$kind] === '' => 'still',
        isset(Looks::EXCLUSIVE[$cur[$kind]]) => 'ex',
        (bool) Looks::collectionOf($cur[$kind]) => 'col',
        (bool) ($list[$cur[$kind]][1] ?? false) => 'anim',
        default => 'still',
    };
    $cat = isset($groups[$cats[$tab] ?? '']) ? $cats[$tab] : $wearing;
    $catTabs = [
        'anim' => ['Animated', count($std[0][3]), 'M12 3l1.8 4.6L18.5 9l-4.7 1.6L12 15.2l-1.8-4.6L5.5 9l4.7-1.4ZM18.5 15l.8 2 2 .7-2 .8-.8 2-.8-2-2-.8 2-.7Z'],
        'col' => ['Collections', $colTotal, 'M12 3.5 21 8l-9 4.5L3 8l9-4.5ZM3 12l9 4.5 9-4.5M3 16l9 4.5 9-4.5'],
        'still' => ['Still', count($std[2][3]), 'M12 20.5a8.5 8.5 0 1 0 0-17 8.5 8.5 0 0 0 0 17ZM12 16.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z'],
        'ex' => ['Exclusive', $open.'/'.count($exItems), 'M7 4h10l4 5-9 11L3 9l4-5ZM3 9h18M12 20 8.5 9 12 4l3.5 5L12 20'],
    ];
    $frName = $cur['frame'] ? Looks::label('frame', $cur['frame']).(isset(Looks::EXCLUSIVE[$cur['frame']]) ? ' · exclusive' : (($c = Looks::collectionOf($cur['frame'])) ? ' · '.Looks::COLLECTIONS[$c][0] : (Looks::FRAMES[$cur['frame']][1] ? ' · animated' : ''))) : 'No border';
    $pvFr = $user->frameClass();
@endphp

<div id="set-style" class="flex flex-col gap-4.5 rounded-[22px] border border-line bg-bg p-3.5 lg:p-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex min-w-0 flex-col gap-0.75">
            <h3 class="m-0 font-display text-lg font-bold tracking-[-0.02em]">Your look</h3>
            <span class="text-[13px] leading-[1.4] text-ink3">An avatar border, plus a background for your queue spot.</span>
        </div>
        <span role="status" aria-live="polite" class="font-mono text-xs tracking-[.08em] text-ink3 uppercase">{{ $saved ? 'Saved' : 'Changes save right away' }}</span>
    </div>

    {{-- data-still pauses the preview: resources/js/looks.js keeps looks inside it still. --}}
    <div x-data="{ paused: false }" :data-still="paused" class="flex flex-col gap-4 rounded-[18px] border border-line bg-surface p-4 md:flex-row md:items-center">
        <div class="flex shrink-0 items-center gap-3.5">
            <x-avatar :src="$user->avatarUrl()" :frame="$pvFr" :name="$user->shownName()" :color="$user->color()" class="size-17 text-[22px]" aria-hidden="true" />
            <div class="flex flex-col gap-0.5">
                <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">Border</span>
                <span class="text-[15px] font-extrabold">{{ $frName }}</span>
            </div>
        </div>
        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
            <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">In the queue · {{ $cur['queue'] ? Looks::label('queue', $cur['queue']) : 'plain' }}</span>
            <div role="img" aria-label="Preview of your queue spot" class="flex min-h-14.5 items-center gap-2.5 rounded-2xl border-[1.5px] border-line bg-surface py-1 pr-3.5 pl-3 {{ $user->queueBgClass() }}">
                <span class="w-5 shrink-0 font-mono text-xs text-ink3">3</span>
                <x-avatar :src="$user->avatarUrl()" :frame="$pvFr" :name="$user->shownName()" :color="$user->color()" class="size-9 text-xs" />
                <span class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-sm font-bold">{{ $user->shownName() }}</span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-flex h-4.25 items-center rounded-full bg-lime px-1.5 text-[9.5px] font-extrabold tracking-[.04em] text-on-lime">NEXT</span>
                        <span class="text-xs text-ink3">2 games tonight</span>
                    </span>
                </span>
            </div>
        </div>
        <button type="button" x-on:click="paused = ! paused; $nextTick(() => window.pxLooks?.())" :aria-pressed="paused" :aria-label="paused ? 'Play the preview animation' : 'Pause the preview animation'"
                class="inline-flex h-9 shrink-0 cursor-pointer items-center gap-1.5 self-start rounded-full border-[1.5px] border-line2 bg-bg px-3 text-[13px] font-bold text-ink transition active:scale-97 md:self-center">
            <svg x-show="paused" x-cloak width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l12-7.5Z" /></svg>
            <svg x-show="! paused" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 4.5h4v15h-4zM13.5 4.5h4v15h-4z" /></svg>
            <span x-text="paused ? 'Play' : 'Pause'">Pause</span>
        </button>
    </div>

    <div role="tablist" aria-label="Customize" class="flex self-stretch rounded-2xl border-[1.5px] border-line bg-surface2 p-1 md:self-start">
        @foreach (['border' => 'Avatar border', 'queue' => 'Queue background'] as $t => $label)
            <button type="button" role="tab" id="look-tab-{{ $t }}" aria-selected="{{ $tab === $t ? 'true' : 'false' }}" aria-controls="look-panel" wire:click="$set('lookTab', '{{ $t }}')"
                    @class(['min-h-10 flex-1 cursor-pointer rounded-xl px-2.5 text-sm font-bold whitespace-nowrap transition-colors duration-200 lg:px-4', 'bg-surface text-ink shadow-[0_1px_2px_rgba(18,23,15,.12),0_4px_12px_-6px_rgba(18,23,15,.25)]' => $tab === $t, 'text-ink2' => $tab !== $t])>{{ $label }}</button>
        @endforeach
    </div>

    <div id="look-panel" role="tabpanel" aria-labelledby="look-tab-{{ $tab }}" class="flex flex-col gap-3.5">
        <div role="tablist" aria-label="Kind of look" class="grid grid-cols-2 gap-1.5 md:grid-cols-[repeat(4,max-content)]">
            @foreach ($catTabs as $c => [$clabel, $cn, $icon])
                @php($on = $c === $cat)
                <button type="button" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}" aria-label="{{ $clabel }}{{ $c === $wearing ? ', you are wearing one of these' : '' }}" wire:click="$set('lookCat.{{ $tab }}', '{{ $c }}')"
                        @class(['inline-flex h-10 min-w-0 cursor-pointer items-center justify-center gap-1.75 rounded-full border-[1.5px] pr-2.5 pl-3 text-sm font-bold whitespace-nowrap transition-colors duration-200 active:scale-97', 'border-ink bg-ink text-bg' => $on, 'border-line bg-surface text-ink' => ! $on])>
                    <svg class="shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}" /></svg>
                    <span>{{ $clabel }}</span>
                    <span aria-hidden="true" @class(['inline-flex h-5 min-w-5.5 items-center justify-center rounded-full px-1.5 font-mono text-xs font-bold', 'bg-lime text-on-lime' => $on, 'bg-surface2 text-ink2' => ! $on])>{{ $cn }}</span>
                    @if ($c === $wearing)
                        <span aria-hidden="true" class="size-1.75 rounded-full bg-lime shadow-[0_0_0_1.5px_var(--color-ink)]"></span>
                    @endif
                </button>
            @endforeach
        </div>
        @if ($cat !== 'still')
            <p class="-mt-1 mb-0 flex items-start gap-2 text-[13px] leading-[1.4] text-ink3"><x-icon name="sparkle" :size="15" class="mt-px shrink-0" /><span>Paused here. Pick one to see it move.</span></p>
        @endif
        @foreach ([$groups[$cat]] as [$glabel, $n, $gid, $items])
            @php($isEx = $gid === 'ex')
            <div class="flex flex-col gap-2.5">
                <h4 id="look-{{ $kind }}-{{ $gid }}" class="sr-only">{{ $glabel }}</h4>
                @if ($gid === 'col')
                    <div id="look-{{ $kind }}-cols" role="tablist" aria-label="Collections" class="flex scroll-mt-5 flex-wrap gap-1.5">
                        @foreach (Looks::COLLECTIONS as $ck => $c)
                            @php($has = isset($c[4][$cur[$kind]]))
                            <button type="button" role="tab" aria-selected="{{ $ck === $colId ? 'true' : 'false' }}" aria-label="{{ $c[0] }} collection{{ $has ? ', has your pick' : '' }}" wire:click="$set('lookCol', '{{ $ck }}')"
                                    @class(['inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-full border-[1.5px] px-3.5 text-[13px] font-bold whitespace-nowrap transition active:scale-97', 'border-ink bg-ink text-bg' => $ck === $colId, 'border-line bg-surface text-ink' => $ck !== $colId])>
                                <span>{{ $c[0] }}</span>
                                @if ($has)
                                    <span aria-hidden="true" class="size-1.75 rounded-full bg-lime shadow-[0_0_0_1.5px_var(--color-ink)]"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <p class="-mt-1 mb-0 text-[13px] leading-[1.4] text-ink3">{{ $colDef[0] }} · {{ $colDef[1] }}</p>
                @elseif ($isEx)
                    <p class="-mt-1 mb-0 text-[13px] leading-[1.4] text-ink3">{{ $exNote }}</p>
                @endif
                {{-- data-still: tiles always show each look's still version (resources/js/looks.js). --}}
                <div role="radiogroup" aria-labelledby="look-{{ $kind }}-{{ $gid }}" data-still @class(['grid gap-2.5', 'grid-cols-4 md:grid-cols-5 lg:grid-cols-6' => $kind === 'frame', 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4' => $kind === 'queue'])>
                    @foreach ($items as $id => $item)
                        @php([$label, $anim] = $item)
                        @php($locked = $isEx && $item[3])
                        @php($on = ! $locked && $cur[$kind] === (string) $id)
                        @php($cls = trim(Looks::preview($kind, (string) $id).($anim ? ' px-still' : '')))
                        <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" aria-disabled="{{ $locked ? 'true' : 'false' }}" wire:key="look-{{ $kind }}-{{ $id ?: 'none' }}"
                                aria-label="{{ $label }}{{ $locked ? ', locked, '.Str::lower(Looks::onlyFor($item[2])) : ($isEx ? ', exclusive, animated' : ($anim ? ', animated' : ($id === '' ? ', no '.($kind === 'frame' ? 'border' : 'background') : ''))) }}"
                                wire:click="setLook('{{ $kind }}', '{{ $id }}')"
                                @class([
                                    'relative flex min-w-0 cursor-pointer rounded-[18px] border-2 transition active:scale-97',
                                    'flex-col items-center gap-2.5 px-1 pt-4 pb-2.5' => $kind === 'frame',
                                    'flex-col items-stretch gap-2 p-2 pb-2.25 text-left' => $kind === 'queue',
                                    'col-span-2 md:col-span-1' => $isEx && $kind === 'frame',
                                    'border-ink bg-surface' => $on,
                                    'border-dashed border-line2' => $locked,
                                    'border-line' => ! $on && ! $locked,
                                ])>
                            @if ($kind === 'frame')
                                <x-avatar :src="$user->avatarUrl()" :frame="$cls" :name="$user->shownName()" :color="$user->color()" @class(['size-12.5 text-[15px]', 'opacity-45' => $locked]) aria-hidden="true" />
                            @else
                                <span aria-hidden="true" @class(['flex h-11.5 items-center gap-2 rounded-xl border border-line bg-surface px-2.5', $cls => (bool) $cls, 'opacity-45' => $locked])>
                                    <span class="font-mono text-xs text-ink3">3</span>
                                    <x-avatar :src="$user->avatarUrl()" :frame="trim($pvFr.($pvFr ? ' px-still' : ''))" :name="$user->shownName()" :color="$user->color()" class="size-6.5 text-[9px]" />
                                    <span class="min-w-0 truncate text-[13px] font-bold">{{ $user->shownFirst() }}</span>
                                </span>
                            @endif
                            <span @class(['max-w-full truncate text-xs leading-[1.2] font-bold', 'text-center' => $kind === 'frame', 'px-1' => $kind === 'queue'])>{{ $label }}</span>
                            @if ($on)
                                <span aria-hidden="true" class="absolute top-1.5 right-1.5 z-3 flex size-5.5 animate-snap items-center justify-center rounded-full border-2 border-bg bg-lime text-on-lime"><x-icon name="check" :size="12" :stroke="3" /></span>
                            @elseif ($locked)
                                <span aria-hidden="true" class="absolute top-1.5 right-1.5 z-3 flex size-6 items-center justify-center rounded-full border-2 border-bg bg-ink text-bg"><x-icon name="lock" :size="12" :stroke="2.4" /></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
