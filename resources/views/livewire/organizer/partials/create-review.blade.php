{{-- Review step of Create / Edit open play. Data from OpenPlays::showReview(). --}}
@php($r = $reviewData)
@php($changes = $r['changes'])
<div class="flex animate-fade flex-col gap-4">
    <div role="status" @class([
        'flex items-start gap-3 rounded-[18px] border px-4 py-3.5',
        'border-line bg-surface' => $editId && ! $changes,
        'border-lime bg-lime-soft' => ! ($editId && ! $changes),
    ])>
        <span aria-hidden="true" class="flex size-8.5 shrink-0 items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="clipboard" :size="18" /></span>
        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
            <span class="text-[15px] font-extrabold">
                {{ $editId ? ($changes ? $changes.' '.($changes === 1 ? 'change' : 'changes').' to review' : 'Nothing has changed yet') : 'Check the details before you publish' }}
            </span>
            <span class="text-[13px] leading-[1.45] text-ink2">
                {{ $editId ? ($changes ? 'Changed details are highlighted. Save to update the open play for everyone.' : 'Go back and change a detail, or close the sheet to keep it as it is.') : 'This is what members will see. Go back to fix anything first.' }}
            </span>
        </div>
    </div>

    <div class="flex flex-col overflow-hidden rounded-[20px] border border-line bg-surface">
        <div class="flex items-center gap-3.5 px-4.5 py-4">
            <div class="flex h-15.5 w-14 shrink-0 flex-col items-center justify-center gap-0.5 rounded-2xl border border-line bg-surface2">
                <span class="font-mono text-[10px] tracking-widest text-ink3 uppercase">{{ $r['mon'] }}</span>
                <span class="font-display text-2xl leading-none font-extrabold tracking-[-0.04em]">{{ $r['day'] }}</span>
                <span class="text-[10px] text-ink3">{{ $r['dow'] }}</span>
            </div>
            <div class="flex min-w-0 flex-1 flex-col gap-0.75">
                <span class="font-mono text-[10px] tracking-[.12em] text-ink3 uppercase">{{ $club }}</span>
                <span class="font-display text-[21px] leading-[1.15] font-extrabold tracking-[-0.02em] wrap-anywhere">{{ $title }}</span>
                <span class="text-[13px] text-ink2">{{ $r['line'] }}</span>
            </div>
        </div>
        @foreach ($r['rows'] as $row)
            <div wire:key="rv-{{ $row['key'] }}" @class(['grid items-baseline gap-x-4 gap-y-0.5 border-t border-line px-4.5 py-3 lg:grid-cols-[112px_minmax(0,1fr)]', 'bg-lime-soft' => $row['changed']])>
                <span class="text-[13px] font-bold text-ink3">{{ $row['label'] }}</span>
                <div class="flex min-w-0 flex-col gap-1">
                    <span class="text-[15px] leading-[1.4] font-semibold wrap-anywhere">{{ $row['value'] }}</span>
                    @if ($row['changed'])
                        <span class="flex flex-wrap items-center gap-1.5 text-xs text-ink3">
                            <span class="inline-flex h-5 items-center rounded-full bg-ink px-1.75 text-[10px] font-extrabold tracking-[.04em] text-bg">CHANGED</span>
                            <span class="line-through wrap-anywhere">{{ $row['was'] }}</span>
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
        @if ($r['hasQr'])
            <div class="flex items-center gap-3 border-t border-line px-4.5 py-3">
                <div class="shrink-0 rounded-xl border border-[#DEDCD0] bg-white p-1.25"><img x-bind:src="src" alt="{{ $r['qrAlt'] }}" class="block size-16 object-contain"></div>
                <span class="text-[13px] leading-[1.45] text-ink2">Members see this QR, the fee and the account name when they tap Pay.</span>
            </div>
        @endif
    </div>

    @if ($r['paid'])
        @php($n = count($r['paid']))
        <div class="flex flex-col gap-2.5">
            <span class="text-[13px] font-bold text-ink2">{{ $n }} paid {{ Str::plural('player', $n) }}</span>
            <div class="flex flex-wrap gap-1.5">
                @foreach (array_slice($r['paid'], 0, 8) as $p)
                    <span class="inline-flex h-7.5 items-center gap-1.5 rounded-full border border-line bg-surface pr-2.5 pl-1 text-[13px] font-semibold">
                        @if ($p['guest'])
                            <span class="flex size-5.5 items-center justify-center rounded-full bg-surface2 text-[9px] font-extrabold text-ink2">{{ initials($p['name']) }}</span>
                        @else
                            <x-avatar :name="$p['name']" :color="$p['color']" :src="$p['avatar']" class="size-5.5 text-[9px]" />
                        @endif
                        <span>{{ $p['name'] }}</span>
                        @if ($p['guest'])
                            <span class="text-[11px] font-bold text-ink3">guest</span>
                        @endif
                    </span>
                @endforeach
                @if ($n > 8)
                    <span class="inline-flex h-7.5 items-center rounded-full bg-surface2 px-2.5 text-[13px] font-bold text-ink2">+{{ $n - 8 }} more</span>
                @endif
            </div>
        </div>
    @endif

    @foreach ($r['notes'] as $note)
        <p class="m-0 flex items-start gap-2.5 text-[13px] leading-normal text-ink2"><x-icon name="info" :size="17" class="mt-px text-ink3" /><span>{{ $note }}</span></p>
    @endforeach
</div>
