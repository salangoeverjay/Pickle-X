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

    /** Exclusive looks (both kinds), id => [label, unlock]: "dev" for developer accounts, or a sponsor tier. */
    public const EXCLUSIVE = [
        'dev' => ['Developer', 'dev'],
        'sp-gold' => ['Gold sponsor', 'gold'],
        'sp-silver' => ['Silver sponsor', 'silver'],
        'sp-bronze' => ['Bronze sponsor', 'bronze'],
    ];

    public const TIERS = ['gold' => 'Gold', 'silver' => 'Silver', 'bronze' => 'Bronze'];

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
        return self::list($kind)[$id][0] ?? self::EXCLUSIVE[$id][0] ?? null;
    }

    /** Whether an exclusive unlock ("dev" or a tier) is open to this account. */
    public static function unlocked(string $unlock, bool $developer, ?string $tier): bool
    {
        return $unlock === 'dev' ? $developer : $unlock === $tier;
    }

    /** "Developer accounts only" / "Gold sponsors only". */
    public static function onlyFor(string $unlock): string
    {
        return $unlock === 'dev' ? 'Developer accounts only' : self::TIERS[$unlock].' sponsors only';
    }

    /**
     * The look an account actually shows, or null. Exclusive looks need their unlock;
     * a sponsor who moved tier shows the new tier's look.
     */
    public static function effective(string $kind, ?string $id, bool $developer, ?string $tier): ?string
    {
        if (! self::valid($kind, $id)) {
            return null;
        }
        if (! isset(self::EXCLUSIVE[$id]) || self::unlocked(self::EXCLUSIVE[$id][1], $developer, $tier)) {
            return $id;
        }

        return self::EXCLUSIVE[$id][1] !== 'dev' && isset(self::TIERS[$tier]) ? "sp-{$tier}" : null;
    }

    /** CSS classes for a look as worn (see effective()), or ''. */
    public static function cls(string $kind, ?string $id, bool $developer, ?string $tier): string
    {
        return self::preview($kind, self::effective($kind, $id, $developer, $tier));
    }

    /** CSS classes for any look, unlocked or not (picker tiles and admin previews). */
    public static function preview(string $kind, ?string $id): string
    {
        if (! self::valid($kind, $id)) {
            return '';
        }
        $pre = $kind === 'frame' ? 'px-fr' : 'px-qb';

        return "{$pre} {$pre}-{$id}";
    }
}
