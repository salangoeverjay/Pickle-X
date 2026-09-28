@php
    $card = 'flex scroll-mt-5 flex-col rounded-3xl bg-surface p-4.5 lg:p-6';
    $h2 = 'm-0 font-display text-[21px] font-bold tracking-[-0.02em]';
    $lede = 'mt-1 mb-0 text-sm text-ink2';
    $primary = 'h-12 cursor-pointer rounded-full bg-ink px-5.5 text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97';
    $ghost = 'cursor-pointer rounded-full border-[1.5px] border-line2 font-bold text-ink transition hover:brightness-96 active:scale-97';
    $firstError = fn (array $keys) => collect($keys)->map(fn ($k) => $errors->first($k))->filter()->first();
@endphp

<x-app-shell>
    <x-page class="max-w-280">
        <x-page-header :eyebrow="$user->roleLabel().' account'" title="Account settings" description="Update your profile, password and notifications, and choose how PickleX looks." />

        <div class="grid items-start gap-7 lg:grid-cols-[200px_minmax(0,1fr)]">
            <nav aria-label="Settings sections" class="sticky top-5 hidden flex-col gap-0.5 lg:flex">
                @foreach ($sections as [$id, $label, $icon])
                    <a href="#set-{{ $id }}" class="flex h-11 items-center gap-2.5 rounded-xl px-3 text-sm font-semibold text-ink2 transition-colors hover:bg-surface2 hover:text-ink">
                        <x-icon :name="$icon" :size="18" :stroke="1.9" />{{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="flex min-w-0 flex-col gap-4">
                {{-- Profile --}}
                <section id="set-profile" aria-labelledby="set-profile-h" class="{{ $card }} border border-line gap-4.5">
                    <div>
                        <h2 id="set-profile-h" class="{{ $h2 }}">Profile</h2>
                        <p class="{{ $lede }}">How you appear in queues, on courts and in rankings.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4.5">
                        {{-- Initials follow the name field as you type. --}}
                        <span x-data x-text="(() => { const p = ($wire.name || '').trim().split(/\s+/); return ((p[0] || '').charAt(0) + (p[1] || '').charAt(0)).toUpperCase() })()"
                              class="flex size-19 shrink-0 items-center justify-center rounded-full text-[25px] font-extrabold text-on-lime transition-colors duration-250" style="background: {{ $avatar }}">{{ initials($name) }}</span>
                        <div class="flex min-w-0 flex-col gap-2">
                            <span id="av-label" class="text-[13px] font-bold text-ink2">Avatar color</span>
                            <div role="radiogroup" aria-labelledby="av-label" class="flex flex-wrap gap-2">
                                @foreach (\App\Models\User::AVATARS as $hex => $label)
                                    <button type="button" role="radio" aria-checked="{{ $avatar === $hex ? 'true' : 'false' }}" aria-label="{{ $label }}" wire:click="$set('avatar', '{{ $hex }}')"
                                            @class(['size-10 cursor-pointer rounded-full border-3 p-0 shadow-[inset_0_0_0_2px_var(--color-surface)] transition hover:brightness-96 active:scale-97', 'border-ink' => $avatar === $hex, 'border-transparent' => $avatar !== $hex])
                                            style="background: {{ $hex }}"></button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3.5 lg:grid-cols-2">
                        <x-field label="Full name" id="sf-name" autocomplete="name" wire:model="name" />
                        <x-field label="Email" id="sf-email" type="email" autocomplete="email" wire:model="email" />
                        <div class="flex flex-col gap-2">
                            <label for="sf-phone" class="text-[13px] font-bold text-ink2">Mobile number <span class="font-medium text-ink3">(for queue alerts)</span></label>
                            <input id="sf-phone" type="tel" autocomplete="tel" placeholder="+63 9XX XXX XXXX" wire:model="phone" class="input">
                        </div>
                        @if ($user->isMember())
                            <x-field label="Home club" id="sf-club" placeholder="The club you play with most" wire:model="club" />
                        @elseif ($user->isOrganizer())
                            <div class="flex flex-col gap-2">
                                <label for="sf-org" class="text-[13px] font-bold text-ink2">Club</label>
                                <input id="sf-org" type="text" value="{{ $user->club }}" disabled aria-describedby="sf-org-hint" class="input border-dashed border-line2 bg-surface2 font-semibold text-ink2">
                                <span id="sf-org-hint" class="text-xs text-ink3">Club name changes go through admin review.</span>
                            </div>
                        @endif
                    </div>

                    @if ($message = $firstError(['name', 'email', 'phone', 'club', 'avatar']))
                        <x-alert>{{ $message }}</x-alert>
                    @endif

                    <div class="flex justify-end">
                        <button type="button" wire:click="saveProfile" class="{{ $primary }}">Save profile</button>
                    </div>
                </section>

                {{-- Password --}}
                <section id="set-security" aria-labelledby="set-security-h" class="{{ $card }} border border-line gap-4.5">
                    <div>
                        <h2 id="set-security-h" class="{{ $h2 }}">Password</h2>
                        <p class="{{ $lede }}">Use at least 8 characters.</p>
                    </div>

                    <div class="grid gap-3.5 lg:grid-cols-3">
                        <x-field label="Current password" id="pw-cur" type="password" autocomplete="current-password" wire:model="currentPassword" />
                        <x-field label="New password" id="pw-new" type="password" autocomplete="new-password" wire:model="password" />
                        <x-field label="Confirm new password" id="pw-conf" type="password" autocomplete="new-password" wire:model="password_confirmation" />
                    </div>

                    @if ($message = $firstError(['currentPassword', 'password']))
                        <x-alert>{{ $message }}</x-alert>
                    @endif

                    <div class="flex justify-end">
                        <button type="button" wire:click="savePassword" class="{{ $ghost }} h-12 px-5.5 text-[15px]">Update password</button>
                    </div>
                </section>

                {{-- Organizer defaults --}}
                @if ($user->isOrganizer())
                    <section id="set-club" aria-labelledby="set-club-h" class="{{ $card }} border border-line gap-4.5">
                        <div>
                            <h2 id="set-club-h" class="{{ $h2 }}">Club and session defaults</h2>
                            <p class="{{ $lede }}">New open plays for {{ $user->club }} start with these settings.</p>
                        </div>

                        <x-stepper label="Courts at your venue" model="courts" :value="$courts" :min="1" :max="12" class="max-w-55" />
                        <x-segmented label="Default race to" model="raceTo" :options="[11 => '11', 15 => '15', 21 => '21']" class="max-w-115" />
                        <x-segmented label="Default rotation" model="rotation" :options="['rotate' => 'Everyone rotates', 'winners' => 'Winners stay (2 max)']" class="max-w-115" />

                        @if ($message = $firstError(['courts', 'raceTo', 'rotation']))
                            <x-alert>{{ $message }}</x-alert>
                        @endif

                        <div class="flex justify-end">
                            <button type="button" wire:click="saveDefaults" class="{{ $primary }}">Save defaults</button>
                        </div>
                    </section>
                @endif

                {{-- Notifications --}}
                <section id="set-notify" aria-labelledby="set-notify-h" class="{{ $card }} border border-line">
                    <div class="mb-2.5">
                        <h2 id="set-notify-h" class="{{ $h2 }}">Notifications</h2>
                        <p class="{{ $lede }}">Sent by push and SMS. Changes save right away.</p>
                    </div>
                    @foreach ($notifications as $key => $row)
                        <x-settings.switch-row :key="$key" :row="$row" />
                    @endforeach
                </section>

                {{-- Appearance (client-side only, like every theme control) --}}
                <section id="set-look" aria-labelledby="set-look-h" class="{{ $card }} border border-line gap-4">
                    <div>
                        <h2 id="set-look-h" class="{{ $h2 }}">Appearance</h2>
                        <p class="{{ $lede }}">Dark mode is easier on the eyes at night sessions.</p>
                    </div>
                    <div role="radiogroup" aria-label="Theme" wire:ignore x-data="{ theme: document.documentElement.dataset.theme }" class="grid max-w-115 grid-cols-2 gap-2.5">
                        @foreach ([
                            'light' => ['Light', '#F3F2EA', '#DEDCD0', '#12170F', '#C8C6B8'],
                            'dark' => ['Dark', '#0A0E0B', '#26312A', '#EDF2E3', '#36433B'],
                        ] as $value => [$label, $bg, $line, $ink, $muted])
                            <button type="button" role="radio" onclick="setTheme('{{ $value }}')" x-on:click="theme = '{{ $value }}'"
                                    x-bind:aria-checked="theme === '{{ $value }}'" x-bind:class="theme === '{{ $value }}' && '!border-ink'"
                                    class="flex cursor-pointer flex-col gap-2.5 rounded-[18px] border-2 border-line bg-surface p-2.5 text-left text-ink transition hover:brightness-96 active:scale-97">
                                <span aria-hidden="true" class="flex h-16 flex-col gap-1.25 rounded-xl border p-2.5" style="background: {{ $bg }}; border-color: {{ $line }}">
                                    <span class="h-2 w-[60%] rounded-sm" style="background: {{ $ink }}"></span>
                                    <span class="h-2 w-[40%] rounded-sm" style="background: {{ $muted }}"></span>
                                    <span class="mt-auto h-3.5 w-8.5 rounded-full bg-[#D3F347]"></span>
                                </span>
                                <span class="flex items-center gap-2 px-1 pb-0.5 text-sm font-bold">
                                    {{ $label }}
                                    <x-icon name="check" :size="16" :stroke="2.6" x-show="theme === '{{ $value }}'" x-cloak />
                                </span>
                            </button>
                        @endforeach
                    </div>
                </section>

                {{-- Privacy (members) --}}
                @if ($user->isMember())
                    <section id="set-privacy" aria-labelledby="set-privacy-h" class="{{ $card }} border border-line">
                        <div class="mb-2.5">
                            <h2 id="set-privacy-h" class="{{ $h2 }}">Privacy</h2>
                            <p class="{{ $lede }}">Organizers still see your name in the queue so they can call you to court.</p>
                        </div>
                        @foreach ($privacy as $key => $row)
                            <x-settings.switch-row :key="$key" :row="$row" />
                        @endforeach
                    </section>
                @endif

                {{-- Account --}}
                <section id="set-danger" aria-labelledby="set-danger-h" class="{{ $card }} gap-1 border-[1.5px] border-hot-line">
                    <h2 id="set-danger-h" class="{{ $h2 }} mb-2">Account</h2>

                    <div class="flex flex-wrap items-center gap-3 border-t border-line py-3">
                        <div class="flex min-w-50 flex-1 flex-col">
                            <span class="text-[15px] font-semibold">Log out</span>
                            <span class="text-[13px] text-ink3">You'll need your email and password to get back in.</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="{{ $ghost }} h-11 px-4.5 text-sm">Log out</button>
                        </form>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-line py-3">
                        <div class="flex min-w-50 flex-1 flex-col">
                            <span class="text-[15px] font-semibold">Delete account</span>
                            <span class="text-[13px] text-ink3">{{ $deleteHint }}</span>
                        </div>
                        @unless ($confirmingDelete)
                            <button type="button" wire:click="$set('confirmingDelete', true)" class="h-11 cursor-pointer rounded-full border-[1.5px] border-hot-line bg-hot-soft px-4.5 text-sm font-bold text-hot transition hover:brightness-96 active:scale-97">Delete account</button>
                        @endunless
                    </div>

                    @if ($confirmingDelete)
                        <div role="alert" class="flex animate-snap flex-wrap items-center gap-2.5 rounded-2xl border border-hot-line bg-hot-soft p-3.5">
                            <span class="min-w-50 flex-1 text-sm font-bold text-hot">Delete {{ $user->name }}'s account? This can't be undone.</span>
                            <button type="button" wire:click="$set('confirmingDelete', false)" class="{{ $ghost }} h-11 bg-surface px-4 text-sm">Keep my account</button>
                            <button type="button" wire:click="deleteAccount" class="h-11 cursor-pointer rounded-full bg-hot px-4 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">Yes, delete it</button>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </x-page>
</x-app-shell>
