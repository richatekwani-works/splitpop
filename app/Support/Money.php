<?php

namespace App\Support;

/**
 * All money is integer minor units (paise/cents). No floats in the maths.
 */
class Money
{
    public static function fmt(int $cents, string $symbol = '₹'): string
    {
        return $symbol . number_format($cents / 100, 2);
    }

    public static function toCents(string|int|float|null $value): int
    {
        return (int) round(((float) ($value ?? 0)) * 100);
    }

    /**
     * Split $total evenly across member ids. Leftover paise go one each to the first people,
     * so shares always add up to exactly $total.
     *
     * @param  int[]  $ids
     * @return array<int,int> member id => share in cents
     */
    public static function splitEqual(int $total, array $ids): array
    {
        $ids = array_values($ids);
        $n = count($ids);
        if ($n === 0) {
            return [];
        }

        $base = intdiv($total, $n);
        $rem = $total % $n;
        $out = [];
        foreach ($ids as $i => $id) {
            $out[$id] = $base + ($i < $rem ? 1 : 0);
        }

        return $out;
    }
}
