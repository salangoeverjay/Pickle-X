<?php

// Small formatting helpers shared by models and views.

if (! function_exists('initials')) {
    /** "Kai Santos" => "KS", "Mika" => "M". */
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return strtoupper(mb_substr($parts[0] ?? '?', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }
}

if (! function_exists('palette_color')) {
    /** Stable avatar color for things without a chosen one (walk-ins, requests). */
    function palette_color(int $n): string
    {
        return array_keys(App\Models\User::AVATARS)[$n % count(App\Models\User::AVATARS)];
    }
}

if (! function_exists('clock')) {
    /** Seconds => "mm:ss". */
    function clock(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}

if (! function_exists('ago')) {
    /** Seconds => "1h 13m" / "4m" / "just now". */
    function ago(int $seconds): string
    {
        $m = intdiv(max(0, $seconds), 60);

        return $m >= 60 ? intdiv($m, 60).'h '.($m % 60).'m' : ($m ? $m.'m' : 'just now');
    }
}
