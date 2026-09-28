{{-- Client-only: flips data-theme on <html> (see setTheme in the layout). --}}
<button
    type="button"
    onclick="setTheme()"
    aria-label="Toggle dark mode"
    {{ $attributes->merge(['class' => 'inline-flex size-11 items-center justify-center rounded-full border border-line bg-surface text-ink cursor-pointer transition hover:brightness-96 active:scale-97']) }}
>
    <svg class="hidden dark:block" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM12 2.5v2M12 19.5v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2.5 12h2M19.5 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    <svg class="dark:hidden" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.6A8 8 0 0 1 9.4 4 8 8 0 1 0 20 14.6Z"/></svg>
</button>
