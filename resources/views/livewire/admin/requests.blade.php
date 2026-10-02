@php
    $pending = $counts['pending'] ?? 0;
    $tiles = [
        ['Pending', $pending, $pending ? 'bg-hot-soft text-hot border-hot-line' : 'bg-surface text-ink border-line'],
        ['Active organizers', $counts['approved'] ?? 0, 'bg-surface text-ink border-line'],
        ['Members', $members, 'bg-surface text-ink border-line'],
        ['Live sessions', $live, 'bg-ink text-bg border-ink'],
    ];
    $status = [
        'pending' => ['Pending', 'bg-hot-soft text-hot'],
        'approved' => ['Approved', 'bg-lime-soft text-lime-text'],
        'declined' => ['Declined', 'bg-surface2 text-ink2'],
    ];
    $ghost = 'cursor-pointer rounded-full border-[1.5px] border-line2 font-bold text-ink transition hover:brightness-96 active:scale-97';
@endphp

<x-app-shell>
    <x-page>
        <x-page-header eyebrow="Admin" title="Organizer requests" description="Organizers can only create open plays after you approve them." />

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($tiles as [$label, $value, $classes])
                <div class="flex flex-col gap-2 rounded-[22px] border p-4 lg:p-5 {{ $classes }}">
                    <span class="font-mono text-[11px] tracking-widest uppercase">{{ $label }}</span>
                    <span class="font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] lg:text-[42px]">{{ $value }}</span>
                </div>
            @endforeach
        </div>

        <x-tabs label="Request status" class="self-start">
            @foreach (\App\Livewire\Admin\Requests::TABS as $key => $label)
                <x-tab :active="$tab === $key" wire:click="$set('tab', '{{ $key }}')">
                    {{ $label }}<span class="font-mono text-xs opacity-75">{{ $counts[$key] ?? 0 }}</span>
                </x-tab>
            @endforeach
        </x-tabs>

        @if ($requests->isEmpty())
            <p class="m-0 rounded-[22px] border-[1.5px] border-dashed border-line2 p-6 text-ink2">
                {{ match ($tab) { 'pending' => 'No pending requests. New organizer sign-ups will show up here.', 'approved' => 'No approved organizers yet.', default => 'No declined requests.' } }}
            </p>
        @endif

        <div class="grid gap-3.5 lg:grid-cols-2 2xl:grid-cols-3">
            @foreach ($requestsPage as $request)
                @php($fresh = $request->created_at?->gt(now()->subDay()))
                <div wire:key="req-{{ $request->id }}" @class(['flex animate-fade flex-col gap-3.5 rounded-3xl border-[1.5px] bg-surface p-4.5 lg:p-6', 'border-lime' => $fresh, 'border-line' => ! $fresh])>
                    <div class="flex items-center gap-3">
                        <x-avatar :src="$request->avatarUrl()" :frame="$request->frameClass()" :name="$request->name" :color="palette_color($request->id)" class="size-12 text-[15px]" />
                        <div class="flex min-w-0 flex-1 flex-col">
                            <span class="text-base font-bold">{{ $request->name }}</span>
                            <span class="truncate text-[13px] text-ink3">{{ $request->email }}</span>
                        </div>
                        @if ($fresh)
                            <span class="inline-flex h-6 shrink-0 items-center rounded-full bg-lime px-2.25 text-[11px] font-extrabold text-on-lime">NEW</span>
                        @endif
                        <span class="inline-flex h-6.5 shrink-0 items-center rounded-full px-2.5 text-xs font-bold {{ $status[$request->organizer_status][1] ?? '' }}">{{ $status[$request->organizer_status][0] ?? '' }}</span>
                    </div>

                    <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-ink2">
                        <span class="inline-flex items-center gap-1.5 font-bold text-ink"><x-icon name="users" :size="16" :stroke="1.9" />{{ $request->club }}</span>
                        <span class="inline-flex items-center gap-1.5"><x-icon name="pin" :size="16" :stroke="1.9" />{{ $request->city }}</span>
                        <span class="inline-flex items-center gap-1.5"><x-icon name="court" :size="16" :stroke="1.9" />{{ $request->courts ?? 0 }} {{ Str::plural('court', (int) $request->courts) }}</span>
                        @if ($request->has_played || $request->has_games)
                            <span class="inline-flex h-6 items-center gap-1.5 rounded-full border border-line bg-surface2 px-2.25 text-xs font-bold text-ink"><x-icon name="user" :size="14" :stroke="2" />Already a player · same account</span>
                        @endif
                    </div>

                    <p class="m-0 rounded-[14px] bg-surface2 px-3.5 py-3 text-sm leading-normal text-ink2">{{ $request->organizer_note ?: 'No details added.' }}</p>

                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <span class="text-[13px] text-ink3">Submitted {{ $request->created_at?->format('M j') }}</span>
                        @switch ($request->organizer_status)
                            @case ('pending')
                                <div class="flex gap-2">
                                    <button type="button" wire:click="decline({{ $request->id }})" class="{{ $ghost }} h-11 px-4 text-sm">Decline</button>
                                    <button type="button" wire:click="approve({{ $request->id }})" class="inline-flex h-11 cursor-pointer items-center gap-1.5 rounded-full bg-lime px-4.5 text-sm font-extrabold text-on-lime transition hover:brightness-96 active:scale-97">
                                        <x-icon name="check" :size="16" :stroke="2.6" />Approve
                                    </button>
                                </div>
                                @break
                            @case ('approved')
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-lime-text"><x-icon name="check" :size="16" :stroke="2.6" />Can host open plays</span>
                                @break
                            @default
                                <button type="button" wire:click="reopen({{ $request->id }})" class="{{ $ghost }} h-10 px-3.5 text-[13px]">Move back to pending</button>
                        @endswitch
                    </div>
                </div>
            @endforeach
        </div>
        <x-pager :page="$requestsPage" noun="requests" class="pt-0.5" />
    </x-page>
</x-app-shell>
