<?php

namespace App\Support;

/**
 * "Your look": avatar borders and queue backgrounds a member can wear (CSS in resources/css/looks.css).
 * Exclusive looks only render on accounts an admin marked as developer.
 */
class Looks
{
    /** Avatar borders, id => [label, animated], in picker order. */
    public const FRAMES = [
        'spin' => ['Rainbow spin', true], 'comet' => ['Comet', true], 'fire' => ['On fire', true], 'ocean' => ['Ocean', true], 'pulse' => ['Lime pulse', true],
        'neon' => ['Neon', true], 'orbit' => ['Orbit', true], 'disco' => ['Disco', true], 'heart' => ['Heartbeat', true], 'aurora' => ['Aurora', true],
        'gold' => ['Gold', false], 'silver' => ['Silver', false], 'lime' => ['Lime', false], 'court' => ['Court lines', false], 'kitchen' => ['Kitchen tape', false],
        'rainbow' => ['Rainbow', false], 'sunset' => ['Sunset', false], 'mint' => ['Mint dots', false], 'night' => ['Night', false], 'wood' => ['Paddle wood', false],
    ];

    /** Queue backgrounds, id => [label, animated], in picker order. */
    public const QUEUE_BGS = [
        'shimmer' => ['Shimmer', true], 'barber' => ['Barber pole', true], 'bounce' => ['Bouncing ball', true], 'flow' => ['Color flow', true], 'glow' => ['Lime glow', true],
        'rally' => ['Rally', true], 'aurora' => ['Aurora', true], 'drift' => ['Confetti drift', true], 'waves' => ['Waves', true], 'sparkle' => ['Sparkle', true],
        'courtlines' => ['Court lines', false], 'dots' => ['Pickleball dots', false], 'stripes' => ['Lime stripes', false], 'grid' => ['Graph paper', false], 'sunset' => ['Sunset', false],
        'mint' => ['Mint', false], 'checker' => ['Checker', false], 'confetti' => ['Confetti', false], 'night' => ['Night sky', false], 'lemon' => ['Lemonade', false],
    ];

    /** Developer accounts only (both kinds). */
    public const EXCLUSIVE = ['dev' => 'Developer'];

    /** kind: "frame" or "queue". */
    public static function list(string $kind): array
    {
        return $kind === 'frame' ? self::FRAMES : self::QUEUE_BGS;
    }

    public static function valid(string $kind, ?string $id): bool
    {
        return $id !== null && (isset(self::list($kind)[$id]) || isset(self::EXCLUSIVE[$id]));
    }

    public static function label(string $kind, ?string $id): ?string
    {
        return self::list($kind)[$id][0] ?? self::EXCLUSIVE[$id] ?? null;
    }

    /** CSS classes for a worn look, or '' (none, unknown, or exclusive on a non-developer). */
    public static function cls(string $kind, ?string $id, bool $developer): string
    {
        if (! self::valid($kind, $id) || (isset(self::EXCLUSIVE[$id]) && ! $developer)) {
            return '';
        }
        $pre = $kind === 'frame' ? 'px-fr' : 'px-qb';

        return "{$pre} {$pre}-{$id}";
    }
}
