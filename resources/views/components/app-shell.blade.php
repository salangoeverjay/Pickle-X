{{--
    Signed-in layout: sidebar on desktop; top bar, account menu and bottom nav on mobile.
    Wrap each app screen's view in <x-app-shell>.
--}}
@php
    $user = auth()->user();
    $mode = $user->mode();
    // The page's route, also during Livewire updates (those hit the livewire.update route, which would drop the highlight).
    $page = Livewire::isLivewireRequest()
        ? rescue(fn () => app('router')->getRoutes()->match(\Illuminate\Http\Request::create(Livewire::originalUrl()))->getName(), null, false)
        : Route::currentRouteName();
    $isPage = fn (string $name) => Str::is($name, (string) $page);
    // One account, two modes: approved organizers switch between organizer and player.
    $other = $user->isOrganizer() ? ($mode === 'organizer' ? ['member.dashboard', 'Switch to player mode'] : ['organizer.plays', 'Switch to organizer mode']) : null;
    // What's new: unread updates for this person (admins write them, so they don't get any).
    $wnU = $user->isAdmin() ? 0 : \App\Models\News::unreadBy($user)->count();
    $nav = match ($mode) {
        'organizer' => [
            ['organizer.plays', 'Open plays', 'calendar', null],
            ['organizer.board', 'Court board', 'court', \App\Models\OpenPlay::runBy($user)->status('live')->exists() ? 'LIVE' : null],
        ],
        'admin' => [
            ['admin.requests', 'Requests', 'shield', \App\Models\User::where('organizer_status', 'pending')->count() ?: null],
            ['admin.users', 'Users', 'users', null],
            ['admin.plays', 'Open plays', 'calendar', null],
            ['admin.whats-new', "What's new", 'news', null],
            ['admin.developers', 'Developers', 'code', null],
            ['admin.sponsors', 'Sponsors', 'heart', null],
        ],
        default => [
            ['member.dashboard', 'Home', 'home', null],
            ['member.plays', 'Open plays', 'calendar', null],
            ['member.played-with', 'Played with', 'users', null],
            ['member.rankings', 'Rankings', 'trophy', null],
            // A member co-host runs the court board from player mode while their session is live.
            ...(\App\Models\OpenPlay::status('live')->whereHas('cohosts', fn ($q) => $q->whereKey($user->id))->exists() ? [['organizer.board', 'Court board', 'court', 'LIVE']] : []),
        ],
    };
@endphp

<div class="flex min-h-dvh flex-col md:flex-row">
    {{-- Tablet rail (768 to 1279px wide): the sidebar as a slim column of icons. --}}
    <aside aria-label="PickleX" class="sticky top-0 z-30 hidden h-dvh w-24 shrink-0 flex-col items-center gap-3.5 border-r border-line bg-surface px-2.5 pt-5 pb-4 md:flex xl:hidden">
        <a href="{{ route($user->homeRoute()) }}" wire:navigate aria-label="PickleX home" class="flex size-13 shrink-0 items-center justify-center rounded-2xl bg-lime text-on-lime transition active:scale-97">
            <svg width="30" height="30" viewBox="20 14 228 228" aria-hidden="true"><path fill="currentColor" d="M28.14 65.15 L189.85 226.86 A24.37 24.37 0 0 0 224.31 192.4 L62.6 30.69 A24.37 24.37 0 0 0 28.14 65.15 Z M108.99 111.55 L28.14 192.4 A24.37 24.37 0 0 0 62.6 226.86 L143.45 146.01 A24.37 24.37 0 0 0 108.99 111.55 Z M145.32 84.37 A25.31 25.31 0 1 1 195.93 84.37 A25.31 25.31 0 1 1 145.32 84.37 Z M190.39 39.31 A25.31 25.31 0 1 1 241 39.31 A25.31 25.31 0 1 1 190.39 39.31 Z" /></svg>
        </a>
        @if ($other)
            <div role="radiogroup" aria-label="Mode" class="flex w-19 flex-col gap-0.5 rounded-[14px] border border-line bg-surface2 p-0.75">
                @foreach (['organizer' => ['organizer.plays', 'Organizer'], 'member' => ['member.dashboard', 'Player']] as $m => [$route, $label])
                    <a href="{{ route($route) }}" wire:navigate role="radio" aria-checked="{{ $mode === $m ? 'true' : 'false' }}" @class(['flex h-7.5 items-center justify-center rounded-[10px] text-[11px] font-bold transition-colors', 'bg-ink text-bg' => $mode === $m, 'text-ink2 hover:text-ink' => $mode !== $m])>{{ $label }}</a>
                @endforeach
            </div>
        @else
            <span class="inline-flex h-5.5 items-center rounded-full border border-line bg-surface2 px-2 font-mono text-[10px] tracking-widest text-ink2 uppercase">{{ $user->roleLabel() }}</span>
        @endif
        <nav aria-label="Main" class="flex flex-col items-center gap-1.5">
            @foreach ($nav as [$route, $label, $icon, $badge])
                @php($on = $isPage($route))
                <a href="{{ route($route) }}" wire:navigate @if ($on) aria-current="page" @endif @class(['relative flex min-h-15 w-19 flex-col items-center justify-center gap-1 rounded-2xl px-1 py-2 text-center text-[11.5px] leading-[1.15] font-bold transition active:scale-97', 'bg-lime text-on-lime' => $on, 'text-ink2 hover:bg-surface2 hover:text-ink' => ! $on])>
                    <x-icon :name="$icon" :size="22" /><span>{{ $label }}</span>
                    @if ($badge)
                        <span class="absolute top-1 right-1.5 inline-flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-hot px-1.25 font-mono text-[10px] font-semibold text-white">{{ $badge }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        @if ($mode === 'organizer')
            <a href="{{ route('organizer.plays', ['create' => 1]) }}" wire:navigate aria-label="Create open play" class="flex min-h-15 w-19 flex-col items-center justify-center gap-1 rounded-2xl border-[1.5px] border-dashed border-line2 px-1 py-2 text-[11.5px] leading-[1.15] font-bold text-ink transition hover:bg-surface2 active:scale-97">
                <x-icon name="plus" :size="20" /><span>Create</span>
            </a>
        @endif
        <div class="flex-1"></div>
        <x-theme-toggle class="size-12 rounded-[14px] border-0 bg-transparent text-ink2" />
        <div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false">
            <button type="button" x-on:click="open = ! open" aria-haspopup="menu" x-bind:aria-expanded="open" aria-controls="px-rm" aria-label="Account menu for {{ $user->name }}{{ $wnU ? ", {$wnU} new in What's new" : '' }}" class="relative flex size-13 cursor-pointer items-center justify-center rounded-full active:scale-97">
                <x-avatar :src="$user->avatarUrl()" :frame="$user->frameClass()" :name="$user->name" :color="$user->color()" class="size-11 text-sm" />
                @if ($wnU)
                    <span aria-hidden="true" class="absolute top-0.5 right-0.5 z-1 size-2.75 rounded-full border-2 border-surface bg-hot"></span>
                @endif
            </button>
            <template x-if="open">
                <div>
                    <button type="button" tabindex="-1" aria-hidden="true" x-on:click="open = false" class="fixed inset-0 z-40 cursor-default"></button>
                    <div id="px-rm" role="menu" aria-label="Your account" class="px-pm-pop absolute bottom-0 left-[calc(100%+22px)] z-41 flex w-70 flex-col gap-0.5 rounded-[18px] border border-line bg-surface p-1.5 shadow-card">
                        <div class="flex min-w-0 flex-col px-3 pt-2.5 pb-2"><span class="truncate font-bold">{{ $user->name }}</span><span class="truncate text-[13px] text-ink3">{{ $user->email }}</span></div>
                        @unless ($user->isAdmin())
                            <a href="{{ route('whats-new') }}" wire:navigate role="menuitem" class="flex h-11.5 w-full cursor-pointer items-center gap-3 rounded-xl px-3 text-left text-sm font-bold text-ink transition hover:bg-surface2 active:scale-97"><x-icon name="news" /><span class="flex-1">What's new</span>@if ($wnU)<span class="inline-flex h-5.5 items-center rounded-full bg-lime px-2 text-[11.5px] font-extrabold text-on-lime">{{ $wnU }} new</span>@endif</a>
                        @endunless
                        <a href="{{ route('settings') }}" wire:navigate role="menuitem" class="flex h-11.5 w-full cursor-pointer items-center gap-3 rounded-xl px-3 text-left text-sm font-bold text-ink transition hover:bg-surface2 active:scale-97"><x-icon name="settings" /><span class="flex-1">Account settings</span></a>
                        <a href="{{ route('about') }}" wire:navigate role="menuitem" class="flex h-11.5 w-full cursor-pointer items-center gap-3 rounded-xl px-3 text-left text-sm font-bold text-ink transition hover:bg-surface2 active:scale-97"><x-icon name="love" /><span class="flex-1">Meet the developer</span></a>
                        <button type="button" role="menuitem" x-on:click="open = false; $dispatch('open-coffee')" aria-haspopup="dialog" class="flex h-11.5 w-full cursor-pointer items-center gap-3 rounded-xl px-3 text-left text-sm font-bold text-ink transition hover:bg-surface2 active:scale-97"><x-icon name="coffee" :stroke="1.8" /><span class="flex-1">Buy me a coffee</span></button>
                        <form method="POST" action="{{ route('logout') }}" x-data x-on:submit="$dispatch('px-loading', { kind: 'logout', first: @js($user->firstName()) })">
                            @csrf
                            <button type="submit" role="menuitem" class="flex h-11.5 w-full cursor-pointer items-center gap-3 rounded-xl px-3 text-left text-sm font-bold text-ink transition hover:bg-surface2 active:scale-97 text-hot!"><x-icon name="logout" /><span class="flex-1">Log out</span></button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </aside>

    {{-- Desktop sidebar --}}
    <aside class="sticky top-0 hidden h-dvh w-65 shrink-0 flex-col gap-5 border-r border-line bg-surface px-4 pt-6.5 pb-4.5 xl:flex">
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
                @php($on = $isPage($route))
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

        <button type="button" x-on:click="$dispatch('open-coffee')" aria-haspopup="dialog"
                class="flex h-12 cursor-pointer items-center gap-3 rounded-[14px] px-3 text-left text-sm font-semibold text-ink2 transition hover:bg-surface2 active:scale-97">
            <x-icon name="coffee" :stroke="1.9" /><span class="flex-1">Buy me a coffee</span><span class="inline-flex h-5.5 items-center rounded-full border border-line bg-surface2 px-2 font-mono text-xs font-semibold tracking-[.04em] whitespace-nowrap text-ink2">QR Ph</span>
        </button>

        <button type="button" onclick="setTheme()" x-data="{ dark: document.documentElement.dataset.theme === 'dark' }" x-on:click="dark = ! dark" role="switch" x-bind:aria-checked="dark" aria-label="Dark mode"
                class="flex h-12 cursor-pointer items-center gap-3 rounded-[14px] px-3 text-left text-sm font-semibold text-ink2 transition hover:bg-surface2">
            <x-icon name="moon" class="dark:hidden" /><x-icon name="sun" class="hidden dark:block" />
            <span class="flex-1">Dark mode</span>
            <span class="relative h-6 w-10.5 rounded-full bg-line2 transition-colors dark:bg-lime">
                <span class="absolute top-0.75 left-0.75 size-4.5 rounded-full bg-white transition-[left] dark:left-5.25 dark:bg-on-lime"></span>
            </span>
        </button>

        {{-- Account card: opens a menu with Account settings and Meet the developer. --}}
        @php($acct = $isPage('settings') || $isPage('about') || $isPage('whats-new'))
        <div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false">
            <template x-if="open">
                <div>
                    <button type="button" tabindex="-1" aria-hidden="true" x-on:click="open = false" class="fixed inset-0 z-40 cursor-default"></button>
                    <div id="px-pm" role="menu" aria-label="Your account" class="px-pm-pop absolute inset-x-0 bottom-[calc(100%+8px)] z-41 flex flex-col gap-0.5 rounded-[18px] border border-line bg-surface p-1.5 shadow-card">
                        @foreach ([...($user->isAdmin() ? [] : [['whats-new', "What's new", 'news']]), ['settings', 'Account settings', 'settings'], ['about', 'Meet the developer', 'love']] as [$route, $label, $icon])
                            <a href="{{ route($route) }}" wire:navigate role="menuitem" @if ($isPage($route)) aria-current="page" @endif
                               @class(['flex h-11.5 items-center gap-3 rounded-xl px-3 text-sm font-bold text-ink transition hover:bg-surface2 active:scale-97', 'bg-lime-soft' => $isPage($route)])>
                                <x-icon :name="$icon" /><span class="flex-1">{{ $label }}</span>
                                @if ($route === 'whats-new' && $wnU)
                                    <span class="inline-flex h-5.5 items-center rounded-full bg-lime px-2 text-[11.5px] font-extrabold text-on-lime">{{ $wnU }} new</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </template>
            <div x-bind:class="open && 'bg-surface'" @class(['flex items-center gap-1 rounded-2xl border p-1.5 transition-colors', 'border-ink bg-surface' => $acct, 'border-line bg-surface2' => ! $acct])>
                <button type="button" x-on:click="open = ! open" aria-haspopup="menu" x-bind:aria-expanded="open" aria-controls="px-pm" aria-label="Account menu for {{ $user->name }}"
                        class="flex min-h-12 min-w-0 flex-1 cursor-pointer items-center gap-2.5 rounded-xl p-1 text-left text-ink active:scale-97">
                    <x-avatar :src="$user->avatarUrl()" :frame="$user->frameClass()" :name="$user->name" :color="$user->color()" class="size-9.5 text-[13px]" />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate text-sm font-bold">{{ $user->name }}</span>
                        <span class="truncate text-xs text-ink3">{{ $user->email }}</span>
                    </span>
                    @if ($wnU)
                        <span role="img" aria-label="{{ $wnU }} new in What's new" class="size-2.25 shrink-0 rounded-full bg-hot"></span>
                    @endif
                    <x-icon name="chevron-down" :size="16" :stroke="2.2" class="shrink-0 text-ink3 transition-transform duration-200" x-bind:class="open && 'rotate-180'" />
                </button>
                <form method="POST" action="{{ route('logout') }}" x-data x-on:submit="$dispatch('px-loading', { kind: 'logout', first: @js($user->firstName()) })">
                    @csrf
                    <button type="submit" aria-label="Log out" class="flex size-9.5 cursor-pointer items-center justify-center rounded-xl text-ink2 transition hover:bg-surface2 active:scale-97"><x-icon name="logout" /></button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Mobile top bar --}}
    <header x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="sticky top-0 z-20 flex h-15 shrink-0 items-center justify-between border-b border-line bg-surface pr-2.5 pl-4 md:hidden">
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
            <button type="button" x-on:click="open = ! open" aria-label="Account menu{{ $wnU ? ", {$wnU} new in What's new" : '' }}" x-bind:aria-expanded="open" class="relative flex size-11 cursor-pointer items-center justify-center rounded-full">
                <x-avatar :src="$user->avatarUrl()" :frame="$user->frameClass()" :name="$user->name" :color="$user->color()" class="size-8.5 text-xs" />
                @if ($wnU)
                    <span aria-hidden="true" class="absolute top-1.25 right-1.25 z-1 size-2.5 rounded-full border-2 border-bg bg-hot"></span>
                @endif
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
            @unless ($user->isAdmin())
                <a href="{{ route('whats-new') }}" wire:navigate class="flex h-11 items-center gap-2.5 rounded-xl px-2.5 text-sm font-bold text-ink transition hover:bg-surface2"><x-icon name="news" /><span class="flex-1">What's new</span>@if ($wnU)<span class="inline-flex h-5.5 items-center rounded-full bg-lime px-2 text-[11.5px] font-extrabold text-on-lime">{{ $wnU }} new</span>@endif</a>
            @endunless
            <a href="{{ route('settings') }}" wire:navigate class="flex h-11 items-center gap-2.5 rounded-xl px-2.5 text-sm font-bold text-ink transition hover:bg-surface2"><x-icon name="settings" />Account settings</a>
            <a href="{{ route('about') }}" wire:navigate class="flex h-11 items-center gap-2.5 rounded-xl px-2.5 text-sm font-bold text-ink transition hover:bg-surface2"><x-icon name="love" />Meet the developer</a>
            <button type="button" x-on:click="open = false; $dispatch('open-coffee')" aria-haspopup="dialog" class="flex h-11 cursor-pointer items-center gap-2.5 rounded-xl px-2.5 text-left text-sm font-bold text-ink transition hover:bg-surface2">
                <x-icon name="coffee" :size="18" :stroke="1.8" /><span class="flex-1">Buy me a coffee</span><span class="inline-flex h-5.5 items-center rounded-full border border-line bg-surface2 px-2 font-mono text-xs font-semibold tracking-[.04em] whitespace-nowrap text-ink2">QR Ph</span>
            </button>
            <form method="POST" action="{{ route('logout') }}" x-data x-on:submit="$dispatch('px-loading', { kind: 'logout', first: @js($user->firstName()) })">
                @csrf
                <button type="submit" class="flex h-11 w-full cursor-pointer items-center gap-2.5 rounded-xl bg-surface2 px-2.5 text-sm font-bold text-ink"><x-icon name="logout" />Log out</button>
            </form>
        </div>
    </header>

    <main id="main" tabindex="-1" @class(['relative min-w-0 flex-1', 'pb-19 md:pb-0' => count($nav) > 1])>
        {{ $slot }}
    </main>

    {{-- Mobile bottom nav --}}
    @if (count($nav) > 1)
        {{-- Steps aside while the keyboard is up, so it doesn't cover what you're typing into. --}}
        <nav aria-label="Main" x-data="{ typing: false }" x-on:focusin.window="typing = $event.target.matches('input:not([type=radio], [type=checkbox], [type=file]), textarea, select')" x-on:focusout.window="typing = false"
             x-show="! typing" class="fixed inset-x-0 bottom-0 z-20 flex h-19 border-t border-line bg-surface px-1.5 pt-1.5 pb-3.5 md:hidden">
            @foreach ($nav as [$route, $label, $icon, $badge])
                @php($on = $isPage($route))
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

    {{-- What's new popup: unread updates, once. Closing it either way marks them read. --}}
    @if ($wnU && ! $isPage('whats-new'))
        @php($wnList = \App\Models\News::unreadBy($user)->get())
        <div wire:key="wn-pop" x-data="{ open: true, close() { this.open = false; fetch(@js(route('whats-new.read')), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content }, body: JSON.stringify({ ids: @js($wnList->pluck('id')) }) }) } }"
             x-show="open" x-on:keydown.escape.window="close()" class="fixed inset-0 z-67 flex items-end justify-center md:items-center md:p-6">
            <button type="button" x-on:click="close()" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
            <div role="dialog" aria-modal="true" aria-labelledby="wn-title" x-trap.noscroll="open" class="relative flex max-h-full w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-135 md:max-w-full md:rounded-[28px]">
                <div class="flex shrink-0 items-start justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex min-w-0 flex-col gap-1">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">What's new in PickleX</span>
                        <h2 id="wn-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $wnU === 1 ? 'One update since you were last here' : "{$wnU} updates since you were last here" }}</h2>
                    </div>
                    <button type="button" x-on:click="close()" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink"><x-icon name="x" :size="18" /></button>
                </div>
                <div class="flex min-h-0 flex-1 flex-col gap-2.5 overflow-y-auto px-6 py-4.5">
                    @foreach ($wnList->take(3) as $n)
                        <x-news-card :news="$n" :fresh="true" />
                    @endforeach
                    @if ($wnU > 3)
                        <span class="text-[13px] text-ink3">+ {{ $wnU - 3 }} more in What's new</span>
                    @endif
                </div>
                <div class="flex shrink-0 gap-2.5 border-t border-line px-6 pt-3.5 pb-5.5">
                    <a href="{{ route('whats-new') }}" wire:navigate x-on:click="open = false" class="flex h-13 flex-1 items-center justify-center rounded-full border-[1.5px] border-line2 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">See all updates</a>
                    <button type="button" x-on:click="close()" class="h-13 flex-1 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-[15px] font-extrabold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Got it</button>
                </div>
            </div>
        </div>
    @endif

    <x-coffee />
    <x-toast />
</div>
