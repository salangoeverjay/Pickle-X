@php($players = [['BT', '#E2D2FF'], ['ML', '#FFEFA3'], ['RT', '#CDEBB0'], ['KS', '#BCD9F7']])

<x-auth-shell>
    <x-slot:aside class="w-[46%] bg-court text-white">
        <a href="{{ route('home') }}" wire:navigate aria-label="PickleX home" class="relative self-start">
            <x-logo class="text-[28px]" />
        </a>

        <div class="relative h-75 transform-[perspective(1200px)_rotateX(24deg)_rotateZ(-6deg)]">
            <x-court class="absolute inset-0 rounded-[22px] shadow-[0_40px_60px_-30px_rgba(0,0,0,.6)]" player-class="size-10.5 text-[13px]" :players="$players" />
        </div>

        <div class="relative flex flex-col gap-3.5">
            <span class="inline-flex h-7.5 items-center gap-2 self-start rounded-full bg-black/22 px-3 font-mono text-[11px] tracking-widest">
                <span class="size-1.75 animate-live rounded-full bg-lime"></span>COURT 3 · <x-live-timer />
            </span>
            <h2 class="m-0 font-display text-[46px] leading-[.98] font-extrabold tracking-[-0.04em] text-balance">Tonight's queue is already moving.</h2>
            <p class="m-0 max-w-110 text-base leading-[1.55] text-white/90">Log in to see when you're up next, or to run the courts for your club.</p>
        </div>
    </x-slot:aside>

    {{-- Mobile court strip --}}
    <div class="relative mx-4 mt-1 h-37.5 shrink-0 lg:hidden">
        <x-court class="absolute inset-0 rounded-[22px]" player-class="size-7.5 text-[10px]" :players="$players" />
        <span class="absolute top-2.5 left-2.5 inline-flex h-6 items-center gap-1.5 rounded-full bg-black/35 px-2.25 font-mono text-[10px] tracking-widest text-white">
            <span class="size-1.5 animate-live rounded-full bg-lime"></span>COURT 3 · <x-live-timer />
        </span>
    </div>

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
                <form method="POST" action="{{ route('demo-login') }}" class="grid grid-cols-3 gap-2">
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
