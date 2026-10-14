@php
    // Role pills: [background, text, border].
    $pill = ['member' => 'bg-surface2 text-ink2 border-line', 'organizer' => 'bg-court text-white border-court', 'admin' => 'bg-ink text-bg border-ink'];
    $roles = App\Livewire\Admin\Users::ROLES;
    $badge = 'inline-flex h-6 shrink-0 items-center rounded-full border px-2.25 text-xs font-extrabold whitespace-nowrap';
    $you = 'inline-flex h-5 shrink-0 items-center rounded-full bg-lime px-1.75 text-[10.5px] font-extrabold text-on-lime';
    $btn = 'h-11 shrink-0 cursor-pointer rounded-full border-[1.5px] px-4 text-sm font-bold transition active:scale-97';
    // "No-shows · until Oct 26" for a suspended account.
    $until = fn ($u) => $u->suspended_until ? 'until '.$u->suspended_until->format('M j') : 'until restored';
    $why = fn ($u) => $u->suspensionReason()[0].' · '.$until($u);
@endphp

<x-app-shell>
    <x-page>
        <x-page-header eyebrow="Admin" title="Users" description="Everyone with a PickleX account. Change roles, suspend or restore accounts, sign people out everywhere and delete accounts." />

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($tiles as [$label, $n])
                @php($hot = $label === 'Suspended' && $n)
                <div @class(['flex flex-col gap-2 rounded-[22px] border p-4 lg:p-5', 'border-hot-line bg-hot-soft text-hot' => $hot, 'border-line bg-surface' => ! $hot])>
                    <span class="font-mono text-[11px] tracking-widest uppercase">{{ $label }}</span>
                    <span class="font-display text-[34px] leading-none font-extrabold tracking-[-0.04em] lg:text-[40px]">{{ $n }}</span>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <div role="tablist" aria-label="Show accounts" class="inline-flex max-w-full overflow-x-auto rounded-full border border-line bg-surface p-1 [scrollbar-width:none]">
                @foreach ($tabs as $key => [$label, $n])
                    @php($on = $tab === $key)
                    <button type="button" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')"
                            @class(['inline-flex h-10 shrink-0 cursor-pointer items-center gap-1.75 rounded-full px-3.5 text-sm font-bold whitespace-nowrap transition-colors', 'bg-ink text-bg' => $on, 'text-ink2' => ! $on])>
                        {{ $label }}<span @class(['inline-flex h-5 min-w-5.5 items-center justify-center rounded-full px-1.5 font-mono text-xs font-bold', 'bg-lime text-on-lime' => $on, 'bg-surface2 text-ink2' => ! $on])>{{ $n }}</span>
                    </button>
                @endforeach
            </div>
            <div class="relative min-w-full flex-1 lg:min-w-60">
                <label for="ua-q" class="sr-only">Search accounts</label>
                <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink3" />
                <input id="ua-q" type="search" wire:model.live.debounce.250ms="q" placeholder="Search by name or email" autocomplete="off"
                       class="h-11 w-full rounded-full border-[1.5px] border-line bg-surface pr-4 pl-10.5 text-sm text-ink outline-none focus:border-ink [&::-webkit-search-cancel-button]:hidden">
            </div>
        </div>

        <section aria-label="Accounts" class="overflow-hidden rounded-3xl border border-line bg-surface">
            <div class="flex items-baseline justify-between gap-2.5 border-b border-line px-5 py-3.5">
                <span class="font-bold">Accounts</span>
                <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $rows->count() === $total ? $total.' '.Str::plural('account', $total) : $rows->count().' of '.$total.' accounts' }}</span>
            </div>
            <div class="hidden grid-cols-[minmax(0,2.4fr)_minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,.6fr)_minmax(0,.9fr)_minmax(0,.9fr)_104px] gap-3.5 border-b border-line px-5 py-3 font-mono text-[11px] tracking-widest text-ink3 uppercase lg:grid">
                <span>User</span><span>Role</span><span>Home club</span><span>Games</span><span>Last active</span><span>Status</span><span></span>
            </div>
            @forelse ($rowsPage as $u)
                @php($r = $u->accountRole())
                @php($me = $u->id === auth()->id())
                <div wire:key="u-{{ $u->id }}" class="flex items-center gap-3 border-b border-line px-4 py-3.5 last:border-b-0 lg:grid lg:grid-cols-[minmax(0,2.4fr)_minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,.6fr)_minmax(0,.9fr)_minmax(0,.9fr)_104px] lg:gap-3.5 lg:px-5 lg:py-3">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <x-avatar :src="$u->avatarUrl()" :frame="$u->frameClass()" :name="$u->name" :color="$u->color()" class="size-10.5 text-[13px] lg:size-10" />
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="flex min-w-0 items-center gap-2"><span class="truncate font-bold">{{ $u->name }}</span>@if ($me)<span class="{{ $you }}">YOU</span>@endif</span>
                            <span class="truncate text-[12.5px] text-ink3 lg:text-[13px]">{{ $u->email }}</span>
                            <span class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5 lg:hidden">
                                <span class="{{ $badge }} {{ $pill[$r] }}">{{ $roles[$r] }}</span>
                                @if ($u->isSuspended())<span class="{{ $badge }} border-hot-line bg-hot-soft text-hot">Suspended</span>@endif
                            </span>
                            @if ($u->isSuspended())<span class="text-[12.5px] font-semibold text-hot lg:hidden">{{ $why($u) }}</span>@endif
                        </div>
                    </div>
                    <span class="hidden lg:block"><span class="{{ $badge }} {{ $pill[$r] }}">{{ $roles[$r] }}</span></span>
                    <span class="hidden truncate text-sm text-ink2 lg:block">{{ $u->club ?: '-' }}</span>
                    <span class="hidden font-mono text-[15px] font-semibold lg:block">{{ $games[$u->id] ?? 0 }}</span>
                    <span class="hidden text-sm text-ink2 lg:block">{{ $lastActive($u) }}</span>
                    <span class="hidden min-w-0 flex-col items-start gap-0.75 lg:flex">
                        @if ($u->isSuspended())
                            <span class="{{ $badge }} border-hot-line bg-hot-soft text-hot">Suspended</span>
                            <span class="max-w-full truncate text-[12.5px] font-semibold text-ink2">{{ $u->suspensionReason()[0] }}</span>
                            <span class="text-xs whitespace-nowrap text-ink3">{{ Str::ucfirst($until($u)) }}</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-ink2"><span aria-hidden="true" class="size-2 rounded-full bg-lime-text"></span>Active</span>
                        @endif
                    </span>
                    <button type="button" wire:click="open({{ $u->id }})" aria-label="Manage {{ $u->name }}" class="{{ $btn }} h-10 border-line2 text-ink hover:bg-surface2">Manage</button>
                </div>
            @empty
                <p class="m-0 px-6 py-7 text-ink2">{{ trim($q) !== '' ? 'No accounts match "'.trim($q).'".' : 'No accounts here yet.' }}</p>
            @endforelse
            <x-pager :page="$rowsPage" noun="accounts" class="border-t border-line px-5 py-3" />
        </section>
    </x-page>

    {{-- Manage an account: bottom sheet on mobile, centered on desktop. --}}
    @if ($managed)
        @php($r = $managed->accountRole())
        @php($self = $managed->id === auth()->id())
        <div wire:key="um-{{ $managed->id }}" x-data x-on:keydown.escape.window="$wire.close()" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <button type="button" wire:click="close" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
            <div role="dialog" aria-modal="true" aria-labelledby="um-title" x-trap.noscroll="true" class="relative flex max-h-full w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-170 md:max-w-full md:rounded-[28px]">
                <div class="flex flex-col gap-5 overflow-y-auto px-6 pt-6 pb-6.5">
                    <div class="flex items-start gap-3.5">
                        <x-avatar :src="$managed->avatarUrl()" :frame="$managed->frameClass()" :name="$managed->name" :color="$managed->color()" class="size-14 text-lg" />
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <h2 id="um-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $managed->name }}</h2>
                            <span class="text-sm break-all text-ink2">{{ $managed->email }}</span>
                            <span class="text-[13px] text-ink3">{{ collect([$managed->club, ($n = $games[$managed->id] ?? 0).' '.Str::plural('game', $n), 'Joined '.$managed->created_at->format('M Y'), 'Active '.Str::lcfirst($lastActive($managed))])->filter()->join(' · ') }}</span>
                            <span class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                                <span class="{{ $badge }} {{ $pill[$r] }}">{{ $roles[$r] }}</span>
                                @if ($managed->isSuspended())
                                    <span class="{{ $badge }} border-hot-line bg-hot-soft text-hot">Suspended</span>
                                    <span class="text-[13px] text-hot">{{ $why($managed) }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-ink2"><span aria-hidden="true" class="size-2 rounded-full bg-lime-text"></span>Active</span>
                                @endif
                                @if ($self)<span class="{{ $you }}">YOU</span>@endif
                            </span>
                        </div>
                        <button type="button" wire:click="close" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full bg-surface2 text-ink"><x-icon name="x" :size="18" /></button>
                    </div>

                    <section aria-labelledby="um-role-h" class="flex flex-col gap-2.5">
                        <h3 id="um-role-h" class="m-0 font-display text-[19px] font-bold tracking-[-0.02em]">Role</h3>
                        @if ($self)
                            <p class="m-0 text-sm text-ink2">This is your account. Another admin has to change your role.</p>
                        @endif
                        <div role="radiogroup" aria-labelledby="um-role-h" class="grid grid-cols-1 gap-2 lg:grid-cols-3">
                            @foreach ($roles as $key => $label)
                                @php($on = $draft === $key)
                                <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" @disabled($self) wire:click="pickRole('{{ $key }}')"
                                        @class(['flex min-h-18 cursor-pointer items-start gap-2.5 rounded-2xl border-[1.5px] px-3.5 py-3 text-left text-ink transition active:scale-97 disabled:cursor-not-allowed disabled:opacity-70', 'border-ink bg-surface' => $on, 'border-line' => ! $on])>
                                    <span aria-hidden="true" class="mt-0.5 box-border flex size-4.5 shrink-0 items-center justify-center rounded-full border-2 border-ink2"><span @class(['size-2 rounded-full', 'bg-ink' => $on])></span></span>
                                    <span class="flex flex-col gap-0.5"><span class="text-[15px] font-bold">{{ $label }}</span><span class="text-[13px] leading-[1.4] text-ink2">{{ $descriptions[$key] }}</span></span>
                                </button>
                            @endforeach
                        </div>
                        @unless ($self)
                            <div class="flex justify-end">
                                <button type="button" wire:click="saveRole" aria-disabled="{{ $draft === $r ? 'true' : 'false' }}"
                                        @class(['h-12 cursor-pointer rounded-full px-5.5 text-[15px] font-extrabold transition active:scale-97', 'bg-lime text-on-lime' => $draft !== $r, 'bg-surface2 text-ink3' => $draft === $r])>Save role</button>
                            </div>
                        @endunless
                    </section>

                    <section aria-labelledby="um-acct-h" class="flex flex-col gap-2.5">
                        <h3 id="um-acct-h" class="m-0 font-display text-[19px] font-bold tracking-[-0.02em]">Account</h3>
                        <div class="divide-y divide-line overflow-hidden rounded-[18px] border border-line bg-surface">
                            @php($row = 'flex flex-wrap items-center gap-x-3.5 gap-y-2.5 px-4 py-3.5')
                            @php($text = 'flex min-w-0 flex-[1_1_240px] flex-col gap-0.5')
                            <div class="{{ $row }}">
                                <div class="{{ $text }}"><span class="text-[15px] font-bold">Send a password reset</span><span class="text-[13px] leading-[1.45] text-ink2">Emails {{ $managed->email }} a link to set a new password.</span></div>
                                <button type="button" wire:click="sendReset" class="{{ $btn }} border-line2 text-ink hover:bg-surface2">Send reset</button>
                            </div>
                            @unless ($self)
                                <div class="{{ $row }}">
                                    <div class="{{ $text }}"><span class="text-[15px] font-bold">Sign out of all devices</span><span class="text-[13px] leading-[1.45] text-ink2">Ends every session and turns off Remember me. Their passkeys stay.</span></div>
                                    <button type="button" wire:click="signOut" class="{{ $btn }} border-line2 text-ink hover:bg-surface2">Sign out everywhere</button>
                                </div>
                                @if ($managed->isSuspended())
                                    @php($first = $managed->firstName())
                                    <div id="um-susinfo" class="flex flex-col gap-3 bg-hot-soft p-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="font-mono text-[11px] tracking-[.12em] text-hot uppercase">Suspended · {{ $managed->suspensionReason()[0] }}</span>
                                            <span class="text-base leading-[1.3] font-extrabold">{{ $managed->suspended_until ? 'Lifts on its own '.$managed->suspended_until->format('D, M j').' · '.($left = max(1, (int) ceil(now()->diffInDays($managed->suspended_until)))).' '.Str::plural('day', $left).' left' : 'Until an admin restores it' }}</span>
                                            <span class="text-[13px] leading-[1.45] text-ink2">Since {{ $managed->suspended_at->format('D, M j') }} · by {{ $managed->suspended_by === auth()->id() ? 'you' : ($managed->suspender?->name ?? 'an admin') }} · {{ $first }} was emailed the reason</span>
                                        </div>
                                        @if ($managed->suspension_note)
                                            <div class="flex flex-col gap-1 rounded-r-xl border-l-3 border-hot bg-bg px-3.5 py-2.5">
                                                <span class="text-xs font-bold text-ink3">Note sent with the email</span>
                                                <span class="text-sm leading-normal">{{ $managed->suspension_note }}</span>
                                            </div>
                                        @endif
                                        <div class="flex flex-wrap items-center gap-x-3.5 gap-y-2.5">
                                            <span class="min-w-0 flex-[1_1_220px] text-[13px] leading-[1.45] text-ink2">Restoring lets them log in and join open plays again. We email them that it's lifted.</span>
                                            <button type="button" wire:click="restore" class="{{ $btn }} border-lime bg-lime text-on-lime hover:brightness-96">Restore now</button>
                                        </div>
                                    </div>
                                @elseif ($suspending)
                                    @php($first = $managed->firstName())
                                    @php($lift = $susLength === 'open' ? null : today()->addDays((int) $susLength))
                                    @php($noteReq = $susReason === 'other')
                                    @php($off = $susReason === '' || ($noteReq && trim($susNote) === ''))
                                    <div role="group" aria-labelledby="um-sus-h" class="flex animate-fade flex-col gap-4.5 bg-surface2 px-4 pt-4.5 pb-4">
                                        <div class="flex flex-col gap-1">
                                            <span id="um-sus-h" class="font-display text-lg font-extrabold tracking-[-0.02em]">Suspend {{ $first }}'s account</span>
                                            <span class="text-[13px] leading-[1.45] text-ink2">They can't log in, join open plays or check in. Their games and stats stay, and spots they hold in upcoming open plays are freed.</span>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <span id="um-sus-r" class="text-sm font-bold">Reason</span>
                                            <div role="radiogroup" aria-labelledby="um-sus-r" class="grid grid-cols-1 gap-2 lg:grid-cols-2">
                                                @foreach (App\Models\User::SUSPENSION_REASONS as $key => [$label, $desc])
                                                    @php($on = $susReason === $key)
                                                    <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" wire:click="$set('susReason', '{{ $key }}')"
                                                            @class(['flex min-h-14.5 cursor-pointer items-start gap-2.5 rounded-[14px] border-[1.5px] px-3 py-2.5 text-left text-ink transition active:scale-97', 'border-ink bg-surface' => $on, 'border-line' => ! $on])>
                                                        <span aria-hidden="true" class="mt-0.5 box-border flex size-4.5 shrink-0 items-center justify-center rounded-full border-2 border-ink2"><span @class(['size-2 rounded-full', 'bg-ink' => $on])></span></span>
                                                        <span class="flex min-w-0 flex-col gap-0.5"><span class="text-sm font-bold">{{ $label }}</span><span class="text-[12.5px] leading-[1.35] text-ink2">{{ $desc }}</span></span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <span id="um-sus-l" class="text-sm font-bold">Length</span>
                                            <div role="radiogroup" aria-labelledby="um-sus-l" class="grid grid-cols-[minmax(0,.8fr)_minmax(0,.85fr)_minmax(0,1.35fr)] gap-1 rounded-full border border-line bg-bg p-1 lg:grid-cols-3">
                                                @foreach (['7' => '7 days', '30' => '30 days', 'open' => 'Until restored'] as $key => $label)
                                                    <button type="button" role="radio" aria-checked="{{ $susLength === (string) $key ? 'true' : 'false' }}" wire:click="$set('susLength', '{{ $key }}')"
                                                            @class(['h-10 min-w-0 cursor-pointer rounded-full px-2 text-sm font-bold whitespace-nowrap transition active:scale-97', 'bg-ink text-bg' => $susLength === (string) $key, 'text-ink2' => $susLength !== (string) $key])>{{ $label }}</button>
                                                @endforeach
                                            </div>
                                            <span class="text-[13px] text-ink2">{{ $lift ? 'Lifts on its own on '.$lift->format('D, M j').'. You can restore it sooner.' : 'Stays until an admin restores it.' }}</span>
                                        </div>
                                        <label class="flex flex-col gap-2" x-data="{ n: {{ mb_strlen($susNote) }} }">
                                            <span class="flex items-baseline justify-between gap-2"><span class="text-sm font-bold">Note to {{ $first }} {{ $noteReq ? '(required)' : '(optional)' }}</span><span class="text-xs font-semibold text-ink3 tabular-nums" x-text="n + ' / 300'">{{ mb_strlen($susNote) }} / 300</span></span>
                                            <textarea wire:model.live.debounce.400ms="susNote" x-on:input="n = $event.target.value.length" rows="3" maxlength="300" aria-describedby="um-sus-nh" placeholder="{{ (App\Models\User::SUSPENSION_REASONS[$susReason] ?? App\Models\User::SUSPENSION_REASONS['other'])[3] }}" class="input h-auto resize-y rounded-[14px] py-3 leading-normal"></textarea>
                                            <span id="um-sus-nh" class="text-[12.5px] leading-[1.45] text-ink3">Goes in the email. Be specific: which open plays, what happened, what to do next.</span>
                                        </label>
                                        @if ($susError)
                                            <p role="alert" class="m-0 text-sm font-semibold text-hot">{{ $susError }}</p>
                                        @endif
                                        <div class="flex items-start gap-2.5 rounded-[14px] border border-line bg-bg px-3.5 py-3">
                                            <x-icon name="mail" :size="18" class="mt-px shrink-0 text-ink2" />
                                            <span class="text-[13px] leading-[1.45] text-ink2">We'll email {{ $managed->email }} the reason{{ $lift ? ', the date it lifts' : '' }}{{ trim($susNote) !== '' ? ', your note' : '' }} and how to ask for another look.</span>
                                        </div>
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button type="button" wire:click="$set('suspending', false)" class="{{ $btn }} border-line2 text-ink hover:bg-surface">Cancel</button>
                                            <button type="button" wire:click="suspend" aria-disabled="{{ $off ? 'true' : 'false' }}" @class([$btn, 'border-0 font-extrabold', 'bg-hot text-bg hover:brightness-96' => ! $off, 'bg-surface text-ink3' => $off])>{{ $lift ? 'Suspend for '.$susLength.' days' : 'Suspend account' }}</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="{{ $row }}">
                                        <div class="{{ $text }}"><span class="text-[15px] font-bold">Suspend account</span><span class="text-[13px] leading-[1.45] text-ink2">Pick a reason and how long. They can't log in or join open plays until it lifts, and we email them why.</span></div>
                                        <button type="button" wire:click="startSuspend" class="{{ $btn }} border-hot-line bg-hot-soft text-hot hover:brightness-96">Suspend…</button>
                                    </div>
                                @endif
                                @if ($confirmDelete)
                                    <div role="alert" class="flex animate-fade flex-col gap-3 bg-hot-soft p-4">
                                        <span class="font-extrabold text-hot">Delete {{ $managed->name }}'s account?</span>
                                        <span class="text-sm leading-normal text-ink2">This can't be undone. They lose their login, profile, passkeys and looks. Logged games stay in other players' history as Former player.</span>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="$set('confirmDelete', false)" class="{{ $btn }} border-line2 text-ink hover:bg-surface">Keep account</button>
                                            <button type="button" wire:click="delete" class="{{ $btn }} border-hot bg-hot font-extrabold text-bg hover:brightness-96">Delete account</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="{{ $row }}">
                                        <div class="{{ $text }}"><span class="text-[15px] font-bold">Delete account</span><span class="text-[13px] leading-[1.45] text-ink2">Removes their login, profile and passkeys. Games they played stay in other players' history as Former player.</span></div>
                                        <button type="button" wire:click="askDelete" class="{{ $btn }} border-hot-line text-hot hover:bg-hot-soft">Delete</button>
                                    </div>
                                @endif
                            @endunless
                        </div>
                    </section>
                </div>
            </div>
        </div>
    @endif
</x-app-shell>
