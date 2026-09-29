{{--
    Signed-in layout: sidebar on desktop; top bar, account menu and bottom nav on mobile.
    Wrap each app screen's view in <x-app-shell>.
--}}
@php
    $user = auth()->user();
    $mode = $user->mode();
    // One account, two modes: approved organizers switch between organizer and player.
    $other = $user->isOrganizer() ? ($mode === 'organizer' ? ['member.dashboard', 'Switch to player mode'] : ['organizer.plays', 'Switch to organizer mode']) : null;
    $nav = match ($mode) {
        'organizer' => [
            ['organizer.plays', 'Open plays', 'calendar', null],
            ['organizer.board', 'Court board', 'court', $user->openPlays()->status('live')->exists() ? 'LIVE' : null],
        ],
        'admin' => [
            ['admin.requests', 'Requests', 'shield', \App\Models\User::where('organizer_status', 'pending')->count() ?: null],
            ['admin.developers', 'Developers', 'code', null],
            ['admin.sponsors', 'Sponsors', 'heart', null],
        ],
        default => [
            ['member.dashboard', 'Home', 'home', null],
            ['member.plays', 'Open plays', 'calendar', null],
            ['member.played-with', 'Played with', 'users', null],
            ['member.rankings', 'Rankings', 'trophy', null],
        ],
    };
@endphp

<div class="flex min-h-dvh flex-col lg:flex-row">
    {{-- Desktop sidebar --}}
    <aside class="sticky top-0 hidden h-dvh w-65 shrink-0 flex-col gap-5 border-r border-line bg-surface px-4 pt-6.5 pb-4.5 lg:flex">
        <a href="{{ route($user->homeRoute()) }}" wire:navigate aria-label="PickleX home" class="self-start px-2 text-ink">
            <x-logo class="text-[27px]" />
        </a>
        @if ($other)
            <div class="mx-1 flex flex-col gap-1.5">
                <span id="mode-label" class="px-1 font-mono text-[10px] tracking-[.12em] text-ink3 uppercase">One account · two modes</span>
                <div role="radiogroup" aria-labelledby="mode-label" class="grid grid-cols-2 rounded-[14px] border border-line bg-surface2 p-1">
                    @foreach (['organizer' => ['organizer.plays', 'Organizer'], 'member' => ['member.dashboard', 'Player']] as $m => [$route, $label])
                        <a href="{{ route($route) }}" wire:navigate role="radio" aria-checked="{{ $mode === $m ? 'true' : 'false' }}" @class([
                            'flex h-9.5 items-center justify-center rounded-[10px] text-[13px] font-bold transition-colors',
                            'bg-ink text-bg' => $mode === $m,
                            'text-ink2 hover:text-ink' => $mode !== $m,
                        ])>{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <span class="mx-2 inline-flex h-6.5 items-center self-start rounded-full border border-line bg-surface2 px-2.5 font-mono text-[11px] tracking-[.12em] text-ink2 uppercase">{{ $user->roleLabel() }}</span>
        @endif

        <nav aria-label="Main" class="flex flex-col gap-1">
            @foreach ($nav as [$route, $label, $icon, $badge])
                @php($on = request()->routeIs($route))
                <a href="{{ route($route) }}" wire:navigate @if ($on) aria-current="page" @endif @class([
                    'flex h-12 items-center gap-3 rounded-[14px] px-3 text-[15px] font-bold transition active:scale-97',
                    'bg-lime text-on-lime' => $on,
                    'text-ink2 hover:bg-surface2 hover:text-ink' => ! $on,
                ])>
                    <x-icon :name="$icon" />
                    <span class="flex-1">{{ $label }}</span>
                    @if ($badge)
                        <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-hot px-1.75 font-mono text-xs font-semibold text-white">{{ $badge }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        @if ($mode === 'organizer')
            <a href="{{ route('organizer.plays', ['create' => 1]) }}" wire:navigate class="flex h-12 items-center gap-2.5 rounded-[14px] border-[1.5px] border-dashed border-line2 px-3.5 text-sm font-bold text-ink transition hover:bg-surface2 active:scale-97">
                <x-icon name="plus" />Create open play
            </a>
        @endif

        <div class="flex-1"></div>

        @php($on = request()->routeIs('settings'))
        <a href="{{ route('about') }}" wire:navigate class="flex h-12 items-center gap-3 rounded-[14px] px-3 text-[15px] font-bold text-ink2 transition hover:bg-surface2 hover:text-ink">
            <x-icon name="love" /><span class="flex-1">Meet the developer</span>
        </a>

        <a href="{{ route('settings') }}" wire:navigate @if ($on) aria-current="page" @endif @class([
            'flex h-12 items-center gap-3 rounded-[14px] px-3 text-[15px] font-bold transition active:scale-97',
            'bg-lime text-on-lime' => $on,
            'text-ink2 hover:bg-surface2 hover:text-ink' => ! $on,
        ])>
            <x-icon name="settings" /><span class="flex-1">Account settings</span>
        </a>

        <button type="button" onclick="setTheme()" x-data="{ dark: document.documentElement.dataset.theme === 'dark' }" x-on:click="dark = ! dark" role="switch" x-bind:aria-checked="dark" aria-label="Dark mode"
                class="flex h-12 cursor-pointer items-center gap-3 rounded-[14px] px-3 text-left text-sm font-semibold text-ink2 transition hover:bg-surface2">
            <x-icon name="moon" class="dark:hidden" /><x-icon name="sun" class="hidden dark:block" />
            <span class="flex-1">Dark mode</span>
            <span class="relative h-6 w-10.5 rounded-full bg-line2 transition-colors dark:bg-lime">
                <span class="absolute top-0.75 left-0.75 size-4.5 rounded-full bg-white transition-[left] dark:left-5.25 dark:bg-on-lime"></span>
            </span>
        </button>

        <div class="flex items-center gap-2.5 rounded-2xl border border-line bg-surface2 p-2.5">
            <x-avatar :src="$user->avatarUrl()" :frame="$user->frameClass()" :name="$user->name" :color="$user->color()" class="size-9.5 text-[13px]" />
            <div class="flex min-w-0 flex-1 flex-col">
                <span class="truncate text-sm font-bold">{{ $user->name }}</span>
                <span class="truncate text-xs text-ink3">{{ $user->email }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" aria-label="Log out" class="flex size-9.5 cursor-pointer items-center justify-center rounded-xl text-ink2 transition hover:bg-surface active:scale-97"><x-icon name="logout" /></button>
            </form>
        </div>
    </aside>

    {{-- Mobile top bar --}}
    <header x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="sticky top-0 z-20 flex h-15 shrink-0 items-center justify-between border-b border-line bg-surface pr-2.5 pl-4 lg:hidden">
        <a href="{{ route($user->homeRoute()) }}" wire:navigate aria-label="PickleX home" class="text-ink"><x-logo class="text-2xl" /></a>
        <div class="flex items-center gap-1">
            @if ($other)
                <a href="{{ route($other[0]) }}" wire:navigate aria-label="{{ $other[1] }}" class="inline-flex h-10 items-center gap-1.25 rounded-full border border-line bg-surface2 px-2.5 font-mono text-[10px] tracking-widest text-ink uppercase">
                    {{ $user->roleLabel() }}<x-icon name="swap" :size="14" />
                </a>
            @else
                <span class="mr-0.5 inline-flex h-6 items-center rounded-full border border-line bg-surface2 px-2 font-mono text-[10px] tracking-widest text-ink2 uppercase">{{ $user->roleLabel() }}</span>
            @endif
            <x-theme-toggle class="border-0 bg-transparent" />
            <button type="button" x-on:click="open = ! open" aria-label="Account menu" x-bind:aria-expanded="open" class="flex size-11 cursor-pointer items-center justify-center rounded-full">
                <x-avatar :src="$user->avatarUrl()" :frame="$user->frameClass()" :name="$user->name" :color="$user->color()" class="size-8.5 text-xs" />
            </button>
        </div>

        <div x-show="open" x-cloak x-transition.opacity x-on:click.outside="open = false" class="absolute top-14.5 right-2.5 flex w-60 flex-col gap-1 rounded-[18px] border border-line bg-surface p-2 shadow-card">
            <div class="flex flex-col px-2.5 pt-2.5 pb-2">
                <span class="font-bold">{{ $user->name }}</span>
                <span class="text-[13px] text-ink3">{{ $user->email }}</span>
            </div>
            @if ($other)
                <a href="{{ route($other[0]) }}" wire:navigate class="flex h-11 items-center gap-2.5 rounded-xl px-2.5 text-sm font-bold text-ink transition hover:bg-surface2"><x-icon name="swap" />{{ $other[1] }}</a>
            @endif
            <a href="{{ route('settings') }}" wire:navigate class="flex h-11 items-center gap-2.5 rounded-xl px-2.5 text-sm font-bold text-ink transition hover:bg-surface2"><x-icon name="settings" />Account settings</a>
            <a href="{{ route('about') }}" wire:navigate class="flex h-11 items-center gap-2.5 rounded-xl px-2.5 text-sm font-bold text-ink transition hover:bg-surface2"><x-icon name="love" />Meet the developer</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex h-11 w-full cursor-pointer items-center gap-2.5 rounded-xl bg-surface2 px-2.5 text-sm font-bold text-ink"><x-icon name="logout" />Log out</button>
            </form>
        </div>
    </header>

    <main id="main" tabindex="-1" @class(['relative min-w-0 flex-1', 'pb-19 lg:pb-0' => count($nav) > 1])>
        {{ $slot }}
    </main>

    {{-- Mobile bottom nav --}}
    @if (count($nav) > 1)
        <nav aria-label="Main" class="fixed inset-x-0 bottom-0 z-20 flex h-19 border-t border-line bg-surface px-1.5 pt-1.5 pb-3.5 lg:hidden">
            @foreach ($nav as [$route, $label, $icon, $badge])
                @php($on = request()->routeIs($route))
                <a href="{{ route($route) }}" wire:navigate @if ($on) aria-current="page" @endif @class(['flex min-w-0 flex-1 flex-col items-center justify-center gap-1 text-[11px] font-bold', 'text-ink' => $on, 'text-ink3' => ! $on])>
                    <span @class(['relative flex h-8 w-14.5 items-center justify-center rounded-full transition-colors', 'bg-lime text-on-lime' => $on])>
                        <x-icon :name="$icon" />
                        @if ($badge)
                            <span class="absolute -top-2 left-9.5 flex h-5 min-w-4.5 items-center justify-center rounded-full border-2 border-surface bg-hot px-1.5 text-[9px] font-extrabold tracking-[.06em] text-white">{{ $badge }}</span>
                        @endif
                    </span>
                    <span class="whitespace-nowrap">{{ $label }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    <x-toast />
</div>
