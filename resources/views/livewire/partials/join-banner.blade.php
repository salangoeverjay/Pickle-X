{{-- On Log in and Sign up, after "Log in" or "Create an account" on a share link: which open play you're about to join, and a way out. --}}
@if ($joining)
    <div role="note" class="flex items-center gap-3 rounded-[18px] border-[1.5px] border-lime bg-lime-soft py-2.5 pr-1.5 pl-3">
        <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lime text-on-lime"><x-icon name="link" :size="18" /></span>
        <span class="flex min-w-0 flex-1 flex-col">
            <span class="font-mono text-[11px] tracking-[.1em] text-lime-text uppercase">{{ $kicker }}</span>
            <strong class="truncate text-[15px]">{{ $joining->title }}</strong>
            <span class="truncate text-xs text-ink2">{{ $joining->whenLabel() }}, {{ $joining->timeRange() }}, {{ $joining->venue }}</span>
        </span>
        <button type="button" wire:click="cancelJoin" aria-label="Don't join this open play" title="Don't join" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink2 transition hover:bg-surface"><x-icon name="x" :size="16" /></button>
    </div>
@endif
