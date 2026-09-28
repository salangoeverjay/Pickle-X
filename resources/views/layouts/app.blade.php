<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        {{-- Set theme before paint to avoid a light flash in dark mode. setTheme() is shared by every theme control. --}}
        <script>
            window.setTheme = (theme) => {
                theme ??= document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                document.documentElement.dataset.theme = theme;
                try { localStorage.setItem('theme', theme); } catch {}
            };
            (() => {
                let theme;
                try { theme = localStorage.getItem('theme'); } catch {}
                document.documentElement.dataset.theme = theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            })();
        </script>

        {{ Vite::fonts() }}

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body>
        {{ $slot }}

        @livewireScripts
    </body>
</html>
