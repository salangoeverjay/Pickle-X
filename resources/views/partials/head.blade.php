{{-- Shared <head> contents for app pages and error pages. --}}
@php($description = 'PickleX runs pickleball open play: a fair rotating queue, partner locks, live court boards and stats from the games you play.')
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>{{ $title ?? config('app.name') }}</title>
<meta name="description" content="{{ $description }}">
<meta property="og:site_name" content="PickleX">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $title ?? config('app.name') }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url()->current() }}">

<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
{{-- Installable app (PWA). use-credentials sends cookies with the manifest request, so tunnels
     like ngrok's free plan (which put a warning page in front of cookie-less requests) still serve it. --}}
<link rel="manifest" href="/manifest.json" crossorigin="use-credentials">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="PickleX">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#F3F2EA" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0A0E0B" media="(prefers-color-scheme: dark)">

{{-- Set theme before paint to avoid a light flash in dark mode. setTheme() is shared by every theme control. --}}
<script>
    window.setTheme = (theme) => {
        theme ??= document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = theme;
        try { localStorage.setItem('theme', theme); } catch {}
    };
    window.applyTheme ??= () => {
        let theme;
        try { theme = localStorage.getItem('theme'); } catch {}
        document.documentElement.dataset.theme = theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        // Reduced motion: every avatar border and queue background shows its static version (looks.css).
        document.documentElement.classList.toggle('px-still', matchMedia('(prefers-reduced-motion: reduce)').matches);
    };
    applyTheme();
    if ('serviceWorker' in navigator && ! window.swRegistered) {
        window.swRegistered = true;
        addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
    // wire:navigate copies the new page's <html> attributes, which drops data-theme; restore it before paint.
    if (! window.themeNavigateHook) {
        window.themeNavigateHook = true;
        document.addEventListener('livewire:navigating', (e) => e.detail.onSwap(applyTheme));
        document.addEventListener('livewire:navigated', applyTheme);
    }
</script>

{{ Vite::fonts() }}

@vite(['resources/css/app.css', 'resources/js/app.js'])
