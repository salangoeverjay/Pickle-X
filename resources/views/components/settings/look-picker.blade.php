{{-- Your look: an avatar border and a queue background (App\Support\Looks). Each pick saves right away. --}}
@use('App\Support\Looks')
@props(['user', 'tab' => 'border', 'saved' => false])
@php
    $dev = (bool) $user->is_developer;
    $tier = $user->sponsor_tier;
    // What's worn: an exclusive look someone lost counts as none; a sponsor who moved tier shows the new tier.
    $cur = ['frame' => Looks::effective('frame', $user->frame, $dev, $tier) ?? '', 'queue' => Looks::effective('queue', $user->queue_bg, $dev, $tier) ?? ''];
    $tab = $tab === 'queue' ? 'queue' : 'border';
    $kind = $tab === 'border' ? 'frame' : 'queue';
    $list = Looks::list($kind);
    $std = [
        ['Animated', '10', 'anim', array_filter($list, fn ($l) => $l[1])],
        ['Still', '10 + none', 'still', ['' => ['None', false]] + array_filter($list, fn ($l) => ! $l[1])],
    ];
    // Exclusive: unlocked ones first.
    $exItems = collect(Looks::EXCLUSIVE)->map(fn ($l) => [$l[0], true, $l[1], ! Looks::unlocked($l[1], $dev, $tier)])->sortBy(fn ($l) => $l[3])->all();
    $open = collect($exItems)->reject(fn ($l) => $l[3])->count();
    $tierName = Looks::TIERS[$tier] ?? null;
    $exNote = match (true) {
        $dev && (bool) $tierName => "An admin marked you as a developer and a {$tierName} sponsor, so these are unlocked for you.",
        $dev => 'An admin marked your account as a developer, so this is unlocked for you.',
        (bool) $tierName => "Thanks for sponsoring PickleX. Your {$tierName} sponsor look is unlocked.",
        default => 'For developers and sponsors who help cover PickleX’s costs. An admin unlocks them.',
    };
    $ex = ['Exclusive', $open ? $open.' of '.count($exItems).' unlocked' : 'Locked', 'ex', $exItems];
    $groups = $open ? [$ex, ...$std] : [...$std, $ex];
    $frName = $cur['frame'] ? Looks::label('frame', $cur['frame']).(isset(Looks::EXCLUSIVE[$cur['frame']]) ? ' · exclusive' : (Looks::FRAMES[$cur['frame']][1] ? ' · animated' : '')) : 'No border';
    $pvFr = $user->frameClass();
@endphp

<div id="set-style" class="flex flex-col gap-4.5 rounded-[22px] border border-line bg-bg p-3.5 lg:p-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex min-w-0 flex-col gap-0.75">
            <h3 class="m-0 font-display text-lg font-bold tracking-[-0.02em]">Your look</h3>
            <span class="text-[13px] leading-[1.4] text-ink3">A border for your avatar everywhere it shows, and a background for your spot when you join the queue.</span>
        </div>
        <span role="status" aria-live="polite" class="font-mono text-xs tracking-[.08em] text-ink3 uppercase">{{ $saved ? 'Saved' : 'Changes save right away' }}</span>
    </div>

    <div class="flex flex-col gap-4 rounded-[18px] border border-line bg-surface p-4 lg:flex-row lg:items-center">
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
    </div>

    <div role="tablist" aria-label="Customize" class="flex self-stretch rounded-2xl border-[1.5px] border-line bg-surface2 p-1 lg:self-start">
        @foreach (['border' => 'Avatar border', 'queue' => 'Queue background'] as $t => $label)
            <button type="button" role="tab" id="look-tab-{{ $t }}" aria-selected="{{ $tab === $t ? 'true' : 'false' }}" aria-controls="look-panel" wire:click="$set('lookTab', '{{ $t }}')"
                    @class(['min-h-10 flex-1 cursor-pointer rounded-xl px-2.5 text-sm font-bold whitespace-nowrap transition-colors duration-200 lg:px-4', 'bg-surface text-ink shadow-[0_1px_2px_rgba(18,23,15,.12),0_4px_12px_-6px_rgba(18,23,15,.25)]' => $tab === $t, 'text-ink2' => $tab !== $t])>{{ $label }}</button>
        @endforeach
    </div>

    <div id="look-panel" role="tabpanel" aria-labelledby="look-tab-{{ $tab }}" class="flex flex-col gap-4.5">
        @foreach ($groups as [$glabel, $n, $gid, $items])
            @php($isEx = $gid === 'ex')
            <div class="flex flex-col gap-2.5">
                <div class="flex items-center gap-2">
                    @if ($gid === 'anim')
                        <x-icon name="sparkle" :size="16" class="text-lime-text" />
                    @elseif ($isEx)
                        <x-icon name="gem" :size="16" />
                    @endif
                    <h4 id="look-{{ $kind }}-{{ $gid }}" class="m-0 text-sm font-extrabold">{{ $glabel }}</h4>
                    <span class="font-mono text-xs text-ink3">{{ $n }}</span>
                </div>
                @if ($isEx)
                    <p class="-mt-1 mb-0 text-[13px] leading-[1.4] text-ink3">{{ $exNote }}</p>
                @endif
                <div role="radiogroup" aria-labelledby="look-{{ $kind }}-{{ $gid }}" @class(['grid gap-2.5', 'grid-cols-4 lg:grid-cols-6' => $kind === 'frame', 'grid-cols-2 lg:grid-cols-4' => $kind === 'queue'])>
                    @foreach ($items as $id => $item)
                        @php([$label, $anim] = $item)
                        @php($locked = $isEx && $item[3])
                        @php($on = ! $locked && $cur[$kind] === (string) $id)
                        @php($cls = Looks::preview($kind, (string) $id))
                        <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" aria-disabled="{{ $locked ? 'true' : 'false' }}" wire:key="look-{{ $kind }}-{{ $id ?: 'none' }}"
                                aria-label="{{ $label }}{{ $locked ? ', locked, '.Str::lower(Looks::onlyFor($item[2])) : ($isEx ? ', exclusive, animated' : ($anim ? ', animated' : ($id === '' ? ', no '.($kind === 'frame' ? 'border' : 'background') : ''))) }}"
                                wire:click="setLook('{{ $kind }}', '{{ $id }}')"
                                @class([
                                    'relative flex min-w-0 cursor-pointer rounded-[18px] border-2 transition active:scale-97',
                                    'flex-col items-center gap-2.5 px-1 pt-4 pb-2.5' => $kind === 'frame',
                                    'flex-col items-stretch gap-2 p-2 pb-2.25 text-left' => $kind === 'queue',
                                    'col-span-2 lg:col-span-1' => $isEx && $kind === 'frame',
                                    'border-ink bg-surface' => $on,
                                    'border-dashed border-line2' => $locked,
                                    'border-line' => ! $on && ! $locked,
                                ])>
                            @if ($kind === 'frame')
                                <x-avatar :src="$user->avatarUrl()" :frame="$cls" :name="$user->shownName()" :color="$user->color()" @class(['size-12.5 text-[15px]', 'opacity-45' => $locked]) aria-hidden="true" />
                            @else
                                <span aria-hidden="true" @class(['flex h-11.5 items-center gap-2 rounded-xl border border-line bg-surface px-2.5', $cls => (bool) $cls, 'opacity-45' => $locked])>
                                    <span class="font-mono text-xs text-ink3">3</span>
                                    <x-avatar :src="$user->avatarUrl()" :frame="$pvFr" :name="$user->shownName()" :color="$user->color()" class="size-6.5 text-[9px]" />
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
