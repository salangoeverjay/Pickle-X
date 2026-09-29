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

    /**
     * Collections: themed animated looks, each id a border and a matching queue background.
     * key => [label, blurb, icon path (24px), icon color, [id => label]].
     */
    public const COLLECTIONS = [
        'nj' => ['Shinobi', 'Ninja fire, storms, tides, smoke and sakura.', 'M12 2.5 14 10l7.5 2L14 14l-2 7.5L10 14l-7.5-2L10 10ZM13.6 12a1.6 1.6 0 1 1-3.2 0 1.6 1.6 0 0 1 3.2 0Z', '#D7263D', [
            'nj-flame' => 'Shinobi flame', 'nj-storm' => 'Storm blade', 'nj-tidal' => 'Tidal wave', 'nj-gale' => 'Gale slash', 'nj-stone' => 'Stone guard',
            'nj-sakura' => 'Sakura storm', 'nj-shadow' => 'Shadow smoke', 'nj-talisman' => 'Talisman', 'nj-crane' => 'Paper crane', 'nj-spirit' => 'Spirit aura',
        ]],
        'dh' => ['Demon Hunter', 'Katanas, oni masks, lanterns, moonlit nights and shrine gates.', 'M4.5 19.5 18 6M16 4l4 4M6.5 14.5l3 3M3.5 20.5l2-2', '#8C1424', [
            'dh-ember' => 'Ember katana', 'dh-frost' => 'Frost katana', 'dh-twin' => 'Twin blades', 'dh-oni' => 'Oni mask', 'dh-horns' => 'Oni horns',
            'dh-lantern' => 'Lantern night', 'dh-moon' => 'Crescent night', 'dh-ume' => 'Plum blossom', 'dh-torii' => 'Shrine gate', 'dh-koi' => 'Koi pond',
        ]],
        'pr' => ['Pirate', 'Jolly Rogers, treasure, ship wheels, compasses and a kraken.', 'M12 3.5a6 6 0 0 0-6 6c0 2.2 1.1 3.6 2.6 4.4V16h6.8v-2.1C16.9 13.1 18 11.7 18 9.5a6 6 0 0 0-6-6ZM9.8 9.6h.01M14.2 9.6h.01M4 20l16-2.6M20 20 4 17.4', '#1E2124', [
            'pr-roger' => 'Jolly Roger', 'pr-treasure' => 'Treasure hoard', 'pr-wheel' => "Ship's wheel", 'pr-compass' => 'Compass rose', 'pr-anchor' => 'Anchor',
            'pr-gulls' => 'Sea spray', 'pr-cannon' => 'Cannon blast', 'pr-map' => 'Treasure map', 'pr-parrot' => 'Parrot pal', 'pr-kraken' => 'Kraken',
        ]],
        'sb' => ['Spirit Beasts', 'A silver spirit wolf, a blue-flame phoenix, a jade dragon and a thunder tiger.', 'M7 11.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM10.5 7.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM14.5 7.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM18 11.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM12.5 11c-3 0-6 3.5-6 6.5 0 2 1.5 3 3 3 1.2 0 2-.6 3-.6s1.8.6 3 .6c1.5 0 3-1 3-3 0-3-3-6.5-6-6.5Z', '#6F86A8', [
            'sb-wolf' => 'Silver spirit wolf', 'sb-phoenix' => 'Blue-flame phoenix', 'sb-dragon' => 'Jade dragon', 'sb-tiger' => 'Thunder tiger',
        ]],
        'fw' => ['Fireworks', 'Rainbow bursts, golden willows, sparklers, rockets, hearts and a grand finale.', 'M12 2.5v4M12 17.5v4M2.5 12h4M17.5 12h4M5.3 5.3l2.8 2.8M15.9 15.9l2.8 2.8M5.3 18.7l2.8-2.8M15.9 8.1l2.8-2.8M13.4 12a1.4 1.4 0 1 1-2.8 0 1.4 1.4 0 0 1 2.8 0Z', '#FF5A6E', [
            'fw-rainbow' => 'Rainbow burst', 'fw-willow' => 'Golden willow', 'fw-neon' => 'Neon peony', 'fw-cross' => 'Crossette', 'fw-sparkler' => 'Sparkler',
            'fw-rocket' => 'Rocket launch', 'fw-heart' => 'Heart burst', 'fw-star' => 'Star burst', 'fw-mum' => 'Chrysanthemum', 'fw-finale' => 'Grand finale',
        ]],
        'fl' => ['Fire', 'Blazing rings, blue and soul flames, embers, fireballs, molten lava and a flaming pickleball.', 'M12 21.5c-4 0-6.8-2.7-6.8-6.4 0-3.3 2.2-5.3 3.4-7.6.5 1.6 1.3 2.7 2.4 3.3-.2-3.4 1.2-6.4 3.8-8.3-.1 3.1 1.2 5 2.6 6.9 1.2 1.6 2.2 3.3 2.2 5.7 0 3.7-3.4 6.4-7.6 6.4ZM12 21.5c-1.9 0-3.2-1.2-3.2-3 0-1.6 1.1-2.6 1.9-3.8.3.8.7 1.3 1.3 1.6 0-1.4.6-2.6 1.6-3.4.1 1.4.6 2.3 1.2 3.1.5.7.9 1.4.9 2.5 0 1.8-1.6 3-3.7 3Z', '#FF5A1F', [
            'fl-blaze' => 'Blaze', 'fl-blue' => 'Blue flame', 'fl-inferno' => 'Inferno', 'fl-embers' => 'Ember storm', 'fl-fireball' => 'Fireball',
            'fl-crown' => 'Flame crown', 'fl-lava' => 'Molten lava', 'fl-violet' => 'Soul flame', 'fl-emerald' => 'Emerald flame', 'fl-streak' => 'Hot streak',
        ]],
    ];

    /** Collection key a look belongs to, or null. */
    public static function collectionOf(?string $id): ?string
    {
        foreach (self::COLLECTIONS as $key => $c) {
            if (isset($c[4][$id])) {
                return $key;
            }
        }

        return null;
    }

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
        return $id !== null && (isset(self::list($kind)[$id]) || isset(self::EXCLUSIVE[$id]) || self::collectionOf($id));
    }

    public static function label(string $kind, ?string $id): ?string
    {
        return self::list($kind)[$id][0] ?? self::EXCLUSIVE[$id][0] ?? (($c = self::collectionOf($id)) ? self::COLLECTIONS[$c][4][$id] : null);
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
