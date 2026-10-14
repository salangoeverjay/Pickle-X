<x-auth-shell>
    <x-slot:aside class="w-[46%] bg-court text-white">
        <x-auth-court title="Tonight's queue is already moving." text="Log in to see when you're up next, or to run the courts for your club." />
    </x-slot:aside>

    <x-auth-court :strip="true" />

    <div class="flex flex-1 items-center justify-center px-4 pt-5 pb-8 lg:px-12 lg:pt-6 lg:pb-14">
        <div class="flex w-full max-w-105 flex-col gap-4.5">
            <form wire:submit="login" novalidate class="flex flex-col gap-4.5">
                @include('livewire.partials.join-banner', ['kicker' => 'Log in to join'])
                <div class="flex flex-col gap-2">
                    <h1 class="m-0 font-display text-4xl leading-none font-extrabold tracking-[-0.04em] lg:text-[46px]">Welcome back</h1>
                    <p class="m-0 text-[15px] text-ink2">Log in to join the queue, check your stats, or run your courts.</p>
                </div>

                <x-field label="Email" id="login-email" type="email" autocomplete="email" placeholder="you@example.com" wire:model="email" />

                <div x-data="{ show: false }" class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <label for="login-password" class="text-[13px] font-bold text-ink2">Password</label>
                        <button type="button" x-on:click="show = ! show" x-text="show ? 'Hide' : 'Show'" class="h-8 cursor-pointer px-1 text-[13px] font-semibold text-ink2 underline underline-offset-3">Show</button>
                    </div>
                    <input id="login-password" type="password" x-bind:type="show ? 'text' : 'password'" autocomplete="current-password" placeholder="Your password" wire:model="password" class="input">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                        <button type="button" role="checkbox" x-bind:aria-checked="$wire.remember" x-on:click="$wire.remember = ! $wire.remember" class="inline-flex min-h-11 cursor-pointer items-center gap-2.5 pr-1 text-left text-ink">
                            <span aria-hidden="true" x-bind:class="$wire.remember ? 'border-lime bg-lime' : 'border-line2 bg-surface'" class="box-border flex size-5.5 shrink-0 items-center justify-center rounded-[7px] border-[1.5px] text-on-lime transition-colors">
                                <x-icon name="check" :size="14" :stroke="3" x-show="$wire.remember" />
                            </span>
                            <span class="flex flex-col gap-px leading-tight">
                                <span class="text-sm font-bold">Remember me</span>
                                <span class="text-xs text-ink3">Stay logged in on this device for 30 days</span>
                            </span>
                        </button>
                        <a href="{{ route('password.request') }}" x-bind:href="@js(route('password.request')) + ($wire.email ? '?email=' + encodeURIComponent($wire.email.trim()) : '')" wire:navigate class="inline-flex h-8 items-center px-0.5 text-[13px] font-bold text-ink underline decoration-line2 underline-offset-3">Forgot password?</a>
                    </div>
                </div>

                @if ($errors->any())
                    <x-alert>{{ $errors->first() }}</x-alert>
                @endif

                <button type="submit" class="h-13.5 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-45" wire:loading.attr="disabled">Log in</button>

                {{-- Log in with a passkey (resources/js/passkeys.js): the device's fingerprint, face or screen lock replaces the password. --}}
                <div x-data="passkeyLogin" data-options="{{ route('passkeys.options') }}" data-login="{{ route('passkeys.login') }}" x-on:keydown.escape.window="open = false">
                    <button type="button" x-on:click="start()" class="flex h-13.5 w-full cursor-pointer items-center justify-center gap-2.5 rounded-full border-[1.5px] border-line2 bg-surface text-base font-bold text-ink transition hover:brightness-96 active:scale-97">
                        <x-icon name="passkey" :size="20" :stroke="1.9" />Log in with a passkey
                    </button>
                    <template x-if="open">
                        <div class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
                            <button type="button" x-on:click="open = false" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
                            <div role="dialog" aria-modal="true" aria-labelledby="pk-title" aria-describedby="pk-desc" class="relative flex w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-115 md:max-w-full md:rounded-[28px]">
                                <div class="flex flex-col items-center gap-4.5 px-6 pt-7 pb-5 text-center">
                                    <span x-bind:class="error || 'px-pk-wait'" aria-hidden="true" class="flex size-18 items-center justify-center rounded-full bg-lime-soft text-lime-text"><x-icon name="passkey" :size="34" :stroke="1.8" /></span>
                                    <div class="flex flex-col gap-1.5">
                                        <h2 id="pk-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]" x-text="error ? 'Log in with a passkey' : 'Confirm it\'s you'">Confirm it's you</h2>
                                        <p id="pk-desc" role="status" class="m-0 text-[15px] leading-normal text-ink2" x-show="! error">Pick your account if your device asks, then use your fingerprint, face or screen lock. No password needed.</p>
                                    </div>
                                    <template x-if="error"><div role="alert" class="w-full rounded-[14px] border border-hot-line bg-hot-soft px-3.5 py-3 text-left text-sm leading-[1.45] font-semibold text-hot" x-text="error"></div></template>
                                    <p class="m-0 text-[13px] leading-[1.45] text-ink3">No passkey here yet? Log in with your password, then add one in Account settings › Passkeys.</p>
                                </div>
                                <div class="flex gap-2 border-t border-line px-5 py-4">
                                    <template x-if="error"><button type="button" x-on:click="start()" class="h-12 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">Try again</button></template>
                                    <button type="button" x-on:click="open = false" class="h-12 flex-1 cursor-pointer rounded-full border-[1.5px] border-line2 text-[15px] font-bold text-ink transition hover:bg-surface2 active:scale-97">Use my password instead</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </form>

            @if (config('app.demo'))
                <div class="mt-1 flex items-center gap-3">
                    <span class="h-px flex-1 bg-line"></span>
                    <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Or try a demo account</span>
                    <span class="h-px flex-1 bg-line"></span>
                </div>
                <form method="POST" action="{{ route('demo-login') }}" x-data x-on:submit="$dispatch('px-loading', { kind: 'login' })" class="grid grid-cols-3 gap-2">
                    @csrf
                    @foreach (['member' => ['Member', 'users'], 'organizer' => ['Organizer', 'court'], 'admin' => ['Admin', 'shield']] as $role => [$label, $icon])
                        <button type="submit" name="role" value="{{ $role }}" class="flex h-19 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border-[1.5px] border-line bg-surface text-[13px] font-bold text-ink transition hover:brightness-96 active:scale-97">
                            <x-icon :name="$icon" :stroke="1.9" />{{ $label }}
                        </button>
                    @endforeach
                </form>
            @endif

            <p class="mt-1 mb-0 text-center text-sm text-ink2">
                New to PickleX?
                <a href="{{ route('signup') }}" wire:navigate class="font-bold text-ink underline decoration-lime decoration-3 underline-offset-4">Create an account</a>
            </p>
        </div>
    </div>
</x-auth-shell>
