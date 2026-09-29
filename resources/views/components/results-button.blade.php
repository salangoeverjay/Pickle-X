{{-- Opens the results sheet (livewire:past-results) for a concluded open play. --}}
@props(['play'])

<button type="button" x-on:click="$dispatch('show-results', { id: {{ $play->id }} })" aria-label="Results for {{ $play->title }}, {{ $play->starts_at->format('M j') }}"
        class="inline-flex h-10.5 cursor-pointer items-center gap-1.5 rounded-full border-[1.5px] border-line2 bg-transparent px-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
    Results<x-icon name="chevron-right" :size="16" :stroke="2.2" />
</button>
