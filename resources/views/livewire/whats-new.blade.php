<x-app-shell>
    <x-page class="max-w-230">
        <div class="flex flex-col gap-1.5">
            <span class="font-mono text-xs tracking-[.14em] text-ink3 uppercase">PickleX updates</span>
            <h1 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] md:text-[38px] xl:text-[44px]">What's new</h1>
            <p class="mt-1 mb-0 max-w-155 text-ink2">New features, improvements and fixes. Each one shows once after you sign in, and they all stay here.</p>
            <span class="mt-1.5 inline-flex h-7.5 items-center gap-1.5 self-start rounded-full border border-line bg-surface px-3 text-[13px] font-bold">
                <x-icon name="check" :size="14" :stroke="2.4" />{{ $fresh ? count($fresh).' new '.Str::plural('update', count($fresh)).' · marked as read' : "You're all caught up" }}
            </span>
        </div>

        <div role="tablist" aria-label="Show updates" class="inline-flex max-w-full gap-1 self-start overflow-x-auto rounded-full border border-line bg-surface p-1 [scrollbar-width:none]">
            @foreach ($tabs as $key => [$label, $n])
                @php($on = $tab === $key)
                <button type="button" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')"
                        @class(['inline-flex h-10 shrink-0 cursor-pointer items-center gap-2 rounded-full px-3.5 text-sm font-bold whitespace-nowrap transition-colors', 'bg-ink text-bg' => $on, 'text-ink2' => ! $on])>
                    {{ $label }}<span @class(['inline-flex h-5 min-w-5.5 items-center justify-center rounded-full px-1.5 font-mono text-xs font-semibold', 'bg-lime text-on-lime' => $on, 'bg-surface2 text-ink2' => ! $on])>{{ $n }}</span>
                </button>
            @endforeach
        </div>

        <div role="list" class="flex flex-col gap-3">
            @foreach ($items as $n)
                <x-news-card role="listitem" wire:key="news-{{ $n->id }}" :news="$n" :fresh="in_array($n->id, $fresh)" :page="true" />
            @endforeach
        </div>
        @if ($items->isEmpty())
            <p class="m-0 rounded-[20px] border-[1.5px] border-dashed border-line2 px-5 py-6 text-ink2">{{ $all->isNotEmpty() ? 'Nothing here yet.' : 'No updates yet. New features, improvements and fixes show up here.' }}</p>
        @endif
    </x-page>
</x-app-shell>
