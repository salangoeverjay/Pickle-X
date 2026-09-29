<x-auth-shell>
    <x-slot:aside class="w-[46%] bg-court text-white">
        <x-auth-court title="Tonight's queue is already moving." text="Log in to see when you're up next, or to run the courts for your club." />
    </x-slot:aside>

    <x-auth-court :strip="true" />

    <div class="flex flex-1 items-center justify-center px-4 pt-5 pb-8 lg:px-12 lg:pt-6 lg:pb-14">
        <div class="flex w-full max-w-105 flex-col gap-4.5">
            <form wire:submit="login" novalidate class="flex flex-col gap-4.5">
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
                    <div class="flex justify-end">
                        <a href="{{ route('password.request') }}" x-bind:href="@js(route('password.request')) + ($wire.email ? '?email=' + encodeURIComponent($wire.email.trim()) : '')" wire:navigate class="inline-flex h-8 items-center px-0.5 text-[13px] font-bold text-ink underline decoration-line2 underline-offset-3">Forgot password?</a>
                    </div>
                </div>

                @if ($errors->any())
                    <x-alert>{{ $errors->first() }}</x-alert>
                @endif

                <button type="submit" class="h-13.5 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-45" wire:loading.attr="disabled">Log in</button>
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
