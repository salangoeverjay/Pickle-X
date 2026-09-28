<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')

        @livewireStyles
    </head>
    <body>
        <a href="#main" class="sr-only z-100 rounded-full bg-ink px-4 py-2.5 text-sm font-bold text-bg focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

        {{ $slot }}

        @livewireScripts
    </body>
</html>
