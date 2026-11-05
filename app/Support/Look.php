<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * The quirky bits: pop vibes and a weird little creature + colour for every friend.
 */
class Look
{
    public const VIBES = ['🍕', '🏖️', '🏠', '🎉', '☕', '🎬', '🛒', '✈️', '🍜', '🎮'];

    public const CURRENCIES = ['₹', '$', '€', '£'];

    private const CREATURES = ['👾', '🤖', '👽', '🦖', '🐙', '🦑', '🧌', '🐸', '🦝', '🐊'];

    private const COLORS = ['#c6ff3d', '#3de0ff', '#ffe14d', '#ff8a5c', '#b79bff', '#7dffb2', '#ff7aa8', '#9fb4ff'];

    /**
     * @return array<int,array{emoji:string,color:string}> keyed by member id
     */
    public static function for(Collection $members): array
    {
        $out = [];
        foreach ($members->values() as $i => $m) {
            $out[$m->id] = [
                'emoji' => self::CREATURES[$i % count(self::CREATURES)],
                'color' => self::COLORS[$i % count(self::COLORS)],
            ];
        }

        return $out;
    }
}
