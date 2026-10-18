@php
    $card = 'flex scroll-mt-5 flex-col rounded-3xl bg-surface p-4.5 lg:p-6';
    $h2 = 'm-0 font-display text-[21px] font-bold tracking-[-0.02em]';
    $lede = 'mt-1 mb-0 text-sm text-ink2';
    $primary = 'h-12 cursor-pointer rounded-full bg-ink px-5.5 text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97';
    $ghost = 'cursor-pointer rounded-full border-[1.5px] border-line2 font-bold text-ink transition hover:brightness-96 active:scale-97';
    $firstError = fn (array $keys) => collect($keys)->map(fn ($k) => $errors->first($k))->filter()->first();
@endphp

<x-app-shell>
    <x-page>
        <x-page-header :eyebrow="$user->roleLabel().' account'" title="Account settings" description="Profile, password, notifications and looks." />

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

                    <x-settings.avatar-picker :user="$user" :picker="$picker" />

                    <x-settings.look-picker :user="$user" :tab="$lookTab" :col="$lookCol" :cats="$lookCat" :saved="$lookSaved" />

                    <div class="grid gap-3.5 lg:grid-cols-2">
                        <x-field label="Full name" id="sf-name" autocomplete="name" wire:model="name" />
                        @unless ($user->isAdmin())
                            <div id="sf-dname-f" x-data class="flex scroll-mt-5 flex-col gap-2">
                                <div class="flex items-baseline justify-between gap-2">
                                    <label for="sf-dname" class="text-[13px] font-bold text-ink2">Display name <span class="font-medium text-ink3">(optional)</span></label>
                                    <span aria-hidden="true" class="font-mono text-xs" :class="($wire.displayName || '').trim().length > 20 ? 'text-hot' : 'text-ink3'" x-text="($wire.displayName || '').trim().length + '/20'"></span>
                                </div>
                                <input id="sf-dname" type="text" autocomplete="nickname" maxlength="24" placeholder="A nickname, e.g. Kaiser" wire:model="displayName" aria-describedby="sf-dname-hint" class="input">
                                <span id="sf-dname-hint" class="text-xs text-ink3">Up to 20 characters.</span>
                            </div>
                        @endunless
                    </div>

                    @unless ($user->isAdmin())
                        @include('livewire.partials.display-name', ['user' => $user])
                    @endunless

                    <div class="grid gap-3.5 lg:grid-cols-2">
                        <x-field label="Email" id="sf-email" type="email" autocomplete="email" wire:model="email" />
                        <div class="flex flex-col gap-2">
                            <label for="sf-phone" class="text-[13px] font-bold text-ink2">Mobile number <span class="font-medium text-ink3">(optional)</span></label>
                            <input id="sf-phone" type="tel" autocomplete="tel" placeholder="+63 9XX XXX XXXX" wire:model="phone" aria-describedby="sf-phone-hint" class="input">
                            <span id="sf-phone-hint" class="text-xs/[1.4] text-ink3">Only organizers of open plays you join can see it. PickleX never sends texts.</span>
                        </div>
                        @if ($homeClubEditable)
                            <x-field label="Home club" id="sf-club" placeholder="The club you play with most" wire:model="club" />
                        @elseif ($user->club && ! $user->isAdmin())
                            <div class="flex flex-col gap-2">
                                <label for="sf-org" class="text-[13px] font-bold text-ink2">Club</label>
                                <input id="sf-org" type="text" value="{{ $user->club }}" disabled aria-describedby="sf-org-hint" class="input border-dashed border-line2 bg-surface2 font-semibold text-ink2">
                                <span id="sf-org-hint" class="text-xs text-ink3">Club name changes go through admin review.</span>
                            </div>
                        @endif
                    </div>

                    @if ($message = $firstError(['name', 'displayName', 'email', 'phone', 'club']))
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

                {{-- Passkeys (resources/js/passkeys.js): log in with the device's fingerprint, face or screen lock. --}}
                <section id="set-passkeys" aria-labelledby="set-passkeys-h" x-data="passkeySettings({{ $user->id }}, @js($passkeys->pluck('id')))" class="{{ $card }} border border-line gap-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-[1_1_320px]">
                            <h2 id="set-passkeys-h" class="{{ $h2 }}">Passkeys</h2>
                            <p class="{{ $lede }}">Log in with your fingerprint, face or screen lock instead of a password. Passkeys can't be phished.</p>
                        </div>
                        <button type="button" x-on:click="add()" class="{{ $ghost }} inline-flex h-11 items-center gap-2 px-4.5 text-sm"><x-icon name="plus" :size="16" :stroke="2.4" /><span x-text="here.length ? 'Replace on this device' : 'Add a passkey'">Add a passkey</span></button>
                    </div>

                    @if ($passkeys->isNotEmpty())
                        <ul aria-label="Your passkeys" class="m-0 list-none divide-y divide-line overflow-hidden rounded-[18px] border border-line p-0">
                            @foreach ($passkeys as $pk)
                                <li wire:key="pk-{{ $pk->id }}" class="flex items-center gap-3.5 px-4 py-3.5">
                                    <span aria-hidden="true" class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-surface2 text-ink2"><x-icon :name="Str::contains($pk->name, ['iPhone', 'iOS', 'Android', 'iPad']) ? 'phone' : 'laptop'" :size="20" :stroke="1.9" /></span>
                                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <div class="flex min-w-0 flex-wrap items-center gap-2"><span class="truncate font-bold">{{ $pk->name }}</span><span x-show="isHere({{ $pk->id }})" x-cloak class="inline-flex h-5.5 shrink-0 items-center rounded-full bg-lime px-2 text-[11px] font-extrabold text-on-lime">This device</span></div>
                                        <span class="text-[13px] text-ink3">{{ $pk->created_at->isToday() ? 'Added today' : 'Added '.$pk->created_at->format('M j') }} · {{ $pk->last_used_at ? 'Last used '.($pk->last_used_at->isToday() ? 'today' : $pk->last_used_at->format('M j')) : 'Not used yet' }}</span>
                                    </div>
                                    <button type="button" wire:click="$set('removing', {{ $pk->id }})" aria-label="Remove the passkey for {{ $pk->name }}" class="{{ $ghost }} h-10 shrink-0 px-3.5 text-[13px] text-hot">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                        <p x-show="! here.length" x-cloak class="m-0 text-[13px] leading-[1.45] text-ink2">No passkey on this device yet. Add one to skip your password.</p>
                    @else
                        <div class="flex items-center gap-3 rounded-[18px] border-[1.5px] border-dashed border-line2 px-4 py-3.5">
                            <span aria-hidden="true" class="text-ink3"><x-icon name="passkey" :size="22" :stroke="1.9" /></span>
                            <span class="text-sm leading-[1.45] text-ink2">No passkeys yet. Add one to log in without a password.</span>
                        </div>
                    @endif

                    @if ($rememberedUntil)
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-[18px] bg-surface2 px-4 py-3">
                            <x-icon name="clock" :size="18" class="text-ink2" />
                            <span class="min-w-0 flex-[1_1_240px] text-sm text-ink2">Remember me is on. This device keeps you logged in until {{ $rememberedUntil }}.</span>
                            <button type="button" wire:click="forgetDevice" class="{{ $ghost }} h-10 px-3.5 text-[13px]">Forget this device</button>
                        </div>
                    @endif

                    {{-- Waiting on the device's prompt to create the passkey. --}}
                    <template x-teleport="body">
                        <div x-show="step === 'wait'" x-cloak class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
                            <div class="absolute inset-0 animate-fade bg-[rgba(18,23,15,.45)] dark:bg-black/62"></div>
                            <div role="dialog" aria-modal="true" aria-labelledby="pka-title" aria-describedby="pka-desc" class="relative flex w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-115 md:max-w-full md:rounded-[28px]">
                                <div class="flex flex-col items-center gap-4.5 px-6 pt-7 pb-5 text-center">
                                    <span aria-hidden="true" class="px-pk-wait flex size-18 items-center justify-center rounded-full bg-lime-soft text-lime-text"><x-icon name="passkey" :size="34" :stroke="1.8" /></span>
                                    <div class="flex flex-col gap-1.5">
                                        <h2 id="pka-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">Create a passkey</h2>
                                        <p id="pka-desc" role="status" class="m-0 text-[15px] leading-normal text-ink2">Your device will ask for your fingerprint, face or screen lock to save a passkey for {{ $user->email }}.</p>
                                    </div>
                                </div>
                                <div class="flex border-t border-line px-5 py-4"><button type="button" x-on:click="step = null" class="h-12 flex-1 cursor-pointer rounded-full border-[1.5px] border-line2 text-[15px] font-bold text-ink transition hover:bg-surface2">Cancel</button></div>
                            </div>
                        </div>
                    </template>
                </section>

                {{-- Organizer defaults --}}
                @if ($mode === 'organizer')
                    <section id="set-club" aria-labelledby="set-club-h" class="{{ $card }} border border-line gap-4.5">
                        <div>
                            <h2 id="set-club-h" class="{{ $h2 }}">Club and session defaults</h2>
                            <p class="{{ $lede }}">New open plays for {{ $user->club }} start with these settings.</p>
                        </div>

                        <x-stepper label="Courts at your venue" model="courts" :value="$courts" :min="1" :max="12" class="max-w-55" />
                        <x-segmented label="Default race to" model="raceTo" :options="[11 => '11', 15 => '15', 21 => '21']" class="max-w-115" />
                        <x-segmented label="Default rotation" model="rotation" :options="['rotate' => 'Everyone rotates', 'winners' => 'Winners stay (2 max)']" :hints="['rotate' => 'Fewest games play first, and partners mix every game.', 'winners' => 'Winners stay on for up to 2 games; challengers come from the front of the line.']" class="max-w-115" />

                        <x-segmented label="Default warmup" model="warmup" :options="App\Models\OpenPlay::WARMUPS" :hints="[0 => 'Games start the moment a foursome goes on court.', 2 => $warm = 'New foursomes warm up before the game clock starts. Winners who stay on skip it.', 3 => $warm, 5 => $warm]" class="max-w-115" />

                        @if ($message = $firstError(['courts', 'raceTo', 'rotation', 'warmup']))
                            <x-alert>{{ $message }}</x-alert>
                        @endif

                        <div class="flex justify-end">
                            <button type="button" wire:click="saveDefaults" class="{{ $primary }}">Save defaults</button>
                        </div>
                    </section>
                @endif

                {{-- Organize open plays (players; same account, two modes) --}}
                @if ($mode === 'member')
                    <section id="set-host" aria-labelledby="set-host-h" class="{{ $card }} border border-line gap-4">
                        <div>
                            <h2 id="set-host-h" class="{{ $h2 }}">Organize open plays</h2>
                            <p class="{{ $lede }}">One account, two modes. Keep your player stats and run sessions for your club.</p>
                        </div>

                        @if ($user->isOrganizer())
                            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-lime bg-lime-soft p-3.5">
                                <span class="min-w-50 flex-1 text-sm font-semibold">You're an organizer for <strong>{{ $user->club }}</strong>.</span>
                                <a href="{{ route('organizer.plays') }}" wire:navigate class="inline-flex h-11 items-center rounded-full bg-ink px-4.5 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">Switch to organizer mode</a>
                            </div>
                        @elseif ($user->organizer_status === 'pending')
                            <div class="flex items-start gap-3 rounded-2xl border border-hot-line bg-hot-soft p-3.5">
                                <span class="mt-1 size-3 shrink-0 animate-live rounded-full bg-lime outline-[1.5px] -outline-offset-[1.5px] outline-on-lime"></span>
                                <span class="text-sm leading-[1.45]">Your request for <strong>{{ $user->club }}</strong> is with a PickleX admin. Organizer tools appear here once it's approved.</span>
                            </div>
                        @else
                            @if ($user->organizer_status === 'declined')
                                <p class="m-0 text-sm font-semibold text-hot">Your last request for {{ $user->club }} wasn't approved. You can apply again below.</p>
                            @endif
                            <div class="grid gap-3.5 lg:grid-cols-2">
                                <x-field label="Club or group name" id="ap-club" placeholder="e.g. Panabo Dink Society" wire:model="applyClub" />
                                <x-field label="City" id="ap-city" placeholder="e.g. Panabo City" wire:model="applyCity" />
                            </div>
                            @if ($applyError)
                                <x-alert>{{ $applyError }}</x-alert>
                            @endif
                            <div class="flex justify-end">
                                <button type="button" wire:click="requestOrganizer" class="{{ $primary }}">Send organizer request</button>
                            </div>
                        @endif
                    </section>
                @endif

                {{-- Notifications --}}
                <section id="set-notify" aria-labelledby="set-notify-h" class="{{ $card }} border border-line">
                    <div class="mb-2.5">
                        <h2 id="set-notify-h" class="{{ $h2 }}">Notifications</h2>
                        <p class="{{ $lede }} leading-[1.45]">PickleX notifies you by email only, at {{ $user->email }}. No push notifications and no text messages. Changes save right away.</p>
                    </div>
                    @foreach ($notifications as $key => $row)
                        <x-settings.switch-row :key="$key" :row="$row" />
                    @endforeach
                    <p class="flex items-start gap-2 border-t border-line pt-3 text-[13px]/[1.45] text-ink3">
                        <x-icon name="mail" :size="14" :stroke="2" class="mt-0.5" />
                        <span>Account and security emails are always sent.</span>
                    </p>
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

                {{-- Privacy (player mode) --}}
                @if ($mode === 'member')
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

    {{-- Passkey dialogs sit outside x-page: its entrance transform would pin fixed children to it. --}}
    @if ($naming)
        <div class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <div class="absolute inset-0 animate-fade bg-[rgba(18,23,15,.45)] dark:bg-black/62"></div>
            <form wire:submit="namePasskey" role="dialog" aria-modal="true" aria-labelledby="pkn-title" aria-describedby="pkn-desc" x-trap.noscroll="true" class="relative flex w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-115 md:max-w-full md:rounded-[28px]">
                <div class="flex flex-col gap-4.5 px-6 pt-7 pb-5">
                    <span aria-hidden="true" class="flex size-14 items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="check" :size="26" :stroke="2.6" /></span>
                    <div class="flex flex-col gap-1.5">
                        <h2 id="pkn-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">Passkey saved</h2>
                        <p id="pkn-desc" class="m-0 text-[15px] leading-normal text-ink2">Name it so you can tell your devices apart.</p>
                    </div>
                    <x-field label="Passkey name" id="pkn-name" maxlength="40" autocomplete="off" wire:model="passkeyName" />
                </div>
                <div class="flex border-t border-line px-5 py-4"><button type="submit" class="{{ $primary }} flex-1">Done</button></div>
            </form>
        </div>
    @endif

    @if ($removing && ($pkGone = $passkeys->firstWhere('id', $removing)))
        <div x-data x-on:keydown.escape.window="$wire.set('removing', null)" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <button type="button" wire:click="$set('removing', null)" aria-label="Keep passkey" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
            <div role="alertdialog" aria-modal="true" aria-labelledby="pkd-title" aria-describedby="pkd-desc" x-trap.noscroll="true" class="relative flex w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-115 md:max-w-full md:rounded-[28px]">
                <div class="flex flex-col gap-4 px-6 pt-7 pb-5">
                    <span aria-hidden="true" class="flex size-14 items-center justify-center rounded-full bg-hot-soft text-hot"><x-icon name="passkey" :size="26" :stroke="1.9" /></span>
                    <div class="flex flex-col gap-1.5">
                        <h2 id="pkd-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">Remove this passkey?</h2>
                        <p id="pkd-desc" class="m-0 text-[15px] leading-normal text-ink2">You won't be able to log in with <strong class="text-ink">{{ $pkGone->name }}</strong> anymore. Your password still works.</p>
                    </div>
                    @if ($passkeys->count() === 1)
                        <p class="m-0 rounded-[14px] bg-surface2 px-3.5 py-3 text-sm text-ink2">It's your only passkey. You'll use your password until you add another.</p>
                    @endif
                </div>
                <div class="flex gap-2 border-t border-line px-5 py-4">
                    <button type="button" wire:click="$set('removing', null)" class="{{ $ghost }} h-12 flex-1 text-[15px]">Keep passkey</button>
                    <button type="button" wire:click="removePasskey" class="h-12 flex-1 cursor-pointer rounded-full bg-hot text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">Remove passkey</button>
                </div>
            </div>
        </div>
    @endif
</x-app-shell>
