{{--
    Pages for one list (App\Livewire\Concerns\Pages::pageOf). Desktop shows numbered pages (1 … 4 5 6 … 9);
    mobile shows "Page x of y" between the arrows. Shown whenever the list has rows.
--}}
@props(['page', 'noun' => null])

@php
    $cur = $page->currentPage();
    $last = $page->lastPage();
    $name = $page->getPageName();
    // 0 marks a gap.
    if ($last <= 7) {
        $nums = range(1, $last);
    } else {
        $a = max(2, min($cur - 1, $last - 4));
        $b = min($last - 1, max($cur + 1, 5));
        $nums = [1, ...($a > 2 ? [0] : []), ...range($a, $b), ...($b < $last - 1 ? [0] : []), $last];
    }
    $arrow = 'flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-xl border-[1.5px] border-line bg-surface text-ink transition active:scale-97 disabled:cursor-default disabled:opacity-40';
@endphp

@if ($page->total() > 0)
    <nav aria-label="Pages" {{ $attributes->class('flex flex-wrap items-center justify-between gap-x-4 gap-y-2.5') }}>
        <span class="text-[13px] text-ink2">Showing {{ $page->firstItem() }}-{{ $page->lastItem() }} of {{ $page->total() }}{{ $noun ? ' '.$noun : '' }}</span>
        <div class="flex items-center gap-1.5">
            <button type="button" wire:click="previousPage('{{ $name }}')" @disabled($cur <= 1) aria-label="Previous page" class="{{ $arrow }}"><x-icon name="chevron-left" :size="18" :stroke="2.2" /></button>
            <div class="hidden items-center gap-1.5 lg:flex">
                @foreach ($nums as $n)
                    @if ($n === 0)
                        <span aria-hidden="true" class="w-5.5 text-center font-bold text-ink3">…</span>
                    @else
                        <button type="button" wire:click="setPage({{ $n }}, '{{ $name }}')" aria-label="Page {{ $n }}" @if ($n === $cur) aria-current="page" @endif
                                @class(['h-10 min-w-10 cursor-pointer rounded-xl border-[1.5px] px-2.5 font-mono text-base font-bold transition active:scale-97', 'border-ink bg-ink text-bg' => $n === $cur, 'border-line text-ink' => $n !== $cur])>{{ $n }}</button>
                    @endif
                @endforeach
            </div>
            <span class="min-w-24 text-center text-[13px] font-bold lg:hidden">Page {{ $cur }} of {{ $last }}</span>
            <button type="button" wire:click="nextPage('{{ $name }}')" @disabled($cur >= $last) aria-label="Next page" class="{{ $arrow }}"><x-icon name="chevron-right" :size="18" :stroke="2.2" /></button>
        </div>
    </nav>
@endif
