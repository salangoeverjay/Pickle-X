{{-- One spot in the live view queue. --}}
<div wire:key="q-{{ $q['pos'] }}" @class(['flex items-center gap-2.5 rounded-xl p-2', 'bg-lime-soft' => $q['you'], $q['bg'] => (bool) $q['bg']])>
    <span class="w-5 shrink-0 font-mono text-xs text-ink3">{{ $q['pos'] }}</span>
    <div class="relative flex shrink-0">
        @foreach ($q['people'] as $k => $p)
            <x-avatar :src="$p->avatarUrl()" :frame="$p->frameClass()" :name="$p->name" :color="$p->color()" @class(['size-8 border-2 border-surface text-[11px]', '-ml-2.5' => $k > 0 && ! $q['framed'], 'ml-2' => $k > 0 && $q['framed']]) />
        @endforeach
        @if ($q['pair'])
            <span role="img" aria-label="Locked partners" class="absolute -bottom-1 z-3 {{ $q['framed'] ? 'left-7' : 'left-4.75' }} flex size-4.25 items-center justify-center rounded-full border-2 border-surface bg-lime text-on-lime"><x-icon name="lock" :size="8" :stroke="2.8" /></span>
        @endif
    </div>
    <span class="flex min-w-0 flex-1 flex-col">
        <span class="flex min-w-0 items-center gap-1.5 text-sm font-bold">
            <span class="truncate">{{ $q['label'] }}</span>
            @if ($q['you'])
                <span class="inline-flex h-5 shrink-0 items-center rounded-full bg-lime px-1.75 text-[10px] font-extrabold text-on-lime">YOU</span>
            @endif
        </span>
        @if ($q['meta'])
            <span class="text-xs font-semibold {{ $q['metaClass'] }}">{{ $q['meta'] }}</span>
        @endif
    </span>
    @if ($q['up'])
        <span class="inline-flex h-5.5 shrink-0 items-center rounded-full bg-lime px-2 text-[11px] font-extrabold text-on-lime">NEXT</span>
    @endif
</div>
