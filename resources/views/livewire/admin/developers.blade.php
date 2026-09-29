<x-app-shell>
    <x-page>
        <x-page-header eyebrow="Admin" title="Developers" description="Mark the accounts of the people who build PickleX. A developer account unlocks the exclusive Developer border and queue background. Only admins can change this." />

        <section aria-labelledby="dev-look-h" class="flex flex-col gap-5 rounded-3xl border border-line bg-[#12170F] p-4.5 text-[#F3F2EA] lg:flex-row lg:items-center lg:gap-9 lg:p-6">
            <div class="flex min-w-0 items-center gap-5">
                <span aria-hidden="true" class="px-fr px-fr-dev flex size-21 shrink-0 items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="code" :size="34" :stroke="2.2" /></span>
                <div class="flex min-w-0 flex-col gap-1.5">
                    <span class="inline-flex h-5.5 items-center self-start rounded-full bg-lime px-2.25 text-[11px] font-extrabold tracking-[.06em] text-on-lime">EXCLUSIVE</span>
                    <h2 id="dev-look-h" class="m-0 font-display text-2xl font-extrabold tracking-[-0.03em]">Developer look</h2>
                    <span class="text-sm leading-[1.45] text-[#F3F2EA]/72">An animated code border with a &lt;/&gt; tag, and a scrolling code queue background. Members see it locked in their picker.</span>
                </div>
            </div>
            <div class="flex min-w-0 flex-1 flex-col gap-2 lg:min-w-90">
                <span class="font-mono text-[11px] tracking-widest text-[#F3F2EA]/60 uppercase">In the queue</span>
                <div role="img" aria-label="Preview of the Developer queue background" class="px-qb px-qb-dev flex min-h-16 items-center gap-3 rounded-2xl border-[1.5px] border-[#F3F2EA]/16 bg-[#F3F2EA]/5 py-1.5 pr-4 pl-3.5">
                    <span class="w-4.5 shrink-0 font-mono text-[13px] text-[#F3F2EA]/60">1</span>
                    <span class="px-fr px-fr-dev flex size-9.5 shrink-0 items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="code" :size="18" :stroke="2.4" /></span>
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="truncate text-[15px] font-bold">Developer account</span>
                        <span class="text-[12.5px] text-[#F3F2EA]/66">Up next · Court 2</span>
                    </span>
                </div>
            </div>
        </section>

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <section aria-labelledby="dev-list-h" class="flex flex-col gap-3.5 rounded-3xl border border-line bg-surface p-4.5 lg:p-6">
                <div class="flex items-baseline justify-between gap-2.5">
                    <h2 id="dev-list-h" class="m-0 font-display text-[21px] font-bold tracking-[-0.02em]">Developer accounts</h2>
                    <span class="shrink-0 font-mono text-xs tracking-[.08em] text-ink3 uppercase">{{ $devs->count() }} {{ Str::plural('account', $devs->count()) }}</span>
                </div>
                @if ($devs->isEmpty())
                    <p class="m-0 rounded-[18px] border-[1.5px] border-dashed border-line2 p-4.5 text-sm text-ink2">No developer accounts yet. Search for an account to mark it.</p>
                @else
                    <div role="list" class="flex flex-col gap-2.5">
                        @foreach ($devs as $d)
                            @php($wf = $d->frame === 'dev')
                            @php($wq = $d->queue_bg === 'dev')
                            <div role="listitem" wire:key="dev-{{ $d->id }}" class="flex animate-fade items-center gap-3 rounded-[18px] border border-line bg-surface2 py-3 pr-3 pl-3.5">
                                <x-avatar :src="$d->avatarUrl()" :frame="$d->frameClass()" :name="$d->name" :color="$d->color()" class="size-11.5 text-[15px]" />
                                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="min-w-0 truncate text-base font-bold">{{ $d->name }}</span>
                                        <span class="inline-flex h-5 shrink-0 items-center gap-1 rounded-full bg-ink px-1.75 text-[10.5px] font-extrabold tracking-[.05em] text-bg"><x-icon name="code" :size="11" :stroke="2.6" />DEV</span>
                                    </span>
                                    <span class="truncate text-[13px] text-ink3">{{ $d->email }}</span>
                                    <span class="text-[12.5px] text-ink2">{{ $wf && $wq ? 'Wearing the border and queue background' : ($wf ? 'Wearing the border' : ($wq ? 'Wearing the queue background' : 'Unlocked · not worn yet')) }}</span>
                                </div>
                                <button type="button" wire:click="removeDeveloper({{ $d->id }})" aria-label="Remove developer from {{ $d->name }}"
                                        class="h-10 shrink-0 cursor-pointer rounded-full border-[1.5px] border-line2 bg-surface px-3.5 text-[13px] font-bold text-hot transition hover:brightness-96 active:scale-97">Remove</button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <p class="m-0 text-[13px] leading-[1.45] text-ink3">Removing developer locks the look again. If they were wearing it, their border and queue background go back to none.</p>
            </section>

            <section aria-labelledby="dev-add-h" class="flex flex-col gap-3.5 rounded-3xl border border-line bg-surface p-4.5 lg:p-6">
                <div>
                    <h2 id="dev-add-h" class="m-0 font-display text-[21px] font-bold tracking-[-0.02em]">Mark an account as developer</h2>
                    <p class="mt-1 mb-0 text-sm leading-[1.45] text-ink2">Search by name or email. Check the email so you pick the right person.</p>
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
                    <p role="status" class="m-0 rounded-2xl bg-surface2 px-4 py-3.5 text-sm text-ink2">No account matches “{{ trim($q) }}”, or they are already a developer.</p>
                @else
                    <div role="list" aria-label="Matching accounts" class="flex flex-col gap-2">
                        @foreach ($results as $r)
                            @php($g = $games($r))
                            <div role="listitem" wire:key="res-{{ $r->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2.5 rounded-[18px] border-[1.5px] border-line px-3 py-2.5 lg:flex-nowrap">
                                <x-avatar :src="$r->avatarUrl()" :frame="$r->frameClass()" :name="$r->name" :color="$r->color()" class="size-11 text-sm" />
                                <div class="flex min-w-0 flex-1 flex-col gap-px">
                                    <span class="truncate text-[15px] font-bold">{{ $r->name }}</span>
                                    <span class="truncate text-[13px] text-ink2">{{ $r->email }}</span>
                                    <span class="truncate text-xs text-ink3">{{ $r->club ? $r->club.' · ' : '' }}{{ $g }} {{ Str::plural('game', $g) }}</span>
                                </div>
                                <button type="button" wire:click="makeDeveloper({{ $r->id }})" aria-label="Make {{ $r->name }} a developer"
                                        class="ml-14 inline-flex h-10.5 w-[calc(100%-56px)] shrink-0 cursor-pointer items-center justify-center gap-1.75 rounded-full bg-lime px-4 text-sm font-extrabold whitespace-nowrap text-on-lime transition hover:brightness-96 active:scale-97 lg:ml-0 lg:w-auto">
                                    <x-icon name="code" :size="16" :stroke="2.4" /><span>Make developer</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </x-page>
</x-app-shell>
